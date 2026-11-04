{{-- Etiqueta de color según el estado. Uso: @include('partials.estado', ['estado' => $reclamo->estado]) --}}
<span class="badge badge--{{ \Illuminate\Support\Str::slug($estado) }}">{{ ucfirst($estado) }}</span>
