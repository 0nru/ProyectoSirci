{{-- Línea de tiempo de un reclamo. Variable: $historial --}}
<div class="card">
    <h2 style="margin-bottom:16px">Seguimiento</h2>

    @if (count($historial) === 0)
        <p class="muted small">Todavía no hay movimientos registrados.</p>
    @else
        <ol class="timeline">
            @foreach ($historial as $h)
                <li class="timeline__item">
                    <span class="timeline__punto timeline__punto--{{ \Illuminate\Support\Str::slug($h->estado) }}"></span>
                    <div>
                        <div class="timeline__texto">
                            @include('partials.estado', ['estado' => $h->estado])
                            @if ($h->observacion)
                                <span style="display:block; margin-top:4px">{{ $h->observacion }}</span>
                            @endif
                        </div>
                        <div class="timeline__meta">
                            {{ $h->usuario }} · {{ \App\Support\Fecha::conHora($h->fecha_cambio) }}
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
