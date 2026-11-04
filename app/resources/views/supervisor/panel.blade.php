<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel del supervisor | SIRCI</title>

    {{-- Estilos generales y diseño visual del supervisor --}}
    <link rel="stylesheet" href="{{ asset('css/sirci.css') }}">
    <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}">
</head>
<body>

    {{-- Encabezado principal del sistema --}}
    <header class="encabezado">
        <div>
            <strong>SIRCI</strong>
            <span>Sistema Integral de Reportes Ciudadanos</span>
        </div>

        {{-- Conservamos el cierre de sesión existente --}}
        <form action="{{ url('/logout') }}" method="POST">
            @csrf

            <button type="submit" class="boton-salir">
                Cerrar sesión
            </button>
        </form>
    </header>

    <main class="contenedor">

        <section class="tarjeta">

            <h1>Panel de supervisión</h1>

            <p>
                Bienvenido, <strong>{{ auth()->user()->nombre }}</strong>.
            </p>

            <p>
                Desde este espacio podés consultar y supervisar
                los reclamos registrados en SIRCI.
            </p>

            <h2 class="titulo-seccion">Módulos disponibles</h2>

            <div class="accesos-panel">
                {{-- Acceso a nuestro módulo de agrupamiento geográfico --}}
                <a href="{{ route('supervisor.ubicaciones') }}"
                   class="acceso-panel">

                    <span class="acceso-panel-titulo">
                        <span aria-hidden="true">📍</span> Ubicaciones de reclamos
                    </span>

                    <span class="acceso-panel-descripcion">
                        Consultar reclamos pendientes agrupados por
                        proximidad geográfica y categoría.
                        Acceder al detalle de cada ubicación.
                    </span>
                    {{-- Acceso al tablero estadístico municipal --}}
<a href="{{ route('indicadores') }}" class="acceso-panel">

    <span class="acceso-panel-titulo">
        <span aria-hidden="true">📊</span> Tablero de indicadores
    </span>

    <span class="acceso-panel-descripcion">
        Consultar estadísticas de reclamos, estados,
        categorías, tiempos de resolución y actividad reciente.
    </span>

    <span class="acceso-panel-enlace">
        Ver indicadores →
    </span>

</a>

                    <span class="acceso-panel-enlace">
                        Ver ubicaciones →
                    </span>

                </a>

            </div>

        </section>

    </main>

</body>
</html>