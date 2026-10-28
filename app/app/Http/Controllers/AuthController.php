<?php

// Declara el espacio de nombres al que pertenece este archivo.
// Laravel organiza sus controladores dentro de App\Http\Controllers.
namespace App\Http\Controllers;

// Importa Request, una clase que permite acceder a los datos
// enviados por el usuario y administrar su sesión HTTP.
use Illuminate\Http\Request;

// Importa la herramienta de Laravel que gestiona la autenticación:
// comprobar credenciales, iniciar sesión y cerrar sesión.
use Illuminate\Support\Facades\Auth;

// Declara la clase AuthController, responsable de las acciones
// relacionadas con el inicio y el cierre de sesión.
class AuthController extends Controller
{
    // Muestra la pantalla de inicio de sesión.
    public function mostrarLogin()
    {
        // Busca la vista resources/views/login.blade.php
        // y la devuelve para que el navegador la muestre.
        return view('login');
    }

    // Recibe y procesa los datos enviados desde el formulario de login.
    public function login(Request $request)
    {
        // Valida los datos recibidos antes de intentar autenticar.
        // Si falta un dato o el email no tiene formato válido,
        // Laravel detiene el proceso y devuelve los errores al formulario.
        $datos = $request->validate([
            // El email es obligatorio y debe tener formato de correo.
            'email'    => ['required', 'email'],

            // La contraseña es obligatoria; aquí no se comprueba todavía
            // si es correcta, solo que se haya enviado.
            'password' => ['required'],
        ]);

        // Intenta iniciar sesión con los datos proporcionados.
        // Auth::attempt busca un usuario que coincida con el email,
        // verifica la contraseña usando el mecanismo de Laravel
        // y también exige que el campo activo tenga valor 1.
        // Si las credenciales son válidas, devuelve true; si no, false.
        $ok = Auth::attempt([
            'email'    => $datos['email'],
            'password' => $datos['password'],
            'activo'   => 1,
        ]);

        // Comprueba si Laravel pudo autenticar al usuario.
        if ($ok) {

            // Regenera el identificador de sesión para reducir el riesgo
            // de ataques de fijación de sesión (session fixation).
            $request->session()->regenerate();

            // Envía al usuario a la dirección que intentaba visitar
            // antes de iniciar sesión; si no había una, lo dirige a /panel.
            return redirect()->intended('/panel');
        }

        // Si el email, la contraseña o el estado activo no coinciden,
        // vuelve a la página anterior y muestra un error asociado al email.
        return back()
            ->withErrors([
                // Mensaje genérico: no revela si el email existe
                // o si la contraseña es la que está equivocada.
                'email' => 'Email o contraseña incorrectos.'
            ])

            // Conserva el email escrito para evitar que el usuario
            // tenga que ingresarlo de nuevo, pero no conserva la contraseña.
            ->onlyInput('email');
    }

    // Cierra la sesión del usuario que está autenticado.
    public function logout(Request $request)
    {
        // Elimina la autenticación actual: el usuario deja de estar
        // identificado como conectado.
        Auth::logout();

        // Invalida la sesión actual y elimina sus datos almacenados.
        $request->session()->invalidate();

        // Genera un nuevo token CSRF para proteger los formularios
        // frente a solicitudes falsificadas.
        $request->session()->regenerateToken();

        // Después de cerrar sesión, envía al usuario a /login.
        return redirect('/login');
    }
}
