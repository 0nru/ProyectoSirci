@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <div class="saludo">
        <h1>Hola, {{ \Illuminate\Support\Str::before(auth()->user()->nombre, ' ') }}</h1>
        <p>Reportá un problema de tu barrio o seguí el estado de tus reclamos.</p>
    </div>

    <div class="acciones-rapidas">
        <a href="/reclamos/nuevo" class="accion accion--principal">
            <span class="accion__icono">@include('partials.icon', ['n' => 'plus', 't' => 22])</span>
            <span>
                <span class="accion__titulo" style="display:block">Nuevo reclamo</span>
                <span class="accion__sub" style="display:block">Marcá el lugar en el mapa y contanos qué pasa</span>
            </span>
        </a>

        @if (\Illuminate\Support\Facades\Route::has('mapa'))
        <a href="/mapa" class="accion">
            <span class="accion__icono">@include('partials.icon', ['n' => 'map', 't' => 22])</span>
            <span>
                <span class="accion__titulo" style="display:block">Mapa de reclamos</span>
                <span class="accion__sub muted" style="display:block">{{ $activosCiudad }} reclamos activos en la ciudad</span>
            </span>
        </a>
        @endif
    </div>

    <div class="kpis kpis--3">
        <div class="kpi">
            <div class="kpi__label">Mis reclamos</div>
            <div class="kpi__valor">{{ $resumen['total'] }}</div>
        </div>
        <div class="kpi">
            <div class="kpi__label">En curso</div>
            <div class="kpi__valor">{{ $resumen['activos'] }}</div>
        </div>
        <div class="kpi">
            <div class="kpi__label">Resueltos</div>
            <div class="kpi__valor">{{ $resumen['resueltos'] }}</div>
        </div>
    </div>

    <div class="seccion-titulo">
        <h2>Mis últimos reclamos</h2>
        @if ($resumen['total'] > 0)
            <a href="/reclamos" class="small">Ver todos</a>
        @endif
    </div>

    @if (count($recientes) === 0)
        <div class="vacio">
            <strong>Todavía no registraste reclamos</strong>
            Cuando cargues el primero, vas a poder seguir acá cómo avanza.
            <div style="margin-top:14px"><a href="/reclamos/nuevo" class="btn btn--primary btn--sm">Registrar un reclamo</a></div>
        </div>
    @else
        <div class="lista">
            @foreach ($recientes as $r)
                @include('partials.reclamo-fila', ['r' => $r, 'url' => '/reclamos/' . $r->id])
            @endforeach
        </div>
    @endif
@endsection
