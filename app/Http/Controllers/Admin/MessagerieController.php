<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatChannel;
use App\Models\ChatChannelMember;
use App\Models\ChatMessage;
use App\Models\Reservation;
use App\Services\BranchScopeService;
use App\Services\View\AgentPortalLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessagerieController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (AgentPortalLayout::shouldUse($request->user()) && \Illuminate\Support\Facades\Route::has('agent.messagerie.index')) {
            return redirect()->route('agent.messagerie.index', $request->query());
        }

        $user = $request->user();
        $reservationQuery = Reservation::query()->orderByDesc('id')->limit(200);
        $this->branchScope->scopeReservations($reservationQuery, $user);
        $reservations = $reservationQuery->get(['id', 'client_first_name', 'client_last_name']);
        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
        return view('admin.messagerie.index', compact('reservations', 'users'));
    }

    /**
     * Liste des canaux accessibles à l'utilisateur (pour la sidebar).
     */
    public function channels(Request $request): JsonResponse
    {
        $user = $request->user();
        $channelIds = ChatChannelMember::where('user_id', $user->id)->pluck('channel_id');
        $channels = ChatChannel::with(['branch', 'reservation', 'members' => fn ($q) => $q->with('user:id,name,email')])
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
                    'branch' => $ch->branch ? ['id' => $ch->branch->id, 'name' => $ch->branch->name] : null,
                    'reservation_id' => $ch->reservation_id,
                    'messages_count' => $ch->messages_count,
                    'unread' => $unread,
                    'updated_at' => $ch->updated_at?->toIso8601String(),
                ];
            });

        return response()->json(['channels' => $channels]);
    }

    /**
     * Messages d'un canal.
     */
    public function messages(Request $request, ChatChannel $channel): JsonResponse
    {
        $user = $request->user();
        if (! $channel->members()->where('users.id', $user->id)->exists()) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        ChatChannelMember::where('channel_id', $channel->id)->where('user_id', $user->id)->update(['last_read_at' => now()]);
        $messages = $channel->messages()->with('sender:id,name,email')->orderBy('created_at')->get()->map(fn ($m) => [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'sender_name' => $m->sender->name ?? '',
            'sender_email' => $m->sender->email ?? '',
            'message' => $m->message,
            'attachment' => $m->attachment,
            'created_at' => $m->created_at->toIso8601String(),
        ]);
        return response()->json(['channel' => ['id' => $channel->id, 'display_name' => $channel->display_name], 'messages' => $messages]);
    }

    /**
     * Envoyer un message.
     */
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
            'sender_email' => $msg->sender->email,
            'message' => $msg->message,
            'created_at' => $msg->created_at->toIso8601String(),
        ]);
    }

    /**
     * Créer un canal (direct ou réservation).
     */
    public function createChannel(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'type' => 'required|in:direct,branch,global,reservation',
            'user_id' => 'required_if:type,direct|nullable|exists:users,id',
            'reservation_id' => 'required_if:type,reservation|nullable|exists:reservations,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        if ($data['type'] === ChatChannel::TYPE_DIRECT && ! empty($data['user_id'])) {
            $otherId = (int) $data['user_id'];
            if ($otherId === $user->id) {
                return response()->json(['error' => 'Impossible de créer une conversation avec soi-même.'], 422);
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
        if ($data['type'] === ChatChannel::TYPE_RESERVATION && ! empty($data['reservation_id'])) {
            $reservation = Reservation::find($data['reservation_id']);
            if (! $reservation) {
                return response()->json(['error' => 'Réservation introuvable.'], 404);
            }
            $branchIds = $this->branchScope->visibleBranchIds($user);
            if ($branchIds !== null && ! in_array($reservation->branch_id, $branchIds, true)) {
                return response()->json(['error' => 'Accès non autorisé à cette réservation.'], 403);
            }
            $existing = ChatChannel::where('type', ChatChannel::TYPE_RESERVATION)->where('reservation_id', $reservation->id)->first();
            if ($existing) {
                if (! $existing->members()->where('users.id', $user->id)->exists()) {
                    $existing->channelMembers()->create(['user_id' => $user->id]);
                }
                return response()->json(['channel_id' => $existing->id]);
            }
            $channel = ChatChannel::create([
                'type' => ChatChannel::TYPE_RESERVATION,
                'reservation_id' => $reservation->id,
                'created_by' => $user->id,
            ]);
            $channel->channelMembers()->create(['user_id' => $user->id]);
            if ($reservation->agent_id && $reservation->agent_id !== $user->id) {
                $channel->channelMembers()->firstOrCreate(['user_id' => $reservation->agent_id]);
            }
            if ($reservation->sales_manager_id && $reservation->sales_manager_id !== $user->id) {
                $channel->channelMembers()->firstOrCreate(['user_id' => $reservation->sales_manager_id]);
            }
            return response()->json(['channel_id' => $channel->id]);
        }
        return response()->json(['error' => 'Paramètres invalides.'], 422);
    }
}
