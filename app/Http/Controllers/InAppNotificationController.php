<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Serves the in-app notifications behind the bell in the admin and associate
 * navigation bars.
 *
 * The rows are written by the `database` channel of every notification that
 * includes it in via(). They are scoped to the signed in user, so a dispatcher
 * only ever sees his own.
 */
class InAppNotificationController extends Controller
{
    /**
     * The unread notifications for the bell, newest first, plus the badge total.
     *
     * Only unread rows are listed: the bell is a to-do list of what still needs
     * looking at, and a notification disappears from it once it has been read.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $unread = $user->unreadNotifications()
            ->take(20)
            ->get()
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'body' => $notification->data['body'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'created_at' => $notification->created_at->diffForHumans(),
            ]);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $unread,
        ]);
    }

    /**
     * Mark a single notification as read.
     *
     * The lookup is scoped to the signed in user, so a notification belonging to
     * somebody else is a 404 rather than something he could clear.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Mark every unread notification as read at once.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
