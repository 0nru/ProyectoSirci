{{-- Ícono según la categoría del reclamo. Uso: @include('partials.categoria-icono', ['categoria' => 'Bache']) --}}
@php
    $nombre = \Illuminate\Support\Str::lower($categoria);
    $icono = match (true) {
        str_contains($nombre, 'bache')   => 'bache',
        str_contains($nombre, 'lumin')   => 'luz',
        str_contains($nombre, 'basura'),
        str_contains($nombre, 'basural') => 'basural',
        str_contains($nombre, 'señal')   => 'senal',
        default                          => 'otro',
    };
@endphp
@include('partials.icon', ['n' => $icono, 't' => 20])
