
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubicaciones | SIRCI</title>

    {{-- Reutiliza los estilos generales del sistema --}}
    <link rel="stylesheet" href="{{ asset('css/sirci.css') }}">

    <style>
        .tabla-ubicaciones {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-ubicaciones th,
        .tabla-ubicaciones td {
            padding: 12px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        .resumen-estados {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .estado-resumen {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            font-size: 0.9rem;
        }

        @media (max-width: 640px) {
            .tabla-ubicaciones thead {
                display: none;
            }

            .tabla-ubicaciones tr {
                display: block;
                margin-bottom: 14px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
            }

            .tabla-ubicaciones td {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                border-bottom: 0;
            }

            .tabla-ubicaciones td::before {
                content: attr(data-label);
                min-width: 120px;
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

            <h1>Ubicaciones de reclamos</h1>
            @if (session('aviso'))
    <div class="aviso">
        <p>{{ session('aviso') }}</p>
    </div>
@endif

            <p>
                Reclamos pendientes agrupados por proximidad geográfica
                y categoría.
            </p>

            <p>
                <strong>Radio de agrupamiento:</strong>
                {{ $radioMetros }} metros
            </p>

            @if ($grupos->isEmpty())

                <div class="aviso">
                    <p>No hay reclamos pendientes para mostrar.</p>
                </div>

            @else

                <p>
                    <strong>Grupos encontrados:</strong>
                    {{ $grupos->count() }}
                </p>

                <table class="tabla-ubicaciones">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Dirección o referencia</th>
                            <th>Reclamos</th>
                            <th>Fecha más reciente</th>
                            <th>Estados</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($grupos as $grupo)

                            @php
                                $referencia = $grupo['referencia'];
                            @endphp

                            <tr>
                                <td data-label="Categoría">
                                    {{ $referencia->categoria?->nombre ?? 'Sin categoría' }}
                                </td>

                                <td data-label="Dirección o referencia">
                                    @if ($referencia->barrio)
                                        {{ $referencia->barrio }}
                                    @else
                                        Latitud: {{ $referencia->latitud }}<br>
                                        Longitud: {{ $referencia->longitud }}
                                    @endif
                                </td>

                                <td data-label="Reclamos">
                                    {{ $grupo['reclamos']->count() }}
                                </td>

                                <td data-label="Fecha más reciente">
                                    {{ $grupo['fecha_mas_reciente']
                                        ? \Illuminate\Support\Carbon::parse($grupo['fecha_mas_reciente'])->format('d/m/Y H:i')
                                        : '—' }}
                                </td>

                                <td data-label="Estados">
                                    <div class="resumen-estados">
                                        @foreach ($grupo['resumen_estados'] as $estado => $cantidad)
                                            <span class="estado-resumen">
                                                {{ ucfirst($estado) }}: {{ $cantidad }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                    </div>

                                {{-- Enlace al detalle de los reclamos de este grupo --}}
                                <td data-label="Detalle">
                                    <a href="{{ route('supervisor.ubicaciones.show', $referencia->id) }}">
                                        Ver reclamos
                                    </a>
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
