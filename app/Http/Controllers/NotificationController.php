<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->take(20)->get(['id', 'data', 'read_at', 'created_at']),
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->notifications()->whereKey($id)->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return back();
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'email_mentions' => ['boolean'],
            'email_digest' => ['boolean'],
        ]);

        $user->update(['notification_preferences' => array_merge($user->notification_preferences ?? [], $data)]);

        return back()->with('status', 'Notificatievoorkeuren bijgewerkt.');
    }
}
