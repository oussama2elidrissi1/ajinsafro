<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\MessageLabel;
use App\Models\MessageRead;
use App\Models\MessageStar;
use App\Models\ReservationMessage;
use App\Services\BranchScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ReservationMessageController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    /**
     * Boîte de réception : Inbox / Starred / Important / Draft / Sent / Trash / Labels / Agences.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $folder = $request->query('folder', 'inbox');
        $branchId = $request->query('branch_id');
        $labelId = $request->query('label_id');

        $branches = $this->getVisibleBranches($user);
        $branchIds = $branches->pluck('id')->toArray();
        $labels = MessageLabel::orderBy('name')->get();

        $baseQuery = fn () => ReservationMessage::query()
            ->when(!empty($branchIds), fn ($q) => $q->whereIn('from_branch_id', $branchIds));

        $query = ReservationMessage::query()
            ->with(['fromBranch', 'label'])
            ->orderByDesc('created_at');

        if (!empty($branchIds)) {
            $query->whereIn('from_branch_id', $branchIds);
        }
        if ($branchId && in_array((int) $branchId, $branchIds, true)) {
            $query->where('from_branch_id', (int) $branchId);
        }
        if ($labelId) {
            $query->where('label_id', (int) $labelId);
        }

        if ($folder === 'unread') {
            $readIds = MessageRead::where('user_id', $user->id)->pluck('message_id');
            $query->whereNotIn('id', $readIds)->where('status', ReservationMessage::STATUS_SENT);
        } elseif ($folder === 'starred') {
            $starredIds = MessageStar::where('user_id', $user->id)->pluck('message_id');
            $query->whereIn('id', $starredIds);
        } elseif ($folder === 'important') {
            $query->where('is_important', true);
        } elseif ($folder === 'draft') {
            $query->where('status', ReservationMessage::STATUS_DRAFT);
        } elseif ($folder === 'sent') {
            $query->where('status', ReservationMessage::STATUS_SENT);
        } elseif ($folder === 'trash') {
            $query->where('status', ReservationMessage::STATUS_TRASH);
        } else {
            $query->where('status', ReservationMessage::STATUS_SENT);
        }

        $messages = $query->paginate(20)->withQueryString();

        $inboxCount = $baseQuery()->where('status', ReservationMessage::STATUS_SENT)->count();
        $unreadCount = $baseQuery()->where('status', ReservationMessage::STATUS_SENT)
            ->whereNotIn('id', MessageRead::where('user_id', $user->id)->pluck('message_id'))->count();
        $starredCount = $baseQuery()->whereIn('id', MessageStar::where('user_id', $user->id)->pluck('message_id'))->count();
        $importantCount = $baseQuery()->where('is_important', true)->count();
        $draftCount = $baseQuery()->where('status', ReservationMessage::STATUS_DRAFT)->count();
        $sentCount = $baseQuery()->where('status', ReservationMessage::STATUS_SENT)->count();
        $trashCount = $baseQuery()->where('status', ReservationMessage::STATUS_TRASH)->count();

        return view('admin.reservations.messages.index', [
            'messages' => $messages,
            'branches' => $branches,
            'labels' => $labels,
            'folder' => $folder,
            'branchId' => $branchId ? (int) $branchId : null,
            'labelId' => $labelId ? (int) $labelId : null,
            'inboxCount' => $inboxCount,
            'unreadCount' => $unreadCount,
            'starredCount' => $starredCount,
            'importantCount' => $importantCount,
            'draftCount' => $draftCount,
            'sentCount' => $sentCount,
            'trashCount' => $trashCount,
            'user' => $user,
        ]);
    }

    public function toggleStar(Request $request, int $message): RedirectResponse
    {
        $msg = ReservationMessage::findOrFail($message);
        $this->authorizeMessage($request->user(), $msg);
        $star = MessageStar::where('user_id', $request->user()->id)->where('message_id', $msg->id)->first();
        if ($star) {
            $star->delete();
        } else {
            MessageStar::create(['user_id' => $request->user()->id, 'message_id' => $msg->id]);
        }
        return redirect()->back()->with('success', $star ? 'Retiré des favoris.' : 'Ajouté aux favoris.');
    }

    public function moveToTrash(Request $request, int $message): RedirectResponse
    {
        $msg = ReservationMessage::findOrFail($message);
        $this->authorizeMessage($request->user(), $msg);
        $msg->update(['status' => ReservationMessage::STATUS_TRASH]);
        return redirect()->back()->with('success', 'Message déplacé dans la corbeille.');
    }

    public function setLabel(Request $request, int $message): RedirectResponse
    {
        $msg = ReservationMessage::findOrFail($message);
        $this->authorizeMessage($request->user(), $msg);
        $labelId = $request->input('label_id') ? (int) $request->input('label_id') : null;
        $msg->update(['label_id' => $labelId]);
        return redirect()->back()->with('success', 'Label mis à jour.');
    }

    public function setImportant(Request $request, int $message): RedirectResponse
    {
        $msg = ReservationMessage::findOrFail($message);
        $this->authorizeMessage($request->user(), $msg);
        $msg->update(['is_important' => !$msg->is_important]);
        return redirect()->back()->with('success', $msg->is_important ? 'Marqué important.' : 'Non important.');
    }

    private function authorizeMessage($user, ReservationMessage $msg): void
    {
        $branches = $this->getVisibleBranches($user);
        $branchIds = $branches->pluck('id')->toArray();
        if (!empty($branchIds) && $msg->from_branch_id && !in_array($msg->from_branch_id, $branchIds, true)) {
            abort(403);
        }
    }

    /**
     * Lire un message (marquer comme lu).
     */
    public function show(Request $request, int $message): View|RedirectResponse
    {
        $msg = ReservationMessage::findOrFail($message);
        $user = $request->user();
        $branches = $this->getVisibleBranches($user);
        $branchIds = $branches->pluck('id')->toArray();

        if (!empty($branchIds) && $msg->from_branch_id && !in_array($msg->from_branch_id, $branchIds, true)) {
            abort(403);
        }

        MessageRead::firstOrCreate(
            ['user_id' => $user->id, 'message_id' => $msg->id],
            ['read_at' => now()]
        );

        $msg->load(['fromBranch', 'label']);
        $labels = MessageLabel::orderBy('name')->get();
        $baseQuery = fn () => ReservationMessage::query()
            ->when(!empty($branchIds), fn ($q) => $q->whereIn('from_branch_id', $branchIds));

        return view('admin.reservations.messages.show', [
            'message' => $msg,
            'branches' => $branches,
            'labels' => $labels,
            'user' => $user,
            'inboxCount' => $baseQuery()->where('status', ReservationMessage::STATUS_SENT)->count(),
            'unreadCount' => $baseQuery()->where('status', ReservationMessage::STATUS_SENT)
                ->whereNotIn('id', MessageRead::where('user_id', $user->id)->pluck('message_id'))->count(),
            'starredCount' => $baseQuery()->whereIn('id', MessageStar::where('user_id', $user->id)->pluck('message_id'))->count(),
            'importantCount' => $baseQuery()->where('is_important', true)->count(),
            'draftCount' => $baseQuery()->where('status', ReservationMessage::STATUS_DRAFT)->count(),
            'sentCount' => $baseQuery()->where('status', ReservationMessage::STATUS_SENT)->count(),
            'trashCount' => $baseQuery()->where('status', ReservationMessage::STATUS_TRASH)->count(),
        ]);
    }

    /**
     * Formulaire nouveau message (Compose).
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        $branches = $this->getVisibleBranches($user);
        return view('admin.reservations.messages.create', ['branches' => $branches]);
    }

    /**
     * Enregistrer un nouveau message (envoyé par l'agence de l'utilisateur).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $branchIds = $this->branchScope->visibleBranchIds($user);
        // null = peut tout voir (Admin) ; [] = ne peut voir aucune agence
        if ($branchIds !== null && empty($branchIds)) {
            abort(403, 'Vous devez être rattaché à une agence pour envoyer un message.');
        }
        if ($branchIds !== null && $user->branch_id && !in_array($user->branch_id, $branchIds, true)) {
            abort(403, 'Accès non autorisé.');
        }
        $fromBranchId = $user->branch_id
            ?? ($branchIds[0] ?? Branch::active()->orderBy('name')->value('id'));
        if (!$fromBranchId) {
            abort(403, 'Aucune agence disponible pour envoyer un message.');
        }

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
        ]);

        ReservationMessage::create([
            'from_branch_id' => $fromBranchId,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'status' => ReservationMessage::STATUS_SENT,
        ]);

        return redirect()
            ->route('admin.reservations.messages')
            ->with('success', 'Message envoyé.');
    }

    private function getVisibleBranches($user)
    {
        if ($this->branchScope->canSeeAllBranches($user)) {
            return Branch::active()->orderBy('type')->orderBy('name')->get();
        }
        $ids = $this->branchScope->visibleBranchIds($user);
        if (empty($ids)) {
            return collect();
        }
        return Branch::whereIn('id', $ids)->orderBy('name')->get();
    }
}
