@extends('layouts.admin-v6')

@section('title', 'Messages')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Messages</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
                    <li class="breadcrumb-item active">Messages</li>
                </ol>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Barre latérale gauche (style email inbox) --}}
        <div class="col-md-4 col-lg-3">
            <div class="card mb-3 shadow-sm">
                <div class="card-body p-3">
                    <a href="{{ route('admin.reservations.messages.create') }}" class="btn btn-danger w-100 rounded-3 py-2" style="background-color: #e85347;">
                        <i class="bx bx-edit-alt me-1"></i> Compose
                    </a>
                    <div class="mail-list mt-4">
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'inbox']) }}" class="d-flex align-items-center py-2 px-2 rounded {{ ($folder ?? '') === 'inbox' && !request('branch_id') && !request('label_id') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                            <i class="bx bx-inbox me-2 font-size-18"></i>
                            <span>Inbox</span>
                            <span class="ms-auto">({{ $inboxCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'unread']) }}" class="d-flex align-items-center py-2 px-2 rounded {{ ($folder ?? '') === 'unread' ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                            <i class="bx bx-envelope me-2 font-size-18"></i>
                            <span>Non lus</span>
                            <span class="ms-auto">({{ $unreadCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'starred']) }}" class="d-flex align-items-center py-2 px-2 rounded {{ ($folder ?? '') === 'starred' ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                            <i class="bx bx-star me-2 font-size-18"></i>
                            <span>Starred</span>
                            <span class="ms-auto">({{ $starredCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'important']) }}" class="d-flex align-items-center py-2 px-2 rounded {{ ($folder ?? '') === 'important' ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                            <i class="bx bx-diamond me-2 font-size-18"></i>
                            <span>Important</span>
                            <span class="ms-auto">({{ $importantCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'draft']) }}" class="d-flex align-items-center py-2 px-2 rounded {{ ($folder ?? '') === 'draft' ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                            <i class="bx bx-file me-2 font-size-18"></i>
                            <span>Draft</span>
                            <span class="ms-auto">({{ $draftCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'sent']) }}" class="d-flex align-items-center py-2 px-2 rounded {{ ($folder ?? '') === 'sent' ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                            <i class="bx bx-send me-2 font-size-18"></i>
                            <span>Sent Mail</span>
                            <span class="ms-auto">({{ $sentCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'trash']) }}" class="d-flex align-items-center py-2 px-2 rounded {{ ($folder ?? '') === 'trash' ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                            <i class="bx bx-trash me-2 font-size-18"></i>
                            <span>Trash</span>
                            <span class="ms-auto">({{ $trashCount }})</span>
                        </a>
                    </div>
                    <h6 class="mt-4 mb-2 fw-semibold">Labels</h6>
                    <div class="mail-list">
                        @foreach($labels as $label)
                            <a href="{{ route('admin.reservations.messages', ['label_id' => $label->id]) }}" class="d-flex align-items-center py-2 px-2 rounded {{ (int)request('label_id') === (int)$label->id ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                                <span class="rounded-circle me-2 d-inline-block" style="width:10px;height:10px;background:var(--bs-{{ $label->color }}, #0d6efd);"></span>
                                <span>{{ $label->name }}</span>
                            </a>
                        @endforeach
                    </div>
                    <h6 class="mt-4 mb-2 fw-semibold">Agences</h6>
                    <div class="mail-list">
                        <a href="{{ route('admin.reservations.messages') }}" class="d-flex align-items-center py-2 px-2 rounded {{ !request('branch_id') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                            <i class="bx bx-building-house me-2"></i> Toutes
                        </a>
                        @foreach($branches as $b)
                            <a href="{{ route('admin.reservations.messages', ['branch_id' => $b->id]) }}" class="d-flex align-items-center py-2 px-2 rounded {{ (int)request('branch_id') === (int)$b->id ? 'bg-primary bg-opacity-10 text-primary' : 'text-body' }}">
                                <i class="bx bx-right-arrow-circle me-2"></i>{{ $b->name }}{!! $b->city ? ' <small class="text-muted">(' . e($b->city) . ')</small>' : '' !!}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Zone principale : barre d'outils + liste --}}
        <div class="col-md-8 col-lg-9">
            <div class="card shadow-sm">
                <div class="btn-toolbar msg-toolbar p-3 border-bottom flex-wrap gap-2" role="toolbar">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'inbox']) }}" class="btn btn-primary" title="Inbox"><i class="bx bx-inbox"></i></a>
                        <button type="button" class="btn btn-primary" title="Important" disabled><i class="bx bx-error-circle"></i></button>
                        <button type="button" class="btn btn-primary" title="Corbeille" disabled><i class="bx bx-trash"></i></button>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" title="Dossier"><i class="bx bx-folder"></i> <i class="bx bx-chevron-down ms-1"></i></button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.reservations.messages', ['folder' => 'inbox']) }}">Inbox</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reservations.messages', ['folder' => 'trash']) }}">Trash</a></li>
                        </ul>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" title="Label"><i class="bx bx-purchase-tag"></i> <i class="bx bx-chevron-down ms-1"></i></button>
                        <ul class="dropdown-menu">
                            @foreach($labels as $label)
                                <li>
                                    <form action="{{ route('admin.reservations.messages', ['label_id' => $label->id]) }}" method="get" class="d-inline">
                                        <button type="submit" class="dropdown-item">{{ $label->name }}</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="btn-group btn-group-sm ms-auto">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">More <i class="bx bx-dots-vertical ms-1"></i></button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.reservations.messages', ['folder' => 'unread']) }}">Non lus</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reservations.messages', ['folder' => 'important']) }}">Important</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reservations.messages', ['folder' => 'starred']) }}">Favoris</a></li>
                        </ul>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($messages as $msg)
                        @php
                            $isUnread = !$msg->isReadBy($user);
                            $isStarred = $msg->isStarredBy($user);
                        @endphp
                        <div class="list-group-item list-group-item-action d-flex align-items-start py-3 {{ $isUnread ? 'bg-info bg-opacity-10' : '' }}" data-message-id="{{ $msg->id }}">
                            <div class="form-check me-2 mt-1">
                                <input class="form-check-input msg-checkbox" type="checkbox" value="{{ $msg->id }}" aria-label="Sélectionner">
                            </div>
                            <div class="me-2 mt-1">
                                <form action="{{ route('admin.reservations.messages.star', $msg->id) }}" method="post" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link p-0 border-0 text-warning">
                                        <i class="bx {{ $isStarred ? 'bxs-star' : 'bx-star' }} font-size-18"></i>
                                    </button>
                                </form>
                            </div>
                            <a href="{{ route('admin.reservations.messages.show', $msg->id) }}" class="flex-grow-1 min-w-0 text-decoration-none text-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="{{ $isUnread ? 'fw-bold' : '' }}">
                                        {{ $msg->fromBranch?->name ?? '�?"' }}
                                    </span>
                                    <span class="text-muted small">{{ $msg->created_at->format('d M') }}</span>
                                </div>
                                <div class="mt-1">
                                    @if($msg->label)
                                        <span class="badge bg-{{ $msg->label->color }} me-2">{{ $msg->label->name }}</span>
                                    @endif
                                    {{ Str::limit($msg->subject, 70) }}
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-5">Aucun message.</div>
                    @endforelse
                </div>
                @if($messages->hasPages())
                    <div class="p-2 border-top">{{ $messages->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .mail-list a { text-decoration: none; transition: background .15s; }
        .mail-list a:hover { background: rgba(0,0,0,.05); }
        .list-group-item[data-message-id]:hover { background-color: rgba(13, 110, 253, 0.08) !important; }
        .msg-toolbar .bx { font-size: 0.875rem; }
        .msg-toolbar .btn-group:not(:first-of-type) { border-left: 1px solid rgba(255,255,255,.4); margin-left: 0.5rem; padding-left: 0.5rem; }
        .msg-toolbar .btn-group > * + * { margin-left: 0.5rem; }
        .msg-toolbar > a.btn { margin-right: 0.25rem; }
    </style>
@endsection


