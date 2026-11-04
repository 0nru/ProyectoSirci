{{-- Datos principales de un reclamo (descripción, datos y mapa). Variable: $reclamo --}}
<div class="card">
    <h2 style="margin-bottom:12px">Descripción</h2>
    <p>{{ $reclamo->descripcion ?: 'Sin descripción.' }}</p>
</div>

<div class="card">
    <dl class="datos">
        <div>
            <dt>Categoría</dt>
            <dd>{{ $reclamo->categoria }}</dd>
        </div>
        <div>
            <dt>Área responsable</dt>
            <dd>{{ $reclamo->area }}</dd>
        </div>
        <div>
            <dt>Barrio o referencia</dt>
            <dd>{{ $reclamo->barrio ?: 'Sin indicar' }}</dd>
        </div>
        <div>
            <dt>Registrado</dt>
            <dd>{{ \App\Support\Fecha::conHora($reclamo->fecha_creacion) }}</dd>
        </div>
        @if ($reclamo->fecha_resolucion)
            <div>
                <dt>Resuelto</dt>
                <dd>{{ \App\Support\Fecha::conHora($reclamo->fecha_resolucion) }}</dd>
            </div>
        @endif
    </dl>
</div>

<div class="card">
    <h2 style="margin-bottom:12px">Ubicación</h2>
    <div id="mapa-detalle"
         data-lat="{{ $reclamo->latitud }}"
         data-lng="{{ $reclamo->longitud }}"
         data-estado="{{ \Illuminate\Support\Str::slug($reclamo->estado) }}"></div>
    <p class="faint small" style="margin-top:8px">{{ $reclamo->latitud }}, {{ $reclamo->longitud }}</p>
</div>

<div class="card">
    <h2 style="margin-bottom:12px">Fotos</h2>
    <div class="fotos">
        <div class="foto-placeholder">@include('partials.icon', ['n' => 'camera', 't' => 24])</div>
    </div>
    <p class="faint small" style="margin-top:8px">La carga de fotografías estará disponible próximamente.</p>
</div>
