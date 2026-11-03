<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis reclamos | SIRCI</title>

    {{-- Estilos generales del sistema --}}
    <link rel="stylesheet" href="{{ asset('css/sirci.css') }}">

    {{-- Estilos mínimos de esta pantalla (tabla adaptable a celulares).
         Si el equipo de diseño prefiere, se pueden mover a sirci.css. --}}
    <style>
        .tabla-reclamos {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-reclamos th,
        .tabla-reclamos td {
            padding: 10px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        /* En pantallas angostas cada reclamo se muestra como una tarjeta apilada */
        @media (max-width: 640px) {
            .tabla-reclamos thead {
                display: none;
            }

            .tabla-reclamos tr {
                display: block;
                margin-bottom: 14px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
            }

            .tabla-reclamos td {
                display: flex;
                gap: 12px;
                border-bottom: 0;
            }

            /* Muestra el nombre de la columna (data-label) antes de cada dato */
            .tabla-reclamos td::before {
                content: attr(data-label);
                min-width: 90px;
                font-weight: 600;
            }
        }
    </style>
</head>
<body>
    <header class="encabezado">
        <div>
            <strong>SIRCI</strong>
            <span>Sistema Integral de Reportes Ciudadanos</span>
        </div>

        <a href="{{ url('/panel') }}">Volver al panel</a>
    </header>

    <main class="contenedor">
        <section class="tarjeta">
            <h1>Mis reclamos</h1>

            {{-- Si no hay reclamos, se muestra un mensaje y un enlace para registrar uno --}}
            @if ($reclamos->isEmpty())
                <div class="aviso">
                    <p>Todavía no registraste ningún reclamo.</p>
                    <a href="{{ route('reclamos.crear') }}">Registrar un reclamo</a>
                </div>
            @else
                <table class="tabla-reclamos">
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($reclamos as $reclamo)
                            <tr>
                                <td data-label="N.º">{{ $reclamo->id }}</td>

                                {{-- ?-> evita errores si faltara la categoría o el estado --}}
                                <td data-label="Categoría">{{ $reclamo->categoria?->nombre ?? '—' }}</td>
                                <td data-label="Estado">{{ $reclamo->estado?->nombre ?? '—' }}</td>

                                {{-- La descripción puede estar vacía en la base --}}
                                <td data-label="Descripción">{{ $reclamo->descripcion ?? '—' }}</td>

                                {{-- Si la fecha es nula se muestra un guion; si no, se formatea.
                                     Carbon::parse funciona tanto si la fecha llega como texto
                                     como si ya es un objeto de fecha. --}}
                                <td data-label="Fecha">
                                    {{ $reclamo->fecha_creacion
                                        ? \Illuminate\Support\Carbon::parse($reclamo->fecha_creacion)->format('d/m/Y H:i')
                                        : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>
</body>
</html>