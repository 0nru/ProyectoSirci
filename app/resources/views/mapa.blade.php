@extends('layouts.app')

@section('titulo', 'Mapa de reclamos')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('contenido')
    <div class="page-head">
        <div>
            <h1>Mapa de reclamos</h1>
            <p class="page-head__sub">Problemas urbanos informados por los vecinos de Formosa Capital.</p>
        </div>
        <div class="leyenda">
            <span><i style="background:var(--st-recibido-pin)"></i>Recibido</span>
            <span><i style="background:var(--st-proceso-pin)"></i>En proceso</span>
            <span><i style="background:var(--st-resuelto-pin)"></i>Resuelto</span>
        </div>
    </div>

    <div class="mapa-layout">
        <div>
            <div id="mapa-publico"
                 data-url="{{ route('mapa.datos') }}"
                 data-lat="-26.1849"
                 data-lng="-58.1731"
                 data-zoom="13"></div>
        </div>

        <aside class="mapa-lateral">
            <div class="card">
                <div class="campo" style="margin-bottom:12px">
                    <span class="etiqueta">Categoría</span>
                    <div class="chips" id="filtro-categoria">
                        <button type="button" class="chip is-active" data-valor="">Todas</button>
                        @foreach ($categorias as $c)
                            <button type="button" class="chip" data-valor="{{ $c->id }}">{{ $c->nombre }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="campo" style="margin-bottom:12px">
                    <span class="etiqueta">Estado</span>
                    <div class="chips" id="filtro-estado">
                        <button type="button" class="chip is-active" data-valor="">Todos</button>
                        @foreach ($estados as $e)
                            <button type="button" class="chip" data-valor="{{ $e }}">{{ ucfirst($e) }}</button>
                        @endforeach
                    </div>
                </div>

                <label class="check" style="margin-bottom:14px">
                    <input type="checkbox" id="filtro-resueltos">
                    <span>Incluir reclamos resueltos</span>
                </label>

                <button type="button" class="btn btn--sm btn--block" id="btn-ubicacion">
                    @include('partials.icon', ['n' => 'locate', 't' => 16])
                    Ir a mi ubicación
                </button>
            </div>

            <div class="mapa-contador" id="mapa-contador" role="status">Cargando reclamos…</div>

            <div class="mapa-lista" id="mapa-lista"></div>
        </aside>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('js/mapa-publico.js') }}"></script>
@endpush
