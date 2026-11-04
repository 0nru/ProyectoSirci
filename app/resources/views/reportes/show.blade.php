@extends('layouts.app')

@section('titulo', 'Reclamo #' . $reclamo->id)

@push('estilos')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('contenido')
    <div class="page-head">
        <div>
            <a href="/reclamos" class="small">&larr; Mis reclamos</a>
            <h1 style="margin-top:6px">{{ $reclamo->categoria }} #{{ $reclamo->id }}</h1>
        </div>
        @include('partials.estado', ['estado' => $reclamo->estado])
    </div>

    <div class="grid-detalle grid-detalle--inverso">
        <div class="stack">
            @include('partials.reclamo-info')
        </div>
        <div class="stack">
            @include('partials.reclamo-timeline')
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('js/mapa-detalle.js') }}"></script>
@endpush
