@extends('layouts.app')

@section('titulo', 'Detalle de reclamo')

@push('estilos')
    {{-- Estilos locales para conservar la funcionalidad y presentación de Gestión. --}}
    <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gestion.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('contenido')
    <div class="contenedor">
        <section class="tarjeta">
            <div class="gestion-cabecera">
                <div>
                    <a href="{{ route('gestion.index') }}">← Volver a los reclamos</a>
                    <h1>Reclamo #{{ $reclamo->id }} — {{ $reclamo->categoria }}</h1>
                    <p>{{ $reclamo->barrio ?: 'Sin dirección indicada' }} ·
                        {{ \App\Support\Fecha::conHora($reclamo->fecha_creacion) }}</p>
                </div>
                <span class="gestion-estado gestion-estado--{{ \Illuminate\Support\Str::slug($reclamo->estado) }}">
                    {{ ucfirst($reclamo->estado) }}
                </span>
            </div>

            @if ($errors->any())
                <div class="gestion-error" role="alert">
                    No se pudo guardar el cambio. Revisá los datos del formulario.
                </div>
            @endif

            <div class="gestion-columnas">
                <div class="gestion-pila">
                    <section class="gestion-seccion">
                        <h2>Descripción del reclamo</h2>
                        <p>{{ $reclamo->descripcion ?: 'Sin descripción.' }}</p>
                        <dl class="gestion-datos">
                            <div><dt>Categoría</dt><dd>{{ $reclamo->categoria }}</dd></div>
                            <div><dt>Área actual</dt><dd>{{ $reclamo->area }}</dd></div>
                            <div><dt>Dirección o referencia</dt><dd>{{ $reclamo->barrio ?: 'No indicada' }}</dd></div>
                            <div><dt>Registrado</dt><dd>{{ \App\Support\Fecha::conHora($reclamo->fecha_creacion) }}</dd></div>
                            @if ($reclamo->fecha_resolucion)
                                <div><dt>Resuelto</dt><dd>{{ \App\Support\Fecha::conHora($reclamo->fecha_resolucion) }}</dd></div>
                            @endif
                        </dl>
                    </section>

                    <section class="gestion-seccion">
                        <h2>Ubicación</h2>
                        <div id="mapa-gestion" class="gestion-mapa"
                             data-lat="{{ $reclamo->latitud }}"
                             data-lng="{{ $reclamo->longitud }}"
                             role="img" aria-label="Ubicación geográfica del reclamo"></div>
                        <p class="gestion-aclaracion">
                            Coordenadas: {{ $reclamo->latitud }}, {{ $reclamo->longitud }} ·
                            <a href="https://www.openstreetmap.org/?mlat={{ $reclamo->latitud }}&amp;mlon={{ $reclamo->longitud }}#map=18/{{ $reclamo->latitud }}/{{ $reclamo->longitud }}"
                               target="_blank" rel="noopener noreferrer">Abrir en OpenStreetMap</a>
                        </p>
                    </section>

                    <section class="gestion-seccion">
                        <h2>Datos del ciudadano</h2>
                        <dl class="gestion-datos">
                            <div><dt>Nombre</dt><dd>{{ $ciudadano?->nombre ?? 'No disponible' }}</dd></div>
                            <div><dt>Correo</dt><dd>{{ $ciudadano?->email ?? 'No disponible' }}</dd></div>
                            <div><dt>Teléfono</dt><dd>{{ $ciudadano?->telefono ?: 'No informado' }}</dd></div>
                        </dl>
                        <p class="gestion-aclaracion">Información destinada únicamente al personal municipal autorizado.</p>
                    </section>

                    <section class="gestion-seccion">
                        <h2>Fotografías</h2>
                        <p class="gestion-aclaracion">La carga de fotografías todavía no está disponible en el backend.</p>
                    </section>
                </div>

                <div class="gestion-pila">
                    <section class="gestion-seccion">
                        <h2>Actualizar estado</h2>
                        @if (count($transiciones) === 0)
                            <p>No hay transiciones disponibles para tu rol en el estado actual.</p>
                        @else
                            <form method="POST" action="{{ route('gestion.estado', ['reporte' => $reclamo->id]) }}" class="gestion-formulario">
                                @csrf
                                <div class="gestion-campo">
                                    <label for="estado">Nuevo estado</label>
                                    <select id="estado" name="estado" required>
                                        @foreach ($transiciones as $transicion)
                                            <option value="{{ $transicion }}" @selected(old('estado') === $transicion)>
                                                {{ ucfirst($transicion) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('estado') <small class="gestion-texto-error">{{ $message }}</small> @enderror
                                </div>
                                <div class="gestion-campo">
                                    <label for="observacion">Observación (opcional)</label>
                                    <textarea id="observacion" name="observacion" rows="4" maxlength="500"
                                        placeholder="Ej.: Se asignó una cuadrilla para reparar el problema.">{{ old('observacion') }}</textarea>
                                    @error('observacion') <small class="gestion-texto-error">{{ $message }}</small> @enderror
                                </div>
                                <button type="submit" class="gestion-boton gestion-boton--primario">Guardar nuevo estado</button>
                                <p class="gestion-aclaracion">El cambio registra un movimiento en el historial y genera una notificación interna para el ciudadano.</p>
                            </form>
                        @endif
                    </section>

                    <section class="gestion-seccion">
                        <h2>Área responsable</h2>
                        <form method="POST" action="{{ route('gestion.area', ['reporte' => $reclamo->id]) }}" class="gestion-formulario">
                            @csrf
                            <div class="gestion-campo">
                                <label for="area_id">Asignar área</label>
                                <select id="area_id" name="area_id" required>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area->id }}" @selected((int) old('area_id', $reclamo->area_id) === (int) $area->id)>
                                            {{ $area->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('area_id') <small class="gestion-texto-error">{{ $message }}</small> @enderror
                            </div>
                            <button type="submit" class="gestion-boton gestion-boton--secundario">Guardar área</button>
                        </form>
                    </section>

                    <section class="gestion-seccion">
                        <h2>Historial del reclamo</h2>
                        @if ($historial->isEmpty())
                            <p class="gestion-aclaracion">Todavía no hay movimientos registrados.</p>
                        @else
                            <ol class="gestion-historial">
                                @foreach ($historial as $movimiento)
                                    <li>
                                        <div class="gestion-historial-cabecera">
                                            <span class="gestion-estado gestion-estado--{{ \Illuminate\Support\Str::slug($movimiento->estado) }}">
                                                {{ ucfirst($movimiento->estado) }}
                                            </span>
                                            <time>{{ \App\Support\Fecha::conHora($movimiento->fecha_cambio) }}</time>
                                        </div>
                                        <p class="gestion-aclaracion">{{ $movimiento->usuario }} · {{ $movimiento->area ?: 'Sin área' }}</p>
                                        @if ($movimiento->observacion)
                                            <p>{{ $movimiento->observacion }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    {{-- El mapa sigue usando los recursos locales de Leaflet. --}}
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}" defer></script>
    <script src="{{ asset('js/gestion-mapa.js') }}" defer></script>
@endpush
