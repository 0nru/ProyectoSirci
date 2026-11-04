<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'SIRCI') · SIRCI</title>

    <link rel="stylesheet" href="{{ asset('css/frontend-original.css') }}">
</head>
<body>
    <main class="auth">
        <div class="auth__box">
            <div class="auth__logo">
                <span class="brand__mark">@include('partials.icon', ['n' => 'pin', 't' => 26])</span>
                <h1>SIRCI</h1>
                <p>Reportá y seguí problemas urbanos de tu ciudad</p>
            </div>

            @yield('contenido')
        </div>
    </main>
</body>
</html>
