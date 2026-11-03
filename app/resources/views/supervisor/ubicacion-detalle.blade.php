<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de ubicación | SIRCI</title>

    {{-- Reutiliza los estilos generales del sistema --}}
    <link rel="stylesheet" href="{{ asset('css/sirci.css') }}">

    <style>
        .tabla-detalle {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-detalle th,
        .tabla-detalle td {
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
            .tabla-detalle thead {
                display: none;
            }

            .tabla-detalle tr {
                display: block;
                margin-bottom: 14px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
            }

            .tabla-detalle td {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                border-bottom: 0;
            }

            .tabla-detalle td::before {
                content: attr(data-label);
                min-width: 120px;
                font-weight: 600;
            }
        }
    </style>

    {{-- Estilos visuales compartidos por las pantallas del supervisor --}}
    <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}">
</head>
<body>

    <header class="encabezado">
        <div>
            <strong>SIRCI</strong>
            <span>Sistema Integral de Reportes Ciudadanos</span>
        </div>

        <a href="{{ route('supervisor.ubicaciones') }}">Volver a las ubicaciones</a>
    </header>

    <main class="contenedor">
        <section class="tarjeta">

            @php
                // Reclamo que funciona como punto de referencia fijo del grupo.
                $referencia = $grupo['referencia'];
            @endphp

            <h1>Detalle de la ubicación</h1>

            <p>
                <strong>Categoría:</strong>
                {{ $referencia->categoria?->nombre ?? 'Sin categoría' }}
            </p>

            <p>
                <strong>Dirección o referencia:</strong>
                @if ($referencia->barrio)
                    {{ $referencia->barrio }}
                @else
                    Latitud: {{ $referencia->latitud }}, Longitud: {{ $referencia->longitud }}
                @endif
            </p>

            <p>
                <strong>Reclamos en esta ubicación:</strong>
                {{ $grupo['reclamos']->count() }}
            </p>

            <p>
                <strong>Fecha del reclamo más reciente:</strong>
                {{ $grupo['fecha_mas_reciente']
                    ? \Illuminate\Support\Carbon::parse($grupo['fecha_mas_reciente'])->format('d/m/Y H:i')
                    : '—' }}
            </p>

            <div class="resumen-estados">
                @foreach ($grupo['resumen_estados'] as $estado => $cantidad)
                    <span class="estado-resumen">
                        {{ ucfirst($estado) }}: {{ $cantidad }}
                    </span>
                @endforeach
            </div>

            <p>
                <strong>Radio de agrupamiento:</strong>
                {{ $radioMetros }} metros
            </p>

            <table class="tabla-detalle">
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Fecha de creación</th>
                        <th>Dirección o referencia</th>
                        <th>Coordenadas</th>
                        <th>Distancia a la referencia</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- La referencia aparece primero y luego los demás por fecha ascendente
                         (es el orden en que los devuelve el servicio de agrupamiento). --}}
                    @foreach ($grupo['reclamos'] as $reclamo)
                        <tr>
                            <td data-label="N.º">
                                {{ $reclamo->id }}
                                @if ($reclamo->is($referencia))
                                    <strong>(referencia)</strong>
                                @endif
                            </td>

                            <td data-label="Descripción">{{ $reclamo->descripcion ?? '—' }}</td>

                            <td data-label="Estado">{{ ucfirst($reclamo->estado?->nombre ?? 'sin estado') }}</td>

                            <td data-label="Fecha de creación">
                                {{ $reclamo->fecha_creacion?->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            <td data-label="Dirección o referencia">{{ $reclamo->barrio ?? '—' }}</td>

                            <td data-label="Coordenadas">
                                {{ $reclamo->latitud }}, {{ $reclamo->longitud }}
                            </td>

                            <td data-label="Distancia a la referencia">
                                @if ($reclamo->is($referencia))
                                    Referencia
                                @else
                                    {{ number_format($distancias->get($reclamo->id, 0), 1, ',', '.') }} m
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </section>
    </main>

</body>
</html>