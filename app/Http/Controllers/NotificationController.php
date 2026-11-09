<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // GET /notificaciones - bandeja de notificaciones del usuario autenticado
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    // GET /notificaciones/{notification} - marca como leída y abre el destino
    public function read(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('dashboard'));
    }

    // PATCH /notificaciones/leer-todas - marca todas como leídas
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
