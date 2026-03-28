<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\ChatChannel;
use App\Models\ChatChannelMember;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessagesController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $partner = $user->partner;

        // Contacts autorisés: comptes siège/admin + comptabilité (extensible via rôles)
        $contacts = User::query()
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->get(['id', 'name', 'email'])
            ->filter(function (User $u) {
                return $u->canAccessAdmin() || $u->isComptable();
            })
            ->values();

        return view('partner_v2.messages.index', compact('partner', 'contacts'));
    }

    /**
     * Liste des canaux accessibles au partenaire (sidebar).
     */
    public function channels(Request $request): JsonResponse
    {
        $user = $request->user();
        $channelIds = ChatChannelMember::where('user_id', $user->id)->pluck('channel_id');

        $channels = ChatChannel::with(['reservation', 'members' => fn ($q) => $q->with('user:id,name,email')])
            ->whereIn('id', $channelIds)
            ->withCount(['messages'])
            ->with(['channelMembers' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (ChatChannel $ch) use ($user) {
                $member = $ch->channelMembers->first();
                $unread = 0;
                if ($member) {
                    if ($member->last_read_at) {
                        $unread = $ch->messages()->where('created_at', '>', $member->last_read_at)->count();
                    } else {
                        $unread = $ch->messages_count;
                    }
                }
                $otherMembers = $ch->members->where('id', '!=', $user->id);
                $label = $ch->display_name;
                if ($ch->type === ChatChannel::TYPE_DIRECT && $otherMembers->isNotEmpty()) {
                    $label = $otherMembers->first()->user->name ?? $label;
                }

                return [
                    'id' => $ch->id,
                    'type' => $ch->type,
                    'name' => $label,
                    'display_name' => $ch->display_name,
                    'reservation_id' => $ch->reservation_id,
                    'messages_count' => $ch->messages_count,
                    'unread' => $unread,
                    'updated_at' => $ch->updated_at?->toIso8601String(),
                ];
            });

        return response()->json(['channels' => $channels]);
    }

    public function messages(Request $request, ChatChannel $channel): JsonResponse
    {
        $user = $request->user();
        if (! $channel->members()->where('users.id', $user->id)->exists()) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        ChatChannelMember::where('channel_id', $channel->id)->where('user_id', $user->id)->update(['last_read_at' => now()]);

        $messages = $channel->messages()
            ->with('sender:id,name,email')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender->name ?? '',
                'message' => $m->message,
                'created_at' => $m->created_at->toIso8601String(),
            ]);

        return response()->json([
            'channel' => ['id' => $channel->id, 'display_name' => $channel->display_name],
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, ChatChannel $channel): JsonResponse
    {
        $user = $request->user();
        if (! $channel->members()->where('users.id', $user->id)->exists()) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $data = $request->validate(['message' => 'required|string|max:10000']);

        $msg = ChatMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $user->id,
            'message' => $data['message'],
        ]);
        $msg->load('sender:id,name,email');

        return response()->json([
            'id' => $msg->id,
            'sender_id' => $msg->sender_id,
            'sender_name' => $msg->sender->name,
            'message' => $msg->message,
            'created_at' => $msg->created_at->toIso8601String(),
        ]);
    }

    /**
     * Créer une conversation directe avec un contact autorisé (siège/compta/support).
     */
    public function createDirect(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate(['user_id' => 'required|exists:users,id']);
        $otherId = (int) $data['user_id'];
        if ($otherId === $user->id) {
            return response()->json(['error' => 'Impossible de créer une conversation avec soi-même.'], 422);
        }

        $other = User::findOrFail($otherId);
        $allowed = $other->canAccessAdmin() || $other->isComptable();
        if (! $allowed) {
            return response()->json(['error' => 'Contact non autorisé.'], 403);
        }

        $existing = ChatChannel::where('type', ChatChannel::TYPE_DIRECT)
            ->whereHas('channelMembers', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('channelMembers', fn ($q) => $q->where('user_id', $otherId))
            ->first();
        if ($existing) {
            return response()->json(['channel_id' => $existing->id]);
        }

        $channel = ChatChannel::create(['type' => ChatChannel::TYPE_DIRECT, 'created_by' => $user->id]);
        $channel->channelMembers()->create(['user_id' => $user->id]);
        $channel->channelMembers()->create(['user_id' => $otherId]);

        return response()->json(['channel_id' => $channel->id]);
    }
}

