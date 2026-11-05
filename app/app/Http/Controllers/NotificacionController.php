<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Notificaciones dentro de la aplicación (RF-10).
class NotificacionController extends Controller
{
    // Listado completo de las notificaciones del usuario.
    public function index()
    {
        $notificaciones = DB::table('notificaciones')
            ->where('usuario_id', Auth::id())
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        return view('notificaciones.index', compact('notificaciones'));
    }

    // Marca una notificación como leída y lleva al reclamo relacionado.
    public function leer(int $notificacion)
    {
        $fila = DB::table('notificaciones')
            ->where('id', $notificacion)
            ->where('usuario_id', Auth::id())
            ->first();

        abort_if($fila === null, 404);

        DB::table('notificaciones')->where('id', $fila->id)->update(['leida' => 1]);

        if ($fila->reporte_id) {
            return redirect('/reclamos/' . $fila->reporte_id);
        }

        return redirect('/notificaciones');
    }

    // Marca todas las notificaciones pendientes como leídas.
    public function leerTodas()
    {
        DB::table('notificaciones')
            ->where('usuario_id', Auth::id())
            ->where('leida', 0)
            ->update(['leida' => 1]);

        return back();
    }
}
