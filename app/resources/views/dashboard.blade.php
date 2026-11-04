@extends('layouts.app')

@section('titulo', 'Indicadores')

@section('contenido')
    <div class="page-head">
        <div>
            <h1>Panel de indicadores</h1>
            <p class="page-head__sub">Resumen de los reclamos para priorizar el trabajo del municipio.</p>
        </div>

        <div class="chips">
            <a href="/indicadores?periodo=7" class="chip {{ $periodo === '7' ? 'is-active' : '' }}">7 días</a>
            <a href="/indicadores?periodo=30" class="chip {{ $periodo === '30' ? 'is-active' : '' }}">30 días</a>
            <a href="/indicadores?periodo=90" class="chip {{ $periodo === '90' ? 'is-active' : '' }}">90 días</a>
            <a href="/indicadores?periodo=todo" class="chip {{ $periodo === 'todo' ? 'is-active' : '' }}">Todo</a>
        </div>
    </div>

    {{-- Indicadores principales --}}
    <div class="kpis">
        <div class="kpi">
            <div class="kpi__label">Reclamos</div>
            <div class="kpi__valor">{{ $kpis['total'] }}</div>
            <div class="kpi__nota">en el período</div>
        </div>
        <div class="kpi {{ $demorados > 0 ? 'kpi--alerta' : '' }}">
            <div class="kpi__label">Sin atender</div>
            <div class="kpi__valor">{{ $kpis['recibidos'] }}</div>
            <div class="kpi__nota">
                @if ($demorados > 0)
                    {{ $demorados }} hace más de 3 días
                @else
                    todos al día
                @endif
            </div>
        </div>
        <div class="kpi">
            <div class="kpi__label">En proceso</div>
            <div class="kpi__valor">{{ $kpis['en_proceso'] }}</div>
            <div class="kpi__nota">en trabajo</div>
        </div>
        <div class="kpi">
            <div class="kpi__label">Resueltos</div>
            <div class="kpi__valor">{{ $kpis['resueltos'] }}</div>
            <div class="kpi__nota">{{ $kpis['tasa'] }} % del total</div>
        </div>
        <div class="kpi">
            <div class="kpi__label">Tiempo promedio</div>
            <div class="kpi__valor">{{ $tiempo['valor'] }}<small>{{ $tiempo['unidad'] }}</small></div>
            <div class="kpi__nota">hasta la resolución</div>
        </div>
    </div>

    <div class="grid-2">
        {{-- Por categoría --}}
        <div class="card">
            <h2 style="margin-bottom:16px">Reclamos por categoría</h2>
            @if (count($porCategoria) === 0)
                <p class="muted small">No hay reclamos en este período.</p>
            @else
                @php $maxCategoria = max(1, (int) $porCategoria->max('total')); @endphp
                <div class="barras">
                    @foreach ($porCategoria as $c)
                        <div>
                            <div class="barra__top"><span>{{ $c->nombre }}</span><span>{{ $c->total }}</span></div>
                            <div class="barra__pista"><div class="barra__fill" style="width: {{ round($c->total * 100 / $maxCategoria) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Por estado --}}
        <div class="card">
            <h2 style="margin-bottom:16px">Reclamos por estado</h2>
            @php $maxEstado = max(1, (int) $porEstado->max()); @endphp
            <div class="barras">
                @foreach (['recibido', 'en proceso', 'resuelto'] as $e)
                    <div>
                        <div class="barra__top"><span>{{ ucfirst($e) }}</span><span>{{ (int) ($porEstado[$e] ?? 0) }}</span></div>
                        <div class="barra__pista"><div class="barra__fill barra__fill--{{ \Illuminate\Support\Str::slug($e) }}" style="width: {{ round(((int) ($porEstado[$e] ?? 0)) * 100 / $maxEstado) }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Direcciones/referencias declaradas por los ciudadanos (no barrios normalizados). --}}
        <div class="card">
            <h2 style="margin-bottom:16px">Direcciones o referencias más repetidas</h2>
            @if (count($porBarrio) === 0)
                <p class="muted small">No hay direcciones o referencias registradas en este período.</p>
            @else
                @php $maxBarrio = max(1, (int) $porBarrio->max('total')); @endphp
                <div class="barras">
                    @foreach ($porBarrio as $b)
                        <div>
                            <div class="barra__top"><span>{{ $b->nombre }}</span><span>{{ $b->total }}</span></div>
                            <div class="barra__pista"><div class="barra__fill" style="width: {{ round($b->total * 100 / $maxBarrio) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Por área --}}
        <div class="card">
            <h2 style="margin-bottom:16px">Carga por área municipal</h2>
            @if (count($porArea) === 0)
                <p class="muted small">No hay reclamos en este período.</p>
            @else
                @php $maxArea = max(1, (int) $porArea->max('total')); @endphp
                <div class="barras">
                    @foreach ($porArea as $a)
                        <div>
                            <div class="barra__top"><span>{{ $a->nombre }}</span><span>{{ $a->total }}</span></div>
                            <div class="barra__pista"><div class="barra__fill" style="width: {{ round($a->total * 100 / $maxArea) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Reclamos nuevos por día --}}
    <div class="card" style="margin-top:16px">
        <h2 style="margin-bottom:16px">Reclamos nuevos por día <span class="faint small">· últimos 14 días</span></h2>
        @php $maxSerie = max(1, (int) collect($serie)->max('total')); @endphp
        <div class="columnas">
            @foreach ($serie as $d)
                <div class="columna" title="{{ $d['completa'] }}: {{ $d['total'] }}">
                    <span class="columna__num">{{ $d['total'] }}</span>
                    <div class="columna__fill {{ $d['total'] === 0 ? 'columna__fill--cero' : '' }}" style="height: {{ $d['total'] === 0 ? 3 : max(6, round($d['total'] * 80 / $maxSerie)) }}px"></div>
                    <span class="columna__dia">{{ $d['etiqueta'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Últimos reclamos --}}
    <div class="seccion-titulo">
        <h2>Últimos reclamos recibidos</h2>
        <a href="/gestion" class="small">Ver todos</a>
    </div>
    <div class="lista">
        @foreach ($recientes as $r)
            @include('partials.reclamo-fila', ['r' => $r, 'url' => '/gestion/' . $r->id])
        @endforeach
    </div>
@endsection
