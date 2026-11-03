<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Supervisor | SIRCI</title>
</head>
<body>

    <h1>Panel de Supervisión SIRCI</h1>

    <p>Bienvenido, {{ auth()->user()->nombre }}.</p>

    <p>El panel administrativo está en construcción.</p>

    <form action="{{ url('/logout') }}" method="POST">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>

</body>
</html>