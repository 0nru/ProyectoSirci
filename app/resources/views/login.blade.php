
<!DOCTYPE html>
<!-- Indica al navegador que el documento utiliza HTML5. -->

<html lang="es">
<!-- Abre el documento HTML e indica que el idioma principal es español. -->

<head>
    <!-- Aquí se configura información de la página que no aparece
         directamente como contenido principal. -->

    <meta charset="UTF-8">
    <!-- Permite mostrar correctamente tildes, eñes y otros caracteres. -->

    <title>SIRCI · Ingresar</title>
    <!-- Es el título que aparece en la pestaña del navegador. -->

    <style>
        /* A partir de aquí definimos los estilos visuales con CSS. */

        body {
            font-family: sans-serif;
            /* Usa una tipografía sencilla disponible en el sistema. */

            background: #f3f4f6;
            /* Establece un fondo gris claro para toda la página. */

            display: flex;
            /* Activa Flexbox, un sistema para organizar elementos. */

            justify-content: center;
            /* Centra horizontalmente el contenido dentro del body. */

            padding-top: 80px;
            /* Deja espacio arriba para que el formulario no quede pegado
               al borde superior de la ventana. */
        }

        form {
            background: #fff;
            /* Pone el fondo del formulario en blanco. */

            padding: 30px;
            /* Agrega espacio interior entre el contenido y los bordes. */

            border-radius: 8px;
            /* Redondea ligeramente las esquinas del formulario. */

            width: 320px;
            /* Define el ancho del formulario. */

            box-shadow: 0 2px 8px rgba(0,0,0,.1);
            /* Agrega una sombra suave para separarlo visualmente del fondo. */
        }

        input {
            width: 100%;
            /* Hace que cada campo ocupe todo el ancho disponible. */

            padding: 8px;
            /* Agrega espacio dentro del campo para facilitar la escritura. */

            margin: 6px 0 14px;
            /* Define los márgenes: 6 px arriba, 0 a los lados y 14 px abajo. */

            box-sizing: border-box;
            /* Incluye el padding y los bordes dentro del ancho total.
               Evita que el campo sobresalga del formulario. */
        }

        button {
            width: 100%;
            /* Hace que el botón ocupe todo el ancho disponible. */

            padding: 10px;
            /* Aumenta el espacio interior para que sea más cómodo pulsarlo. */

            cursor: pointer;
            /* Muestra el cursor de mano al pasar sobre el botón. */
        }

        .error {
            color: #b91c1c;
            /* Muestra los mensajes de error en rojo oscuro. */

            margin-bottom: 10px;
            /* Deja espacio debajo del mensaje de error. */
        }
    </style>
    <!-- Terminan las reglas CSS. -->

</head>
<!-- Termina la configuración de la página. -->

<body>
    <!-- Aquí comienza el contenido visible del documento. -->

    <form method="POST" action="/login">
        <!-- Crea el formulario de acceso.
             method="POST" indica que enviará datos al servidor.
             action="/login" indica la dirección que recibirá esos datos. -->

        @csrf
        <!-- Directiva de Blade que genera un token de seguridad CSRF.
             Laravel lo utiliza para comprobar que la solicitud procede
             de un formulario válido y no de una petición falsificada. -->

        <h2>SIRCI</h2>
        <!-- Muestra el encabezado del formulario. -->

        @error('email')
            <!-- Blade comprueba si existe un error de validación asociado
                 al campo email. Si existe, muestra el contenido siguiente. -->

            <div class="error">{{ $message }}</div>
            <!-- $message contiene el mensaje de error.
                 Las llaves dobles hacen que Blade lo muestre escapando HTML
                 para reducir el riesgo de inyección de contenido. -->

        @enderror
        <!-- Termina el bloque condicional del error. -->

        <label>Email</label>
        <!-- Texto que identifica el campo del correo electrónico. -->

        <input type="email" name="email"
               value="{{ old('email') }}" required>
        <!-- type="email": el navegador comprueba el formato del correo.
             name="email": es la clave con la que se envía el dato a Laravel.
             old('email'): recupera el email anterior si hubo un error.
             required: exige que el campo no esté vacío antes de enviar. -->

        <label>Contraseña</label>
        <!-- Texto que identifica el campo de la contraseña. -->

        <input type="password" name="password" required>
        <!-- type="password": oculta visualmente los caracteres escritos.
             name="password": permite que Laravel reciba la contraseña
             bajo ese nombre.
             required: impide enviar el formulario vacío desde el navegador.
             La contraseña no se conserva con old(). -->

        <button type="submit">Ingresar</button>
        <!-- Envía el formulario al servidor cuando se pulsa el botón. -->

    </form>
    <!-- Termina el formulario de acceso. -->

</body>
<!-- Termina el contenido visible de la página. -->

</html>
<!-- Cierra el documento HTML. -->
