@extends('layouts.guest')

@section('titulo', 'Ingresar')

@section('contenido')
    <form method="POST" action="{{ route('login') }}" class="card" novalidate>
        {{-- Token de seguridad que exige Laravel en los formularios POST. --}}
        @csrf

        @error('email')
            <div class="alert alert--error" role="alert">{{ $message }}</div>
        @enderror

        @if (session('status'))
            <div class="alert alert--ok" role="status">{{ session('status') }}</div>
        @endif

        <div class="campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   placeholder="tu@email.com" autocomplete="email" required autofocus>
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" autocomplete="current-password" required>
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn--primary btn--block">Iniciar sesión</button>
    </form>

    {{-- Se muestran cuando incorporemos las rutas respectivas. --}}
    @if (\Illuminate\Support\Facades\Route::has('registro'))
        <p class="auth__pie">
            ¿No tenés cuenta? <a href="{{ route('registro') }}"><strong>Registrate</strong></a>
        </p>
    @endif

    @if (\Illuminate\Support\Facades\Route::has('mapa'))
        <p class="auth__alt">
            <a href="{{ route('mapa') }}" class="muted">Ver el mapa de reclamos sin iniciar sesión</a>
        </p>
    @endif
@endsection
