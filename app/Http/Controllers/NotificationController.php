<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/** In-app notification bell actions (PRD v2 §8). */
class NotificationController extends Controller
{
    /** Open a notification: mark read, then go to its target page. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $routeName = $notification->data['route'] ?? 'dashboard';
        $target = Route::has($routeName) ? route($routeName) : route('dashboard');

        return redirect($target);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Semua notifikasi ditandai dibaca.');
    }
}
