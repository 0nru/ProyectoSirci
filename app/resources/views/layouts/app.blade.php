<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'SIRCI') · SIRCI</title>

    <link rel="stylesheet" href="{{ asset('css/frontend-original.css') }}">
    @stack('estilos')
</head>
<body>
@php
    // Datos del usuario y menú según su rol.
    $usuario = auth()->user();
    $esMunicipio = $usuario && in_array($usuario->rol, ['operador', 'supervisor'], true);

    // Estos valores se compartirán desde el backend cuando integremos las notificaciones.
    $notifNoLeidas = $notifNoLeidas ?? 0;
    $notifRecientes = $notifRecientes ?? collect();

    if ($usuario && $esMunicipio) {
    // Navegación principal del operador y supervisor.
    // Gestión es la pantalla inicial del personal municipal.
        $menu = [
        ['url' => '/gestion', 'texto' => 'Gestión de reclamos', 'icono' => 'list'],
        ['url' => '/indicadores', 'texto' => 'Indicadores', 'icono' => 'chart'],
        ['url' => '/mapa', 'texto' => 'Mapa', 'icono' => 'map'],
    ];
    } elseif ($usuario) {
        $menu = [
            ['url' => '/panel', 'texto' => 'Inicio', 'icono' => 'home'],
        ];
        // El mapa aún no está habilitado: aparece solo cuando exista su ruta.
        if (\Illuminate\Support\Facades\Route::has('mapa')) {
            $menu[] = ['url' => '/mapa', 'texto' => 'Mapa', 'icono' => 'map'];
        }
        $menu[] = ['url' => '/reclamos', 'texto' => 'Mis reclamos', 'icono' => 'list'];
    } else {
        $menu = [];
        if (\Illuminate\Support\Facades\Route::has('mapa')) {
            $menu[] = ['url' => '/mapa', 'texto' => 'Mapa', 'icono' => 'map'];
        }
    }

    // Marca como activa la opción de la página actual.
    $activo = function (string $url) {
        $ruta = ltrim($url, '/');

        if ($url === '/reclamos') {
            return request()->is('reclamos') || (request()->is('reclamos/*') && ! request()->is('reclamos/nuevo'));
        }

        return request()->is($ruta) || request()->is($ruta . '/*');
    };

    $iniciales = $usuario
        ? collect(explode(' ', trim($usuario->nombre)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
        : '';
@endphp

<div class="shell">

    {{-- Menú lateral (escritorio) --}}
    <aside class="sidebar" aria-label="Menú principal">
        <a href="{{ $usuario ? ($esMunicipio ? '/gestion' : '/panel') : '/mapa' }}" class="brand">
            <span class="brand__mark">@include('partials.icon', ['n' => 'pin', 't' => 17])</span>
            SIRCI
        </a>

        <nav class="nav">
            @foreach ($menu as $item)
                <a href="{{ $item['url'] }}" class="nav__item {{ $activo($item['url']) ? 'is-active' : '' }}">
                    @include('partials.icon', ['n' => $item['icono'], 't' => 18])
                    {{ $item['texto'] }}
                </a>
            @endforeach

            @if ($usuario && ! $esMunicipio && \Illuminate\Support\Facades\Route::has('notificaciones'))
                <a href="/notificaciones" class="nav__item {{ request()->is('notificaciones') ? 'is-active' : '' }}">
                    @include('partials.icon', ['n' => 'bell', 't' => 18])
                    Notificaciones
                    @if ($notifNoLeidas > 0)
                        <span class="nav__count">{{ $notifNoLeidas }}</span>
                    @endif
                </a>
            @endif
        </nav>

        @if ($usuario && ! $esMunicipio)
            <a href="/reclamos/nuevo" class="btn btn--primary btn--block sidebar__cta">
                @include('partials.icon', ['n' => 'plus', 't' => 18])
                Nuevo reclamo
            </a>
        @endif
    </aside>

    <div class="main">

        {{-- Barra superior --}}
        <header class="topbar">
            <a href="{{ $usuario ? ($esMunicipio ? '/gestion' : '/panel') : '/mapa' }}" class="brand topbar__brand">
                <span class="brand__mark">@include('partials.icon', ['n' => 'pin', 't' => 17])</span>
                SIRCI
            </a>

            <div class="topbar__acciones">
                @if ($usuario)

                    {{-- Campana de notificaciones (solo escritorio; en el móvil está en la barra inferior) --}}
                    @if (! $esMunicipio && \Illuminate\Support\Facades\Route::has('notificaciones'))
                        <details class="menu only-desktop">
                            <summary class="icon-btn" aria-label="Notificaciones">
                                @include('partials.icon', ['n' => 'bell', 't' => 18])
                                @if ($notifNoLeidas > 0)
                                    <span class="icon-btn__badge">{{ $notifNoLeidas }}</span>
                                @endif
                            </summary>
                            <div class="menu__panel">
                                <div class="menu__head">
                                    <span>Notificaciones</span>
                                    @if ($notifNoLeidas > 0)
                                        <form method="POST" action="/notificaciones/leer-todas">
                                            @csrf
                                            <button type="submit" class="btn btn--ghost btn--sm">Marcar todas</button>
                                        </form>
                                    @endif
                                </div>

                                @if (count($notifRecientes) === 0)
                                    <div class="menu__empty">Todavía no tenés notificaciones.</div>
                                @else
                                    <div class="notif-lista">
                                        @foreach ($notifRecientes as $n)
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

                                <div class="menu__foot"><a href="/notificaciones">Ver todas</a></div>
                            </div>
                        </details>
                    @endif

                    {{-- Menú de usuario --}}
                    <details class="menu">
                        <summary class="avatar" aria-label="Menú de usuario">{{ $iniciales }}</summary>
                        <div class="menu__panel menu__panel--sm">
                            <div class="menu__user">
                                <strong>{{ $usuario->nombre }}</strong>
                                <span class="faint small">{{ ucfirst($usuario->rol) }}</span>
                            </div>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit" class="menu__link">
                                    @include('partials.icon', ['n' => 'logout', 't' => 18])
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </details>

                @else
                    <a href="/registro" class="btn btn--sm">Crear cuenta</a>
                    <a href="/login" class="btn btn--primary btn--sm">Ingresar</a>
                @endif
            </div>
        </header>

        {{-- Contenido de cada pantalla --}}
        <main class="content">
            @if (session('status'))
                <div class="alert alert--ok" role="status">
                    @include('partials.icon', ['n' => 'check', 't' => 18])
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @yield('contenido')
        </main>
    </div>

    {{-- Barra inferior (móvil) --}}
    @if ($usuario)
        <nav class="tabbar" aria-label="Navegación">
            @if ($esMunicipio)
                @foreach ($menu as $item)
                    <a href="{{ $item['url'] }}" class="tabbar__item {{ $activo($item['url']) ? 'is-active' : '' }}">
                        @include('partials.icon', ['n' => $item['icono'], 't' => 21])
                        {{ $item['texto'] }}
                    </a>
                @endforeach
            @else
                <a href="/panel" class="tabbar__item {{ $activo('/panel') ? 'is-active' : '' }}">
                    @include('partials.icon', ['n' => 'home', 't' => 21])
                    Inicio
                </a>
                @if (\Illuminate\Support\Facades\Route::has('mapa'))
                <a href="/mapa" class="tabbar__item {{ $activo('/mapa') ? 'is-active' : '' }}">
                    @include('partials.icon', ['n' => 'map', 't' => 21])
                    Mapa
                </a>
                @endif
                <a href="/reclamos/nuevo" class="tabbar__fab" aria-label="Nuevo reclamo">
                    @include('partials.icon', ['n' => 'plus', 't' => 24])
                </a>
                <a href="/reclamos" class="tabbar__item {{ $activo('/reclamos') ? 'is-active' : '' }}">
                    @include('partials.icon', ['n' => 'list', 't' => 21])
                    Reclamos
                </a>
                @if (\Illuminate\Support\Facades\Route::has('notificaciones'))
                <a href="/notificaciones" class="tabbar__item {{ request()->is('notificaciones') ? 'is-active' : '' }}">
                    @include('partials.icon', ['n' => 'bell', 't' => 21])
                    Alertas
                    @if ($notifNoLeidas > 0)
                        <span class="tabbar__badge">{{ $notifNoLeidas }}</span>
                    @endif
                </a>
                @endif
            @endif
        </nav>
    @endif
</div>

@stack('scripts')
</body>
</html>
