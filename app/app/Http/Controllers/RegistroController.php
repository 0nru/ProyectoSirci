<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Alta de ciudadanos (RF-01).
class RegistroController extends Controller
{
    // Muestra el formulario de registro.
    public function mostrar()
    {
        return view('registro');
    }

    // Crea la cuenta e inicia la sesión automáticamente.
    public function registrar(Request $request)
    {
        $datos = $request->validate([
            'nombre'   => ['required', 'string', 'min:3', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:usuarios,email'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terminos' => ['accepted'],
        ], [
            'nombre.required'    => 'Ingresá tu nombre completo.',
            'nombre.min'         => 'El nombre debe tener al menos :min caracteres.',
            'email.required'     => 'Ingresá tu correo electrónico.',
            'email.email'        => 'El correo electrónico no tiene un formato válido.',
            'email.unique'       => 'Ya existe una cuenta con ese correo.',
            'password.required'  => 'Elegí una contraseña.',
            'password.min'       => 'La contraseña debe tener al menos :min caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'terminos.accepted'  => 'Tenés que aceptar los términos para crear la cuenta.',
        ]);

        // El modelo User guarda la contraseña ya cifrada (cast "hashed").
        $usuario = User::create([
            'nombre'        => $datos['nombre'],
            'email'         => $datos['email'],
            'telefono'      => $datos['telefono'] ?? null,
            'password_hash' => $datos['password'],
            'rol'           => 'ciudadano',
        ]);

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect('/panel')
            ->with('status', '¡Cuenta creada! Ya podés registrar tu primer reclamo.');
    }
}
