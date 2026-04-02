<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessagerieController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $folder = (string) $request->query('folder', 'inbox');
        $search = trim((string) $request->query('q', ''));

        $messages = Message::query()
            ->with(['sender:id,name,email', 'recipient:id,name,email'])
            ->when($folder === 'inbox', function (Builder $query) use ($user) {
                $query->where('recipient_id', $user->id)
                    ->where('folder_recipient', 'inbox');
            })
            ->when($folder === 'sent', function (Builder $query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->whereNull('folder_sender');
            })
            ->when($folder === 'trash', function (Builder $query) use ($user) {
                $query->where(function (Builder $trashQuery) use ($user) {
                    $trashQuery->where(function (Builder $recipientTrash) use ($user) {
                        $recipientTrash->where('recipient_id', $user->id)
                            ->where('folder_recipient', 'trash');
                    })->orWhere(function (Builder $senderTrash) use ($user) {
                        $senderTrash->where('sender_id', $user->id)
                            ->where('folder_sender', 'trash');
                    });
                });
            })
            ->when($folder === 'drafts', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('subject', 'like', '%'.$search.'%')
                        ->orWhere('preview', 'like', '%'.$search.'%')
                        ->orWhereHas('sender', fn (Builder $sender) => $sender->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $unreadCount = Message::query()
            ->where('recipient_id', $user->id)
            ->where('folder_recipient', 'inbox')
            ->where('read', false)
            ->count();

        $rangeLabel = $messages->total() > 0
            ? sprintf('%d-%d sur %d', $messages->firstItem(), $messages->lastItem(), $messages->total())
            : '0-0 sur 0';

        $contacts = User::query()
            ->where('id', '!=', $user->id)
            ->where(function (Builder $query) {
                $query->role(['agent', 'manager', 'admin'])
                    ->orWhereHas('roles', fn (Builder $roles) => $roles->whereIn('name', ['Agent', 'Manager', 'Admin', 'Super Admin', 'Admin Siège']));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function (User $contact) {
                $contact->role = (string) ($contact->getRoleNames()->first() ?? 'agent');

                return $contact;
            });

        $routeBase = $this->resolveRouteBase($request);

        return view('agent.messagerie.index', compact(
            'messages',
            'folder',
            'search',
            'unreadCount',
            'rangeLabel',
            'contacts',
            'routeBase'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        Message::create([
            'sender_id' => (int) $request->user()->id,
            'recipient_id' => (int) $validated['recipient_id'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'folder_sender' => null,
            'folder_recipient' => 'inbox',
            'read' => false,
            'starred' => false,
        ]);

        return back()->with('status', 'Message envoyé avec succès.');
    }

    public function show(Request $request, Message $message): View
    {
        $userId = (int) $request->user()->id;
        abort_unless($message->sender_id === $userId || $message->recipient_id === $userId, 403);

        if (! $message->read && $message->recipient_id === $userId) {
            $message->update([
                'read' => true,
                'read_at' => now(),
            ]);
            $message->refresh();
        }

        $message->load(['sender:id,name,email', 'recipient:id,name,email']);
        $replyRecipient = $message->sender_id === $userId ? $message->recipient : $message->sender;
        $routeBase = $this->resolveRouteBase($request);

        return view('agent.messagerie.show', [
            'message' => $message,
            'replyRecipient' => $replyRecipient,
            'routeBase' => $routeBase,
        ]);
    }

    public function markRead(Request $request, Message $message): RedirectResponse
    {
        abort_unless($message->recipient_id === (int) $request->user()->id, 403);

        $message->update([
            'read' => true,
            'read_at' => now(),
        ]);

        return back();
    }

    public function toggleStar(Request $request, Message $message): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        abort_unless($message->sender_id === $userId || $message->recipient_id === $userId, 403);

        $message->update([
            'starred' => ! $message->starred,
        ]);

        return back();
    }

    public function destroy(Request $request, Message $message): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        abort_unless($message->sender_id === $userId || $message->recipient_id === $userId, 403);

        $updates = [];
        if ($message->recipient_id === $userId) {
            $updates['folder_recipient'] = 'trash';
        }
        if ($message->sender_id === $userId) {
            $updates['folder_sender'] = 'trash';
        }

        if ($updates !== []) {
            $message->update($updates);
        }

        return back()->with('status', 'Message déplacé ou supprimé.');
    }

    private function resolveRouteBase(Request $request): string
    {
        $routeName = (string) $request->route()?->getName();

        return str_starts_with($routeName, 'admin.')
            ? 'admin.messagerie'
            : 'agent.messagerie';
    }
}
