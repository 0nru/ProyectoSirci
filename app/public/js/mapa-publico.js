// Mapa público de reclamos (RF-08).
// Carga los reclamos desde /mapa/datos, los dibuja como marcadores de color
// según su estado y permite filtrarlos sin recargar la página.
(function () {
    var contenedor = document.getElementById('mapa-publico');

    if (!contenedor) {
        return;
    }

    if (typeof L === 'undefined') {
        document.getElementById('mapa-contador').textContent =
            'No se pudo cargar el mapa. Recargá la página e intentá nuevamente.';
        return;
    }

    var urlDatos = contenedor.dataset.url;
    var lista = document.getElementById('mapa-lista');
    var contador = document.getElementById('mapa-contador');
    var interactivo = contenedor.dataset.interactivo === '1';

    // ---- Mapa base (OpenStreetMap), centrado en Formosa ----
    var mapa = L.map(contenedor, {
        zoomControl: interactivo,
        dragging: interactivo,
        touchZoom: interactivo,
        scrollWheelZoom: interactivo,
        doubleClickZoom: interactivo,
        boxZoom: interactivo,
        keyboard: interactivo
    }).setView(
        [Number(contenedor.dataset.lat), Number(contenedor.dataset.lng)],
        Number(contenedor.dataset.zoom)
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(mapa);

    var capa = L.layerGroup().addTo(mapa);

    // ---- Filtros actuales ----
    var filtros = { categoria: '', estado: '', resueltos: false };
    var primeraCarga = true;
    var pedidoActual = 0;

    // Evita que un texto con HTML se interprete como código.
    function escapar(texto) {
        return String(texto === null || texto === undefined ? '' : texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function slug(estado) {
        return String(estado).replace(/\s+/g, '-');
    }

    function mayuscula(texto) {
        return texto.charAt(0).toUpperCase() + texto.slice(1);
    }

    function etiqueta(estado) {
        return '<span class="badge badge--' + slug(estado) + '">' + escapar(mayuscula(estado)) + '</span>';
    }

    // Contenido de la ventana emergente de cada marcador.
    function popup(r) {
        var html = '<div class="popup">' +
            '<div class="popup__titulo">' + escapar(r.categoria) + ' #' + r.id + '</div>' +
            etiqueta(r.estado) +
            (r.barrio ? '<div class="popup__meta">' + escapar(r.barrio) + '</div>' : '') +
            '<div class="popup__meta">Registrado ' + escapar(r.hace) + ' (' + escapar(r.fecha) + ')</div>';

        if (r.url) {
            html += '<div style="margin-top:8px"><a href="' + escapar(r.url) + '">Gestionar reclamo &rarr;</a></div>';
        }

        return html + '</div>';
    }

    function icono(estado) {
        return L.divIcon({
            className: 'pin-wrap',
            html: '<span class="pin pin--' + slug(estado) + '"></span>',
            iconSize: [22, 22],
            iconAnchor: [11, 11],
            popupAnchor: [0, -12]
        });
    }

    // ---- Dibujo de marcadores y lista lateral ----
    function dibujar(reportes) {
        capa.clearLayers();
        if (lista) { lista.innerHTML = ''; }

        var puntos = [];

        reportes.forEach(function (r) {
            var marcador = L.marker([r.lat, r.lng], {
                icon: icono(r.estado),
                title: r.categoria,
                interactive: interactivo,
                keyboard: interactivo
            }).addTo(capa);

            puntos.push([r.lat, r.lng]);

            // Solo con sesión se permite abrir marcadores y explorar la lista.
            if (interactivo && lista) {
                marcador.bindPopup(popup(r));

                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'mapa-item';
                item.innerHTML =
                    '<span class="pin pin--' + slug(r.estado) + '" style="width:14px;height:14px;border-width:2px;flex-shrink:0"></span>' +
                    '<span style="flex:1;min-width:0">' +
                        '<span class="mapa-item__titulo" style="display:block">' + escapar(r.categoria) + ' #' + r.id + '</span>' +
                        '<span class="mapa-item__meta" style="display:block">' + escapar(r.barrio || 'Sin barrio') + ' · ' + escapar(r.hace) + '</span>' +
                    '</span>';

                item.addEventListener('click', function () {
                    mapa.setView([r.lat, r.lng], Math.max(mapa.getZoom(), 16));
                    marcador.openPopup();
                    contenedor.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });

                lista.appendChild(item);
            }
        });

        if (lista && reportes.length === 0) {
            lista.innerHTML = '<div class="vacio"><strong>Sin resultados</strong>No hay reclamos con esos filtros.</div>';
        }

        // La primera vez, encuadra el mapa para ver todos los reclamos.
        if (primeraCarga && puntos.length > 0) {
            mapa.fitBounds(puntos, { padding: [40, 40], maxZoom: 15 });
        }
        primeraCarga = false;
    }

    // ---- Carga de datos ----
    function cargar() {
        var actual = ++pedidoActual;
        var params = new URLSearchParams();

        if (interactivo) {
            if (filtros.categoria) { params.set('categoria', filtros.categoria); }
            if (filtros.estado) { params.set('estado', filtros.estado); }
            if (filtros.resueltos) { params.set('resueltos', '1'); }
        }

        contador.textContent = 'Cargando reclamos…';

        fetch(urlDatos + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (respuesta) {
                if (!respuesta.ok) { throw new Error('Respuesta ' + respuesta.status); }
                return respuesta.json();
            })
            .then(function (datos) {
                // Si el usuario cambió un filtro mientras tanto, se descarta esta respuesta.
                if (actual !== pedidoActual) { return; }

                dibujar(datos.reportes);
                contador.textContent = datos.total === 1
                    ? '1 reclamo en el mapa'
                    : datos.total + ' reclamos en el mapa';
            })
            .catch(function () {
                if (actual !== pedidoActual) { return; }
                contador.textContent = 'No se pudieron cargar los reclamos. Intentá de nuevo.';
            });
    }

    // ---- Controles de filtro ----
    function conectarChips(idContenedor, clave) {
        var grupo = document.getElementById(idContenedor);
        if (!grupo || !interactivo) { return; }

        grupo.addEventListener('click', function (evento) {
            var boton = evento.target.closest('.chip');
            if (!boton) { return; }

            grupo.querySelectorAll('.chip').forEach(function (c) { c.classList.remove('is-active'); });
            boton.classList.add('is-active');

            filtros[clave] = boton.dataset.valor;

            // Elegir un estado "resuelto" obliga a incluir los resueltos.
            if (clave === 'estado' && filtros.estado === 'resuelto') {
                filtros.resueltos = true;
                document.getElementById('filtro-resueltos').checked = true;
            }

            cargar();
        });
    }

    conectarChips('filtro-categoria', 'categoria');
    conectarChips('filtro-estado', 'estado');

    var filtroResueltos = document.getElementById('filtro-resueltos');
    if (interactivo && filtroResueltos) {
        filtroResueltos.addEventListener('change', function (evento) {
            filtros.resueltos = evento.target.checked;

            // Si se excluyen resueltos, limpiar también el filtro de estado "resuelto".
            if (!filtros.resueltos && filtros.estado === 'resuelto') {
                filtros.estado = '';
                document.querySelectorAll('#filtro-estado .chip').forEach(function (chip) {
                    chip.classList.toggle('is-active', chip.dataset.valor === '');
                });
            }

            cargar();
        });
    }

    // ---- Ir a mi ubicación ----
    var botonUbicacion = document.getElementById('btn-ubicacion');
    var marcadorUsuario = null;

    if (interactivo && botonUbicacion) {
        botonUbicacion.addEventListener('click', function () {
            if (!navigator.geolocation) {
                contador.textContent = 'Tu navegador no permite obtener la ubicación.';
                return;
            }

            contador.textContent = 'Buscando tu ubicación…';

            navigator.geolocation.getCurrentPosition(function (posicion) {
                var punto = [posicion.coords.latitude, posicion.coords.longitude];

                if (marcadorUsuario) { mapa.removeLayer(marcadorUsuario); }

                marcadorUsuario = L.circleMarker(punto, {
                    radius: 9, color: '#fff', weight: 3, fillColor: '#2F6DA3', fillOpacity: 1
                }).addTo(mapa).bindTooltip('Estás acá');

                mapa.setView(punto, 16);
                contador.textContent = 'Mostrando tu ubicación.';
            }, function () {
                contador.textContent = 'No pudimos obtener tu ubicación. Revisá los permisos del navegador.';
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    }

    cargar();
})();
