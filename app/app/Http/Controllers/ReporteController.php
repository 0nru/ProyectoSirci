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
        // Vuelve a comprobar el rol: proteger solo la pantalla no alcanza.
        $this->soloCiudadanos();

        // Valida los datos antes de intentar guardarlos.
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
        ], [
            'categoria_id.required' => 'Elegí el tipo de problema.',
            'categoria_id.integer' => 'La categoría elegida no es válida.',
            'categoria_id.exists' => 'La categoría elegida no es válida.',
            'descripcion.required' => 'Describí brevemente el problema.',
            'descripcion.min' => 'La descripción debe tener al menos :min caracteres.',
            'descripcion.max' => 'La descripción no puede superar los :max caracteres.',
            'direccion.max' => 'La dirección o referencia no puede superar los :max caracteres.',
            'latitud.required' => 'Marcá en el mapa el lugar del problema.',
            'longitud.required' => 'Marcá en el mapa el lugar del problema.',
            'latitud.numeric' => 'La ubicación marcada no es válida. Volvé a marcarla en el mapa.',
            'longitud.numeric' => 'La ubicación marcada no es válida. Volvé a marcarla en el mapa.',
            'latitud.between' => 'La ubicación marcada no es válida. Volvé a marcarla en el mapa.',
            'longitud.between' => 'La ubicación marcada no es válida. Volvé a marcarla en el mapa.',
        ]);

        // Ejecuta el registro del reclamo y su historial como una sola operación.
        // Si falla cualquiera de las dos escrituras, la transacción se revierte.
        DB::transaction(function () use ($datos) {

            // Recupera la categoría que eligió el ciudadano.
            $categoria = Categoria::findOrFail($datos['categoria_id']);

            // Busca el identificador del estado inicial "recibido".
            $estadoRecibidoId = DB::table('estados')
                ->where('nombre', 'recibido')
                ->value('id');

            // Si falta ese estado en la base, detiene el proceso.
            abort_if(
                $estadoRecibidoId === null,
                500,
                'Falta el estado inicial "recibido" en la base.'
            );

            // Crea el reclamo con el usuario autenticado y los datos validados.
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

            // Registra en el historial que el reclamo fue recibido.
            DB::table('historial_estados')->insert([
                'reporte_id' => $reporte->id,
                'estado_id' => $estadoRecibidoId,
                'area_id' => $categoria->area_id_defecto,
                'usuario_id' => Auth::id(),
                'observacion' => 'Reclamo registrado por el ciudadano',
            ]);
        });

        // Vuelve al panel y muestra un mensaje de confirmación.
        return redirect('/panel')
            ->with('status', 'Tu reclamo fue registrado correctamente.');
    }
}
