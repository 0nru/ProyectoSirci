{{-- Ícono de línea. Uso: @include('partials.icon', ['n' => 'home', 't' => 20]) --}}
@php
    $iconos = [
        'home'     => '<path d="M3 11l9-7 9 7"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/>',
        'map'      => '<path d="M9 20l-6-2V4l6 2 6-2 6 2v14l-6-2-6 2z"/><path d="M9 6v14M15 4v14"/>',
        'list'     => '<path d="M9 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/><path d="M9 15l9-9 3 3-9 9H9z"/>',
        'bell'     => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'chart'    => '<path d="M4 19V10M12 19V4M20 19v-6"/>',
        'pin'      => '<path d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.4"/>',
        'camera'   => '<path d="M4 8a2 2 0 0 1 2-2h1.2a1 1 0 0 0 .8-.4l1-1.3a1 1 0 0 1 .8-.4h4.4a1 1 0 0 1 .8.4l1 1.3a1 1 0 0 0 .8.4H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><circle cx="12" cy="13" r="3.2"/>',
        'check'    => '<path d="M20 6L9 17l-5-5"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'back'     => '<path d="M15 18l-6-6 6-6"/>',
        'next'     => '<path d="M9 18l6-6-6-6"/>',
        'locate'   => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="8"/>',
        'alert'    => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18.5v.01"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
        'bache'    => '<path d="M3 12h18M3 12a2 2 0 0 1 2-2h1M21 12a2 2 0 0 0-2-2h-1"/><circle cx="8" cy="17" r="1.5"/><circle cx="16" cy="17" r="1.5"/>',
        'luz'      => '<path d="M9 18h6M10 21h4M15 3.5A6 6 0 0 0 6 9c0 3 2 4.5 2 6h8c0-1.5 2-3 2-6a6 6 0 0 0-3-5.5"/>',
        'basural'  => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'senal'    => '<rect x="9" y="2" width="6" height="14" rx="2"/><circle cx="12" cy="6" r="1"/><circle cx="12" cy="9" r="1"/><circle cx="12" cy="12" r="1"/><path d="M12 16v6"/>',
        'otro'     => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>',
    ];
@endphp
<svg class="icon" width="{{ $t ?? 20 }}" height="{{ $t ?? 20 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $iconos[$n] ?? '' !!}</svg>
