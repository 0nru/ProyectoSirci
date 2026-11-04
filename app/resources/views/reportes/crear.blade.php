@extends('layouts.app')

@section('titulo', 'Nuevo reclamo')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <style>
        /* El JS existente busca #mapa; conservamos ese identificador.
           El CSS original del equipo usaba #mapa-reclamo. */
        #mapa {
            width: 100%;
            height: 360px;
            border-radius: var(--radio, 12px);
            border: 1px solid var(--border, #d1d5db);
            z-index: 0;
        }
        #ubicacion-aviso[hidden] { display: none !important; }
    </style>
@endpush

@section('contenido')
    <div class="page-head">
        <div>
            <a href="/panel" class="small">&larr; Volver al inicio</a>
            <h1 style="margin-top:6px">Nuevo reclamo</h1>
            <p class="page-head__sub">Completá los datos y marcá en el mapa dónde ocurre el problema.</p>
        </div>
    </div>

    {{-- Envía los datos al método guardar del controlador. --}}
    <form action="{{ route('reclamos.guardar') }}" method="POST" id="form-reclamo" novalidate>
        {{-- Token de seguridad obligatorio en formularios POST de Laravel. --}}
        @csrf

        <div class="grid-detalle">
            <div class="stack">
                <div class="card">
                    <div class="campo">
                        <label for="categoria_id">Tipo de problema</label>

                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">Seleccioná una categoría</option>

                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>

                        @error('categoria_id')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="campo">
                        <label for="descripcion">Descripción del problema</label>

                        <textarea id="descripcion" name="descripcion" rows="5" minlength="10" maxlength="500"
                                  required placeholder="Contá qué sucede y dónde se observa el problema.">{{ old('descripcion') }}</textarea>

                        <small>Entre 10 y 500 caracteres.</small>

                        @error('descripcion')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="campo" style="margin-bottom:0">
                        <label for="direccion">Dirección o referencia del lugar</label>

                        <input type="text" id="direccion" name="direccion" maxlength="80"
                               value="{{ old('direccion') }}"
                               placeholder="Ej.: San Miguel, Av. Uruguay y Belgrano">

                        <small>Escribí una dirección o referencia que ayude a ubicar el problema. Evitá incluir datos personales.</small>

                        @error('direccion')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="aviso" style="margin-bottom:0">
                    <strong>Fotos</strong>
                    <p>La carga de fotografías estará disponible próximamente.</p>
                </div>
            </div>

            <div class="stack">
                <div class="card">
                    <div class="campo" style="margin-bottom:0">
                        <label>Ubicación en el mapa</label>
                        <p class="muted small">Hacé clic sobre el lugar exacto del problema para colocar el marcador. Podés arrastrarlo para ajustarlo.</p>

                        {{-- El mapa se inicializa mediante JavaScript (public/js/reporte-mapa.js). --}}
                        <div id="mapa"
                             data-lat="-26.1849"
                             data-lng="-58.1731"
                             data-zoom="13"></div>

                        {{-- Estos campos ocultos reciben las coordenadas del mapa. --}}
                        <input type="hidden" id="latitud" name="latitud" value="{{ old('latitud') }}">
                        <input type="hidden" id="longitud" name="longitud" value="{{ old('longitud') }}">

                        <p id="ubicacion-texto" class="muted small" role="status">
                            Todavía no seleccionaste una ubicación.
                        </p>

                        {{-- Este aviso lo muestra el JavaScript si intentan enviar sin marcar el mapa. --}}
                        <p id="ubicacion-aviso" class="error" role="alert" hidden>
                            Marcá en el mapa el lugar del problema.
                        </p>

                        @error('latitud')
                            <p class="error">{{ $message }}</p>
                        @enderror

                        @error('longitud')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn--primary btn--block">Registrar reclamo</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('js/reporte-mapa.js') }}"></script>
@endpush
