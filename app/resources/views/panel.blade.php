
<!DOCTYPE html>
<!-- Le indica al navegador que el documento utiliza HTML5. -->

<html lang="es">
<!-- Abre el documento HTML e indica que el idioma de la página es español. -->

<head>
    <!-- Contiene la configuración y los metadatos de la página. -->

    <meta charset="UTF-8">
    <!-- Permite mostrar correctamente caracteres como tildes y eñes. -->

    <title>SIRCI · Panel</title>
    <!-- Define el título que aparece en la pestaña del navegador. -->

</head>
<!-- Termina la sección de configuración de la página. -->

<body style="font-family:sans-serif; padding:40px;">
    <!-- Comienza el contenido visible.
         font-family establece una tipografía sencilla.
         padding:40px agrega espacio alrededor del contenido. -->

    <h1>Hola, {{ auth()->user()->nombre }}</h1>
    <!-- Muestra un encabezado de bienvenida.
         auth() permite acceder al sistema de autenticación de Laravel.
         user() obtiene al usuario que tiene la sesión iniciada.
         ->nombre accede al campo nombre de ese usuario.
         Las llaves dobles de Blade muestran el valor escapando HTML. -->

    <p>Rol: {{ auth()->user()->rol }}</p>
    <!-- Muestra el rol del usuario autenticado.
         Por ejemplo, podría mostrar "Administrador" o "Usuario",
         según los datos guardados en la base de datos. -->
         
{{-- Muestra el mensaje de confirmación después de registrar un reclamo. --}}
@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

{{-- El acceso para crear reclamos aparece únicamente a ciudadanos. --}}
@if (auth()->user()->rol === 'ciudadano')
    <p>
        <a href="{{ route('reclamos.crear') }}">Registrar un reclamo</a>
    </p>
@endif


    <form method="POST" action="/logout">
        <!-- Crea un formulario para cerrar sesión.
             method="POST" envía una petición POST al servidor.
             action="/logout" indica la ruta que recibirá la petición. -->

        @csrf
        <!-- Genera el token de seguridad CSRF que Laravel espera
             para aceptar esta solicitud POST. -->

        <button type="submit">Cerrar sesión</button>
        <!-- Crea el botón que envía el formulario al pulsarlo.
             No cierra la sesión por sí solo: envía la petición a Laravel,
             que ejecuta AuthController::logout(). -->

    </form>
    <!-- Termina el formulario de cierre de sesión. -->

</body>
<!-- Termina el contenido visible de la página. -->

</html>
<!-- Cierra el documento HTML. -->
