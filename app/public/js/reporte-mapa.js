document.addEventListener('DOMContentLoaded', function () {
    // Busca los elementos del formulario que vamos a utilizar.
    const mapaElemento = document.getElementById('mapa-reclamo');
    const campoLatitud = document.getElementById('latitud');
    const campoLongitud = document.getElementById('longitud');
    const mensajeMapa = document.getElementById('mensaje-mapa');
    const formulario = document.getElementById('form-reclamo');

    // Si esta página no contiene el mapa, no hacemos nada.
    if (!mapaElemento) {
        return;
    }

    // Comprueba que Leaflet se haya cargado correctamente.
    if (typeof L === 'undefined') {
        mensajeMapa.textContent = 'No se pudo cargar el mapa. Recargá la página e intentá nuevamente.';
        return;
    }

    // Obtiene el centro inicial y el zoom desde los atributos HTML.
    const latInicial = Number(mapaElemento.dataset.lat);
    const lngInicial = Number(mapaElemento.dataset.lng);
    const zoomInicial = Number(mapaElemento.dataset.zoom);

    // Crea el mapa centrado inicialmente en Formosa.
    const mapa = L.map('mapa-reclamo').setView(
        [latInicial, lngInicial],
        zoomInicial
    );

    // Agrega las imágenes cartográficas de OpenStreetMap.
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(mapa);

    // Al principio no hay ningún marcador seleccionado.
    let marcador = null;

    // Escribe las coordenadas en los campos que enviará el formulario.
    function guardarCoordenadas(latitud, longitud) {
        campoLatitud.value = latitud.toFixed(6);
        campoLongitud.value = longitud.toFixed(6);

        mensajeMapa.textContent =
            'Ubicación seleccionada: ' +
            campoLatitud.value + ', ' +
            campoLongitud.value;
    }

    // Crea o mueve el marcador hasta la ubicación elegida.
    function seleccionarUbicacion(latitud, longitud) {
        if (marcador === null) {
            // Crea un marcador que el usuario también puede arrastrar.
            marcador = L.marker([latitud, longitud], {
                draggable: true
            }).addTo(mapa);

            // Actualiza los campos si el marcador se mueve.
            marcador.on('dragend', function () {
                const posicion = marcador.getLatLng();

                guardarCoordenadas(posicion.lat, posicion.lng);
            });
        } else {
            // Si ya hay un marcador, lo reutiliza en vez de crear otro.
            marcador.setLatLng([latitud, longitud]);
        }

        guardarCoordenadas(latitud, longitud);
    }

    // Cada clic sobre el mapa selecciona una nueva ubicación.
    mapa.on('click', function (evento) {
        seleccionarUbicacion(
            evento.latlng.lat,
            evento.latlng.lng
        );
    });

    // Si Laravel devuelve coordenadas anteriores por un error de validación,
    // restaura el marcador para que el ciudadano no tenga que elegir otra vez.
    const latAnterior = Number(campoLatitud.value);
    const lngAnterior = Number(campoLongitud.value);

    if (
        campoLatitud.value !== '' &&
        campoLongitud.value !== '' &&
        Number.isFinite(latAnterior) &&
        Number.isFinite(lngAnterior) &&
        latAnterior >= -90 && latAnterior <= 90 &&
        lngAnterior >= -180 && lngAnterior <= 180
    ) {
        seleccionarUbicacion(latAnterior, lngAnterior);
        mapa.setView([latAnterior, lngAnterior], zoomInicial);
    }

    // Evita enviar el formulario si no se seleccionó una ubicación.
    formulario.addEventListener('submit', function (evento) {
        if (campoLatitud.value === '' || campoLongitud.value === '') {
            evento.preventDefault();

            mensajeMapa.textContent =
                'Primero marcá en el mapa dónde ocurre el problema.';

            mapaElemento.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    });
});
