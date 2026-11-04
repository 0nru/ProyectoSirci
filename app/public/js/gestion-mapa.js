// Mapa de solo lectura en el detalle de gestión. No modifica coordenadas.
(function () {
    'use strict';
    var contenedor = document.getElementById('mapa-gestion');
    if (!contenedor || typeof L === 'undefined') return;

    var lat = Number(contenedor.dataset.lat);
    var lng = Number(contenedor.dataset.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) return;

    var mapa = L.map(contenedor, { scrollWheelZoom: false }).setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
    }).addTo(mapa);
    L.marker([lat, lng]).addTo(mapa);
})();
