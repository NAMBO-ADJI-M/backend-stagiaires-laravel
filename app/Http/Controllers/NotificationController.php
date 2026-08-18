<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Liste toutes les notifications de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'data' => $user->notifications()->paginate(20)
        ]);
    }

    /**
     * Marque une notification comme lue.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    /**
     * Marque toutes les notifications comme lues.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }
}
