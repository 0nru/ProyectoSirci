@extends('layouts.app')

@section('titulo', 'Notificaciones')

@section('contenido')
    <div class="page-head">
        <div>
            <h1>Notificaciones</h1>
            <p class="page-head__sub">Avisos sobre el avance de tus reclamos.</p>
        </div>

        @if ($notificaciones->where('leida', 0)->count() > 0)
            <form method="POST" action="/notificaciones/leer-todas">
                @csrf
                <button type="submit" class="btn btn--sm">
                    @include('partials.icon', ['n' => 'check', 't' => 16])
                    Marcar todas como leídas
                </button>
            </form>
        @endif
    </div>

    @if (count($notificaciones) === 0)
        <div class="vacio">
            <strong>No tenés notificaciones</strong>
            Te avisamos acá cuando el municipio actualice el estado de tus reclamos.
        </div>
    @else
        <div class="card card--flush notif-lista">
            @foreach ($notificaciones as $n)
                <form method="POST" action="/notificaciones/{{ $n->id }}/leer">
                    @csrf
                    <button type="submit" class="notif {{ $n->leida ? '' : 'is-nueva' }}">
                        <span class="notif__punto"></span>
                        <span>
                            <span class="notif__msg">{{ $n->mensaje }}</span><br>
                            <span class="notif__fecha">{{ \App\Support\Fecha::hace($n->fecha) }}</span>
                        </span>
                    </button>
                </form>
            @endforeach
        </div>
    @endif
@endsection
