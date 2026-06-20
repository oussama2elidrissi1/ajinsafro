<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\ClientNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead(Request $request, ClientNotification $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && (int) $notification->user_id === (int) $user->id, 403);

        if (! $notification->is_read) {
            $notification->forceFill(['is_read' => true])->save();
        }

        if ($notification->link) {
            return redirect()->to($notification->link);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        ClientNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Notifications marquees comme lues.');
    }
}
