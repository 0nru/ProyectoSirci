@extends('layouts.app')

@section('titulo', 'Administración de usuarios')

@push('estilos')
<style>
    .usuarios-tarjeta { margin-bottom: 18px; }
    .usuarios-tarjeta h2 { margin-top: 0; }
    .usuarios-tabla { width: 100%; border-collapse: collapse; }
    .usuarios-tabla th, .usuarios-tabla td { padding: 12px 10px; border-bottom: 1px solid var(--border, #dedede); text-align: left; vertical-align: middle; }
    .usuarios-tabla th { font-size: 0.85rem; }
    .usuarios-tabla td { overflow-wrap: anywhere; }
    .usuarios-tabla .usuarios-controles { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .usuarios-tabla select { min-width: 105px; max-width: 150px; }
    .usuarios-tabla .usuarios-correo { font-size: .85rem; opacity: .75; }
    .usuarios-tabla .usuarios-yo { font-size: .8rem; opacity: .75; }
    .usuarios-errores { margin-bottom: 16px; }
    .usuarios-paginacion { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 16px; }
    @media(max-width: 850px) {
        .usuarios-tabla thead { display: none; }
        .usuarios-tabla, .usuarios-tabla tbody, .usuarios-tabla tr, .usuarios-tabla td { display: block; width: 100%; }
        .usuarios-tabla tr { border-bottom: 1px solid var(--border, #dedede); padding: 12px 0; }
        .usuarios-tabla td { border: 0; padding: 4px 0; }
    }
</style>
@endpush

@section('contenido')
    <div class="page-head">
        <div>
            <a href="{{ route('gestion.index') }}" class="small">&larr; Volver a Gestión</a>
            <h1 style="margin-top:6px">Administración de usuarios</h1>
            <p class="page-head__sub">Solo para supervisores. Creá operadores y administrá roles y estados de las cuentas.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="card usuarios-errores" role="alert">
            <strong>No se pudo completar la operación:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card usuarios-tarjeta">
        <h2>Crear cuenta de operador</h2>
        <p class="muted small">La cuenta se crea activa. El usuario deberá utilizar las credenciales que le asignes.</p>
        <form method="POST" action="{{ route('usuarios.crearOperador') }}">
            @csrf
            <div class="grid-detalle">
                <div class="stack">
                    <div class="campo">
                        <label for="nuevo-nombre">Nombre completo</label>
                        <input id="nuevo-nombre" name="nombre" type="text" required maxlength="100" value="{{ old('nombre') }}">
                    </div>
                    <div class="campo">
                        <label for="nuevo-email">Correo electrónico</label>
                        <input id="nuevo-email" name="email" type="email" required maxlength="150" value="{{ old('email') }}">
                    </div>
                    <div class="campo">
                        <label for="nuevo-telefono">Teléfono (opcional)</label>
                        <input id="nuevo-telefono" name="telefono" type="text" maxlength="30" value="{{ old('telefono') }}">
                    </div>
                </div>
                <div class="stack">
                    <div class="campo">
                        <label for="nuevo-password">Contraseña (mínimo 8 caracteres)</label>
                        <input id="nuevo-password" name="password" type="password" minlength="8" required autocomplete="new-password">
                    </div>
                    <div class="campo">
                        <label for="nuevo-password-conf">Repetir contraseña</label>
                        <input id="nuevo-password-conf" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">
                    </div>
                    <button class="btn btn--primary" type="submit">Crear operador</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card usuarios-tarjeta">
        <h2>Usuarios registrados</h2>
        <form method="GET" action="{{ route('usuarios.index') }}" class="campo">
            <label for="usuarios-busqueda">Buscar por nombre o correo</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input id="usuarios-busqueda" type="search" name="q" maxlength="100" value="{{ $busqueda }}" placeholder="Nombre o correo" style="flex:1;min-width:180px">
                <button type="submit" class="btn btn--primary btn--sm">Buscar</button>
                @if ($busqueda !== '')
                    <a href="{{ route('usuarios.index') }}" class="btn btn--sm">Limpiar</a>
                @endif
            </div>
        </form>

        <table class="usuarios-tabla">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol y estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $u)
                    <tr>
                        <td>
                            <strong>#{{ $u->id }} · {{ $u->nombre }}</strong>
                            <div class="usuarios-correo">{{ $u->email }}</div>
                            @if ((int) $u->id === (int) auth()->id())
                                <div class="usuarios-yo">Tu cuenta</div>
                            @endif
                        </td>
                        @if ((int) $u->id === (int) auth()->id())
                            <td>{{ ucfirst($u->rol) }} · {{ $u->activo ? 'Activo' : 'Inactivo' }}</td>
                            <td><span class="muted small">No podés modificar tu propia cuenta.</span></td>
                        @else
                            <td colspan="2">
                                <form method="POST" action="{{ route('usuarios.actualizar', ['usuario' => $u->id]) }}" class="usuarios-controles">
                                    @csrf
                                    <label>
                                        <span class="small">Rol</span>
                                        <select name="rol" aria-label="Rol de {{ $u->nombre }}">
                                            @foreach (['ciudadano', 'operador', 'supervisor'] as $rol)
                                                <option value="{{ $rol }}" @selected($u->rol === $rol)>{{ ucfirst($rol) }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span class="small">Estado</span>
                                        <select name="activo" aria-label="Estado de {{ $u->nombre }}">
                                            <option value="1" @selected((int) $u->activo === 1)>Activo</option>
                                            <option value="0" @selected((int) $u->activo === 0)>Inactivo</option>
                                        </select>
                                    </label>
                                    <button class="btn btn--primary btn--sm" type="submit">Guardar cambios</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="3">No hay usuarios con esos criterios.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="usuarios-paginacion">
            <span class="small muted">{{ $usuarios->total() }} usuario(s) · página {{ $usuarios->currentPage() }} de {{ $usuarios->lastPage() }}</span>
            <div style="display:flex;gap:8px">
                @if ($usuarios->previousPageUrl())
                    <a class="btn btn--sm" href="{{ $usuarios->previousPageUrl() }}">Anterior</a>
                @endif
                @if ($usuarios->nextPageUrl())
                    <a class="btn btn--sm" href="{{ $usuarios->nextPageUrl() }}">Siguiente</a>
                @endif
            </div>
        </div>
    </div>
@endsection
