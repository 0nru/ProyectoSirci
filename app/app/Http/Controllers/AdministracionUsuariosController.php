<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdministracionUsuariosController extends Controller
{
    // Listado de cuentas, con búsqueda y paginación.
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->query('q', ''));
        $busqueda = mb_substr($busqueda, 0, 100);

        $usuarios = DB::table('usuarios')
            ->select('id', 'nombre', 'email', 'rol', 'activo', 'fecha_registro')
            ->when($busqueda !== '', function ($consulta) use ($busqueda) {
                $consulta->where(function ($q) use ($busqueda) {
                    $q->where('nombre', 'like', '%' . $busqueda . '%')
                      ->orWhere('email', 'like', '%' . $busqueda . '%');
                });
            })
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', compact('usuarios', 'busqueda'));
    }

    // El registro administrativo crea solo operadores. El registro público
    // continúa creando exclusivamente ciudadanos.
    public function crearOperador(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')],
            'telefono' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ]);

        User::create([
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'] ?? null,
            'password_hash' => $datos['password'], // Cast hashed del modelo User.
            'rol' => 'operador',
            // activo usa el valor predeterminado 1 de la base de datos.
        ]);

        return redirect()->route('usuarios.index')
            ->with('status', 'Cuenta de operador creada correctamente.');
    }

    // Cambio explícito de rol y estado. Evita perder al último supervisor
    // activo; las restricciones se aplican en servidor, no solo en la vista.
    public function actualizar(Request $request, int $usuario)
    {
        $datos = $request->validate([
            'rol' => ['required', Rule::in(['ciudadano', 'operador', 'supervisor'])],
            'activo' => ['required', Rule::in(['0', '1'])],
        ]);

        DB::transaction(function () use ($usuario, $datos) {
            // Bloquear siempre los supervisores activos en orden consistente
            // para proteger la regla también ante solicitudes concurrentes.
            $supervisoresActivos = DB::table('usuarios')
                ->where('rol', 'supervisor')
                ->where('activo', 1)
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            $objetivo = DB::table('usuarios')
                ->where('id', $usuario)
                ->lockForUpdate()
                ->first(['id', 'rol', 'activo']);

            abort_if($objetivo === null, 404);

            if ((int) $objetivo->id === (int) Auth::id()) {
                throw ValidationException::withMessages([
                    'usuarios' => 'No podés modificar tu propia cuenta desde esta pantalla.',
                ]);
            }

            $nuevoRol = $datos['rol'];
            $nuevoActivo = (int) $datos['activo'];
            $seRetiraSupervisorActivo = $objetivo->rol === 'supervisor'
                && (int) $objetivo->activo === 1
                && ($nuevoRol !== 'supervisor' || $nuevoActivo !== 1);

            if ($seRetiraSupervisorActivo && $supervisoresActivos->count() <= 1) {
                throw ValidationException::withMessages([
                    'usuarios' => 'Debe permanecer al menos un supervisor activo.',
                ]);
            }

            DB::table('usuarios')->where('id', $usuario)->update([
                'rol' => $nuevoRol,
                'activo' => $nuevoActivo,
            ]);
        }, 3);

        return redirect()->route('usuarios.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }
}
