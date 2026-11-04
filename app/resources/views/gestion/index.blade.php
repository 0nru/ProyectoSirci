@extends('layouts.app')

@section('titulo', 'Gestión de reclamos')

@push('estilos')
    {{-- Estilos locales para conservar la funcionalidad y presentación de Gestión. --}}
    <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gestion.css') }}">
@endpush

@section('contenido')
    <div class="contenedor">
        <section class="tarjeta">
            <div class="gestion-cabecera">
                <div>
                    <h1>Gestión de reclamos</h1>
                    <p>Consultá y administrá los reclamos recibidos por el municipio.</p>
                </div>
                <span class="gestion-total">{{ $reclamos->total() }} {{ $reclamos->total() === 1 ? 'reclamo' : 'reclamos' }}</span>
            </div>

            {{-- El formulario GET permite combinar filtros sin modificar datos. --}}
            <form class="gestion-filtros" method="GET" action="{{ route('gestion.index') }}">
                <div class="gestion-campo gestion-campo--buscar">
                    <label for="q">Buscar</label>
                    <input type="search" id="q" name="q" value="{{ $filtros['q'] }}"
                           placeholder="Número, dirección o descripción">
                </div>

                <div class="gestion-campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado }}" @selected($filtros['estado'] === $estado)>
                                {{ ucfirst($estado) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="gestion-campo">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected((string) $filtros['categoria'] === (string) $categoria->id)>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="gestion-campo">
                    <label for="area">Área responsable</label>
                    <select id="area" name="area">
                        <option value="">Todas las áreas</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected((string) $filtros['area'] === (string) $area->id)>
                                {{ $area->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="gestion-acciones-filtro">
                    <button class="gestion-boton gestion-boton--primario" type="submit">Aplicar filtros</button>
                    <a class="gestion-boton gestion-boton--secundario" href="{{ route('gestion.index') }}">Limpiar</a>
                </div>
            </form>

            @if ($reclamos->isEmpty())
                <div class="aviso" role="status">
                    No hay reclamos que coincidan con los filtros seleccionados.
                </div>
            @else
                <div class="gestion-tabla-contenedor">
                    <table class="gestion-tabla">
                        <thead>
                            <tr>
                                <th>N.º</th>
                                <th>Reclamo</th>
                                <th>Dirección o referencia</th>
                                <th>Estado</th>
                                <th>Área</th>
                                <th>Registrado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reclamos as $reclamo)
                                <tr>
                                    <td data-label="N.º">#{{ $reclamo->id }}</td>
                                    <td data-label="Reclamo">
                                        <strong>{{ $reclamo->categoria }}</strong>
                                        <span class="gestion-descripcion">
                                            {{ \Illuminate\Support\Str::limit($reclamo->descripcion ?: 'Sin descripción', 85) }}
                                        </span>
                                    </td>
                                    <td data-label="Dirección o referencia">{{ $reclamo->barrio ?: 'No indicada' }}</td>
                                    <td data-label="Estado">
                                        <span class="gestion-estado gestion-estado--{{ \Illuminate\Support\Str::slug($reclamo->estado) }}">
                                            {{ ucfirst($reclamo->estado) }}
                                        </span>
                                    </td>
                                    <td data-label="Área">{{ $reclamo->area }}</td>
                                    <td data-label="Registrado">{{ \App\Support\Fecha::conHora($reclamo->fecha_creacion) }}</td>
                                    <td data-label="Acción">
                                        <a class="gestion-enlace" href="{{ route('gestion.ver', ['reporte' => $reclamo->id]) }}">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Paginación manual: conserva los filtros aplicados. --}}
                @if ($reclamos->hasPages())
                    <nav class="gestion-paginacion" aria-label="Páginas de reclamos">
                        <span>Página {{ $reclamos->currentPage() }} de {{ $reclamos->lastPage() }}</span>
                        <div>
                            @if ($reclamos->previousPageUrl())
                                <a class="gestion-boton gestion-boton--secundario" href="{{ $reclamos->previousPageUrl() }}">← Anterior</a>
                            @endif
                            @if ($reclamos->nextPageUrl())
                                <a class="gestion-boton gestion-boton--secundario" href="{{ $reclamos->nextPageUrl() }}">Siguiente →</a>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </section>
    </div>
@endsection
