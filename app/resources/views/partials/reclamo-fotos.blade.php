{{-- Galería reutilizada en detalle ciudadano y Gestión municipal. --}}
@if ($fotos->isEmpty())
    <p class="faint small">Este reclamo no tiene fotografías adjuntas.</p>
@else
    <div class="fotos">
        @foreach ($fotos as $foto)
            @php
                // La ruta almacenada es relativa al disco public de Laravel.
                $urlFoto = '/storage/' . ltrim($foto->url, '/');
            @endphp
            <a href="{{ $urlFoto }}" target="_blank" rel="noopener noreferrer"
               aria-label="Abrir fotografía {{ $loop->iteration }} del reclamo {{ $reclamo->id }}">
                <img src="{{ $urlFoto }}"
                     alt="Fotografía {{ $loop->iteration }} del reclamo {{ $reclamo->id }}"
                     loading="lazy"
                     style="width:100%; height:155px; object-fit:cover; display:block; border-radius:8px; border:1px solid #e2e8f0;">
            </a>
        @endforeach
    </div>
@endif
