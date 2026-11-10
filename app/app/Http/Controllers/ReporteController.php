<?php

namespace App\Http\Controllers;

// Modelo para consultar las categorías habilitadas.
use App\Models\Categoria;

// Modelo que representa los reclamos.
use App\Models\Reporte;

// Permite recibir y validar los datos enviados por el formulario.
use Illuminate\Http\Request;

// Permite identificar al usuario que inició sesión.
use Illuminate\Support\Facades\Auth;

// Permite ejecutar operaciones dentro de una transacción.
use Illuminate\Support\Facades\DB;

// Permite comprobar que la categoría seleccionada exista y esté activa.
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Storage;

class ReporteController extends Controller
{
    // Comprueba que el usuario sea ciudadano.
    // Si es supervisor u otro rol, bloquea el acceso con error 403.
    private function soloCiudadanos(): void
    {
        abort_unless(
            Auth::user()->rol === 'ciudadano',
            403,
            'Solo los ciudadanos pueden registrar reclamos.'
        );
    }

    // Muestra el formulario para registrar un reclamo.
    public function crear()
    {
        // Evita que otros roles accedan al formulario.
        $this->soloCiudadanos();

        // Obtiene únicamente las categorías activas, ordenadas por nombre.
        $categorias = Categoria::where('activa', 1)
            ->orderBy('nombre')
            ->get();

        // Envía las categorías a la vista que construiremos después.
        return view('reportes.crear', compact('categorias'));
    }

    // Procesa los datos que el ciudadano envía desde el formulario.
public function guardar(Request $request)
{
    // Solo un ciudadano autenticado puede registrar reclamos.
    $this->soloCiudadanos();

    // Validación de los datos y las fotografías.
    $datos = $request->validate([
        'categoria_id' => [
            'required',
            'integer',
            Rule::exists('categorias', 'id')->where('activa', 1),
        ],
        'descripcion' => ['required', 'string', 'min:10', 'max:500'],
        'direccion' => ['nullable', 'string', 'max:80'],
        'latitud' => ['required', 'numeric', 'between:-90,90'],
        'longitud' => ['required', 'numeric', 'between:-180,180'],

        // La tabla fotos admite como máximo cuatro por reclamo.
        'fotos' => ['required', 'array', 'min:1', 'max:4'],
        'fotos.*' => [
            'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
        ],
    ], [
        'categoria_id.required' => 'Elegí el tipo de problema.',
        'categoria_id.integer' => 'La categoría elegida no es válida.',
        'categoria_id.exists' => 'La categoría elegida no es válida.',
        'descripcion.required' => 'Describí brevemente el problema.',
        'descripcion.min' => 'La descripción debe tener al menos :min caracteres.',
        'descripcion.max' => 'La descripción no puede superar los :max caracteres.',
        'direccion.max' => 'La dirección no puede superar los :max caracteres.',
        'latitud.required' => 'Marcá en el mapa el lugar del problema.',
        'longitud.required' => 'Marcá en el mapa el lugar del problema.',
        'latitud.numeric' => 'La ubicación marcada no es válida.',
        'longitud.numeric' => 'La ubicación marcada no es válida.',
        'latitud.between' => 'La ubicación marcada no es válida.',
        'longitud.between' => 'La ubicación marcada no es válida.',
        'fotos.required' => 'Adjuntá al menos una fotografía.',
        'fotos.array' => 'Las fotografías enviadas no son válidas.',
        'fotos.min' => 'Adjuntá al menos una fotografía.',
        'fotos.max' => 'Podés adjuntar como máximo cuatro fotografías.',
        'fotos.*.image' => 'El archivo debe ser una imagen válida.',
        'fotos.*.mimes' => 'Solo se admiten fotografías JPG, PNG o WebP.',
        'fotos.*.max' => 'Cada fotografía puede pesar como máximo 2 MB.',
    ]);

    // Registramos las rutas para poder limpiar los archivos
    // si ocurre un error mientras se guarda el reclamo.
    $rutasGuardadas = [];

    try {
        DB::transaction(function () use ($datos, &$rutasGuardadas) {

            $categoria = Categoria::findOrFail($datos['categoria_id']);

            $estadoRecibidoId = DB::table('estados')
                ->where('nombre', 'recibido')
                ->value('id');

            abort_if(
                $estadoRecibidoId === null,
                500,
                'Falta el estado inicial "recibido" en la base.'
            );

            // Primero se crea el reclamo.
            $reporte = Reporte::create([
                'usuario_id' => Auth::id(),
                'categoria_id' => $categoria->id,
                'area_id' => $categoria->area_id_defecto,
                'estado_id' => $estadoRecibidoId,
                'descripcion' => $datos['descripcion'],
                'barrio' => $datos['direccion'] ?? null,
                'latitud' => round((float) $datos['latitud'], 6),
                'longitud' => round((float) $datos['longitud'], 6),
            ]);

            // Se registra el estado inicial en el historial.
            DB::table('historial_estados')->insert([
                'reporte_id' => $reporte->id,
                'estado_id' => $estadoRecibidoId,
                'area_id' => $categoria->area_id_defecto,
                'usuario_id' => Auth::id(),
                'observacion' => 'Reclamo registrado por el ciudadano',
            ]);

            // Guardar las fotografías en el disco público de Laravel.
            foreach ($datos['fotos'] as $indice => $foto) {

                // Cada reclamo tiene su propia carpeta.
                // Laravel genera un nombre único para cada archivo.
                $ruta = $foto->store(
                    'reportes/' . $reporte->id,
                    'public'
                );

                if ($ruta === false) {
                    throw new \RuntimeException(
                        'No se pudo almacenar una fotografía.'
                    );
                }

                $rutasGuardadas[] = $ruta;

                // Vincula la imagen con el reclamo en la tabla fotos.
                DB::table('fotos')->insert([
                    'reporte_id' => $reporte->id,
                    'url' => $ruta,
                    'orden' => $indice + 1,
                ]);
            }
        });

    } catch (\Throwable $error) {

        // La transacción revierte los registros de la base.
        // También eliminamos las imágenes ya almacenadas
        // para evitar archivos huérfanos.
        if (! empty($rutasGuardadas)) {
            Storage::disk('public')->delete($rutasGuardadas);
        }

        throw $error;
    }

    return redirect('/panel')
        ->with('status', 'Tu reclamo fue registrado correctamente.');
}
        // Muestra al ciudadano únicamente sus propios reclamos.
    public function misReclamos()
    {
        // Solo los ciudadanos pueden entrar.
        // Los supervisores reciben un error 403.
        abort_unless(
            Auth::user()->rol === 'ciudadano',
            403,
            'Solo los ciudadanos pueden consultar sus reclamos.'
        );

        // Busca los reclamos y carga sus categorías y estados.
        $reclamos = Reporte::with(['categoria', 'estado'])
            // El ID se obtiene de la sesión autenticada.
            ->where('usuario_id', Auth::id())
            // Ordena desde el reclamo más reciente.
            ->orderByDesc('fecha_creacion')
            // Si coinciden las fechas, prioriza el ID mayor.
            ->orderByDesc('id')
            ->get();

        // Envía los resultados a la nueva vista.
        return view('reportes.mis-reclamos', compact('reclamos'));
    }
}
