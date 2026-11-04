@extends('layouts.guest')

@section('titulo', 'Crear cuenta')

@section('contenido')
    <form method="POST" action="/registro" class="card" novalidate>
        @csrf

        <div class="campo">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}"
                   placeholder="Nombre y apellido" autocomplete="name" required autofocus>
            @error('nombre')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   placeholder="tu@email.com" autocomplete="email" required>
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <label for="telefono">Teléfono <span class="opcional">(opcional)</span></label>
            <input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}"
                   placeholder="3704 123 456" autocomplete="tel">
            @error('telefono')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password"
                   placeholder="Mínimo 8 caracteres" autocomplete="new-password" required>
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <label for="password_confirmation">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   placeholder="Repetí la contraseña" autocomplete="new-password" required>
        </div>

        <div class="campo">
            <label class="check" style="text-transform:none; letter-spacing:0; font-weight:400;">
                <input type="checkbox" name="terminos" value="1" @checked(old('terminos'))>
                <span>Acepto los términos de uso y la política de privacidad de SIRCI.</span>
            </label>
            @error('terminos')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn--primary btn--block">Crear cuenta</button>
    </form>

    <p class="auth__pie">
        ¿Ya tenés cuenta? <a href="/login"><strong>Iniciar sesión</strong></a>
    </p>
@endsection
