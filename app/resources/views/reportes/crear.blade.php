
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo reclamo | SIRCI</title>

    {{-- Carga los estilos que crearemos en sirci.css --}}
    <link rel="stylesheet" href="{{ asset('css/sirci.css') }}">
    <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>
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
            <h1>Nuevo reclamo</h1>
            <p>Completá los datos y marcá en el mapa dónde ocurre el problema.</p>

            {{-- Envía los datos al método guardar del controlador --}}
            <form action="{{ route('reclamos.guardar') }}" method="POST" id="form-reclamo">
                {{-- Token de seguridad obligatorio en formularios POST de Laravel --}}
                @csrf

                <div class="campo">
                    <label for="categoria_id">Tipo de problema</label>

                    <select id="categoria_id" name="categoria_id" required>
                        <option value="">Seleccioná una categoría</option>

                        @foreach ($categorias as $categoria)
                            <option
                                value="{{ $categoria->id }}"
                                @selected(old('categoria_id') == $categoria->id)
                            >
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

                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="5"
                        minlength="10"
                        maxlength="500"
                        required
                        placeholder="Contá qué sucede y dónde se observa el problema."
                    >{{ old('descripcion') }}</textarea>

                    <small>Entre 10 y 500 caracteres.</small>

                    @error('descripcion')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="campo">
                    <label for="direccion">Dirección o referencia del lugar</label>

                    <input
                        type="text"
                        id="direccion"
                        name="direccion"
                        maxlength="80"
                        value="{{ old('direccion') }}"
                        placeholder="Ej.: calle, esquina, manzana o referencia"
                    >

                    <small>Ingresá una referencia que permita localizar el problema.</small>

                    @error('direccion')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="campo">
                    <label>Ubicación en el mapa</label>
                    <p>Hacé clic sobre el lugar exacto del problema para colocar el marcador.</p>

                    {{-- El mapa se inicializará mediante JavaScript --}}
                    <div
                        id="mapa-reclamo"
                        data-lat="-26.1849"
                        data-lng="-58.1731"
                        data-zoom="13"
                    ></div>

                    {{-- Estos campos ocultos recibirán las coordenadas del mapa --}}
                    <input
                        type="hidden"
                        id="latitud"
                        name="latitud"
                        value="{{ old('latitud') }}"
                    >

                    <input
                        type="hidden"
                        id="longitud"
                        name="longitud"
                        value="{{ old('longitud') }}"
                    >

                    <p id="mensaje-mapa" role="status">
                        Todavía no seleccionaste una ubicación.
                    </p>

                    @error('latitud')
                        <p class="error">{{ $message }}</p>
                    @enderror

                    @error('longitud')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="aviso">
                    <strong>Fotos</strong>
                    <p>La carga de fotografías estará disponible próximamente.</p>
                </div>

                <button type="submit">Registrar reclamo</button>
            </form>
        </section>
    </main>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/reporte-mapa.js') }}"></script>
</body>
</html>
