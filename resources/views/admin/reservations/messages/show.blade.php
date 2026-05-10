@extends('layouts.admin-v2')

@section('title', $message->subject)

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Message</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.reservations.messages') }}">Messages</a></li>
                    <li class="breadcrumb-item active">Lecture</li>
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
        {{-- Barre latérale gauche (identique à la liste) --}}
        <div class="col-md-4 col-lg-3">
            <div class="card mb-3 shadow-sm">
                <div class="card-body p-3">
                    <a href="{{ route('admin.reservations.messages.create') }}" class="btn btn-danger w-100 rounded-3 py-2" style="background-color: #e85347;">
                        <i class="bx bx-edit-alt me-1"></i> Compose
                    </a>
                    <div class="mail-list mt-4">
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'inbox']) }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                            <i class="bx bx-inbox me-2 font-size-18"></i>
                            <span>Inbox</span>
                            <span class="ms-auto">({{ $inboxCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'unread']) }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                            <i class="bx bx-envelope me-2 font-size-18"></i>
                            <span>Non lus</span>
                            <span class="ms-auto">({{ $unreadCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'starred']) }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                            <i class="bx bx-star me-2 font-size-18"></i>
                            <span>Starred</span>
                            <span class="ms-auto">({{ $starredCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'important']) }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                            <i class="bx bx-diamond me-2 font-size-18"></i>
                            <span>Important</span>
                            <span class="ms-auto">({{ $importantCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'draft']) }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                            <i class="bx bx-file me-2 font-size-18"></i>
                            <span>Draft</span>
                            <span class="ms-auto">({{ $draftCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'sent']) }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                            <i class="bx bx-send me-2 font-size-18"></i>
                            <span>Sent Mail</span>
                            <span class="ms-auto">({{ $sentCount }})</span>
                        </a>
                        <a href="{{ route('admin.reservations.messages', ['folder' => 'trash']) }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                            <i class="bx bx-trash me-2 font-size-18"></i>
                            <span>Trash</span>
                            <span class="ms-auto">({{ $trashCount }})</span>
                        </a>
                    </div>
                    <h6 class="mt-4 mb-2 fw-semibold">Labels</h6>
                    <div class="mail-list">
                        @foreach($labels as $label)
                            <a href="{{ route('admin.reservations.messages', ['label_id' => $label->id]) }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                                <span class="rounded-circle me-2 d-inline-block" style="width:10px;height:10px;background:var(--bs-{{ $label->color }}, #0d6efd);"></span>
                                <span>{{ $label->name }}</span>
                            </a>
                        @endforeach
                    </div>
                    <h6 class="mt-4 mb-2 fw-semibold">Agences</h6>
                    <div class="mail-list">
                        <a href="{{ route('admin.reservations.messages') }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                            <i class="bx bx-building-house me-2"></i> Toutes
                        </a>
                        @foreach($branches as $b)
                            <a href="{{ route('admin.reservations.messages', ['branch_id' => $b->id]) }}" class="d-flex align-items-center py-2 px-2 rounded text-body">
                                <i class="bx bx-right-arrow-circle me-2"></i>{{ $b->name }}{!! $b->city ? ' <small class="text-muted">(' . e($b->city) . ')</small>' : '' !!}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Zone lecture : barre d'outils + en-tête + corps --}}
        <div class="col-md-8 col-lg-9">
            <div class="card shadow-sm">
                {{-- Barre d'outils (actions sur le message) --}}
                <div class="btn-toolbar msg-toolbar p-3 border-bottom flex-wrap gap-2" role="toolbar">
                    <a href="{{ route('admin.reservations.messages') }}" class="btn btn-primary btn-sm" title="Retour à la liste"><i class="bx bx-arrow-back"></i></a>
                    <div class="btn-group btn-group-sm">
                        <form action="{{ route('admin.reservations.messages.star', $message->id) }}" method="post" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary" title="{{ $message->isStarredBy($user) ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                                <i class="bx {{ $message->isStarredBy($user) ? 'bxs-star' : 'bx-star' }}"></i>
                            </button>
                        </form>
                        <form action="{{ route('admin.reservations.messages.trash', $message->id) }}" method="post" class="d-inline" onsubmit="return confirm('Déplacer dans la corbeille ?');">
                            @csrf
                            <button type="submit" class="btn btn-primary" title="Corbeille"><i class="bx bx-trash"></i></button>
                        </form>
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
                                    <form action="{{ route('admin.reservations.messages.label', $message->id) }}" method="post" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="label_id" value="{{ $label->id }}">
                                        <button type="submit" class="dropdown-item">{{ $label->name }}</button>
                                    </form>
                                </li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('admin.reservations.messages.label', $message->id) }}" method="post" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="label_id" value="">
                                    <button type="submit" class="dropdown-item">Aucun label</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <div class="btn-group btn-group-sm ms-auto">
                        <form action="{{ route('admin.reservations.messages.important', $message->id) }}" method="post" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary dropdown-toggle" title="Important"><i class="bx {{ $message->is_important ? 'bxs-diamond' : 'bx-diamond' }}"></i></button>
                        </form>
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">More <i class="bx bx-dots-vertical ms-1"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('admin.reservations.messages', ['folder' => 'inbox']) }}">Inbox</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.reservations.messages', ['folder' => 'starred']) }}">Favoris</a></li>
                        </ul>
                    </div>
                </div>

                {{-- En-tête du message (expéditeur + sujet) --}}
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0 rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width:48px;height:48px;">
                            <i class="bx bx-building-house font-size-24"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <strong class="font-size-15">{{ $message->fromBranch?->name ?? '—' }}</strong>
                                @if($message->fromBranch?->email)
                                    <span class="text-muted small">{{ $message->fromBranch->email }}</span>
                                @endif
                                <span class="text-muted small ms-auto">{{ $message->created_at->format('d M Y à H:i') }}</span>
                            </div>
                            <h5 class="mt-2 mb-0 font-size-16">{{ $message->subject }}</h5>
                            @if($message->label)
                                <span class="badge bg-{{ $message->label->color }} mt-2">{{ $message->label->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Corps du message --}}
                <div class="card-body message-body">
                    {!! nl2br(e($message->body)) !!}
                </div>
            </div>
        </div>
    </div>

    <style>
        .mail-list a { text-decoration: none; transition: background .15s; }
        .mail-list a:hover { background: rgba(0,0,0,.05); }
        .message-body { white-space: pre-wrap; word-wrap: break-word; }
        .msg-toolbar .bx { font-size: 0.875rem; }
        .msg-toolbar .btn-group:not(:first-of-type) { border-left: 1px solid rgba(255,255,255,.4); margin-left: 0.5rem; padding-left: 0.5rem; }
        .msg-toolbar .btn-group > * + * { margin-left: 0.5rem; }
        .msg-toolbar > a.btn { margin-right: 0.25rem; }
    </style>
@endsection
