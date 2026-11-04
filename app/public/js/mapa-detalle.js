// Mini mapa de solo lectura que muestra dónde está un reclamo.
(function () {
    var contenedor = document.getElementById('mapa-detalle');

    if (!contenedor || typeof L === 'undefined') {
        return;
    }

    var lat = Number(contenedor.dataset.lat);
    var lng = Number(contenedor.dataset.lng);

    if (!isFinite(lat) || !isFinite(lng)) {
        return;
    }

    var mapa = L.map(contenedor, { scrollWheelZoom: false }).setView([lat, lng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(mapa);

    var estado = contenedor.dataset.estado || 'recibido';

    L.marker([lat, lng], {
        icon: L.divIcon({
            className: 'pin-wrap',
            html: '<span class="pin pin--' + estado + '"></span>',
            iconSize: [22, 22],
            iconAnchor: [11, 11]
        })
    }).addTo(mapa);
})();
