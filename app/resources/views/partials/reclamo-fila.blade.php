{{-- Fila de un reclamo en una lista. Uso: @include('partials.reclamo-fila', ['r' => $reclamo, 'url' => '/reclamos/'.$reclamo->id]) --}}
<a href="{{ $url }}" class="fila">
    <span class="fila__icono fila__icono--{{ \Illuminate\Support\Str::slug($r->estado) }}">
        @include('partials.categoria-icono', ['categoria' => $r->categoria])
    </span>
    <span class="fila__texto">
        <span class="fila__titulo">{{ $r->categoria }}@if ($r->barrio) — {{ $r->barrio }}@endif</span>
        <span class="fila__meta">#{{ $r->id }} · {{ \App\Support\Fecha::hace($r->fecha_creacion) }}</span>
    </span>
    @include('partials.estado', ['estado' => $r->estado])
</a>
