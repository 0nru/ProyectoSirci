@extends('layouts.app')

@section('titulo', 'Mis reclamos')

@section('contenido')
    <div class="page-head">
        <div>
            <h1>Mis reclamos</h1>
            <p class="page-head__sub">Seguí el estado de cada reclamo que registraste.</p>
        </div>
        <a href="/reclamos/nuevo" class="btn btn--primary btn--sm">
            @include('partials.icon', ['n' => 'plus', 't' => 16])
            Nuevo reclamo
        </a>
    </div>

    <div class="chips" style="margin-bottom:16px">
        <a href="/reclamos" class="chip {{ $filtro === 'todos' ? 'is-active' : '' }}">Todos ({{ $conteos['todos'] }})</a>
        <a href="/reclamos?filtro=activos" class="chip {{ $filtro === 'activos' ? 'is-active' : '' }}">Activos ({{ $conteos['activos'] }})</a>
        <a href="/reclamos?filtro=resueltos" class="chip {{ $filtro === 'resueltos' ? 'is-active' : '' }}">Resueltos ({{ $conteos['resueltos'] }})</a>
    </div>

    @if (count($reclamos) === 0)
        <div class="vacio">
            <strong>No hay reclamos para mostrar</strong>
            @if ($filtro === 'todos')
                Registrá tu primer reclamo para empezar.
            @else
                Probá con otro filtro.
            @endif
        </div>
    @else
        <div class="lista">
            @foreach ($reclamos as $r)
                @include('partials.reclamo-fila', ['r' => $r, 'url' => '/reclamos/' . $r->id])
            @endforeach
        </div>
    @endif
@endsection
