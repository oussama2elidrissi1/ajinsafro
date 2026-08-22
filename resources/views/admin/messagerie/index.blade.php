@extends('layouts.admin-v6')

@section('title', 'Messagerie')
@section('page_title', 'Messagerie')

@php
    $breadcrumbs = [
        ['label' => 'Accueil', 'url' => \Illuminate\Support\Facades\Route::has('admin.dashboard.v6') ? route('admin.dashboard.v6') : (\Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin'))],
        ['label' => 'Messagerie'],
    ];
@endphp

@section('content')
    <div class="admin-v6-card p-3 p-md-4">
        @if(session('status'))
            <div class="alert alert-success py-2">{{ session('status') }}</div>
        @endif

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-grid gap-1">
                <div class="text-muted" style="font-weight:700">Messagerie interne</div>
                <div style="font-weight:800;font-size:16px">Conversations & boîte interne</div>
            </div>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-conversations" type="button" role="tab">
                    <i class="bx bx-conversation me-1"></i>Conversations
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-inbox" type="button" role="tab">
                    <i class="bx bx-envelope me-1"></i>Boîte interne
                    @if(($inboxUnreadCount ?? 0) > 0)
                        <span class="badge bg-danger ms-1">{{ $inboxUnreadCount }}</span>
                    @endif
                </button>
            </li>
        </ul>

        <div class="tab-content pt-3">
            {{-- ─── Onglet 1 : conversations temps réel (canaux) ─────────────── --}}
            <div class="tab-pane fade show active" id="pane-conversations" role="tabpanel">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newChannelModal">
                        <i class="bx bx-plus"></i>
                        <span class="ms-1">Nouvelle conversation</span>
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <div class="admin-v6-card p-3" style="height:100%">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div style="font-weight:800">Conversations</div>
                                <span class="badge bg-light text-muted">Auto</span>
                            </div>

                            <div class="mb-2">
                                <input type="search" class="form-control" placeholder="Rechercher une conversation..." id="channel-search">
                            </div>

                            <div id="channel-list" class="list-group mail-list" style="max-height: 520px; overflow:auto">
                                <div class="text-muted small">Chargement...</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="admin-v6-card p-3" style="min-height: 560px">
                            <div id="message-area-placeholder" class="text-center text-muted py-5">
                                <i class="bx bx-message-detail" style="font-size:52px"></i>
                                <p class="mt-2 mb-0">Sélectionnez une conversation ou créez-en une.</p>
                            </div>

                            <div id="message-area" class="d-none">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                    <div class="d-grid gap-1" style="min-width:0">
                                        <h5 id="channel-title" class="mb-0" style="font-weight:900"></h5>
                                        <div class="text-muted small">Historique des messages</div>
                                    </div>
                                </div>

                                <div id="messages-container" class="border rounded p-3 mb-3" style="max-height: 380px; overflow-y: auto; background:#fff"></div>

                                <div class="d-flex gap-2">
                                    <input type="text" id="message-input" class="form-control" placeholder="Écrire un message..." maxlength="10000">
                                    <button type="button" id="send-btn" class="btn btn-primary">Envoyer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Onglet 2 : boîte interne (messages portail agent) ────────── --}}
            <div class="tab-pane fade" id="pane-inbox" role="tabpanel">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newInternalMessageModal">
                        <i class="bx bx-plus"></i>
                        <span class="ms-1">Nouveau message</span>
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-7">
                        <div class="admin-v6-card p-3">
                            <div style="font-weight:800" class="mb-2">Boîte de réception</div>

                            @forelse($inboxMessages ?? [] as $inboxMessage)
                                <div class="border rounded mb-2 internal-message {{ $inboxMessage->read ? '' : 'internal-message--unread' }}" data-message-id="{{ $inboxMessage->id }}">
                                    <button type="button"
                                            class="btn w-100 text-start p-2 internal-message__head"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#internal-message-{{ $inboxMessage->id }}"
                                            data-read-url="{{ $inboxMessage->read ? '' : route('admin.messagerie.read', $inboxMessage) }}">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <div style="min-width:0">
                                                <div class="text-truncate" style="font-weight:{{ $inboxMessage->read ? '600' : '800' }}">
                                                    {{ $inboxMessage->subject ?: 'Sans sujet' }}
                                                </div>
                                                <div class="small text-muted text-truncate">
                                                    De : {{ $inboxMessage->sender->name ?? 'Utilisateur supprimé' }}
                                                </div>
                                            </div>
                                            <div class="text-end flex-shrink-0">
                                                @unless($inboxMessage->read)
                                                    <span class="badge bg-danger internal-message__unread-badge">Non lu</span>
                                                @endunless
                                                <div class="small text-muted">{{ $inboxMessage->created_at?->format('d/m/Y H:i') }}</div>
                                            </div>
                                        </div>
                                    </button>

                                    <div class="collapse" id="internal-message-{{ $inboxMessage->id }}">
                                        <div class="border-top p-3">
                                            <div class="mb-3" style="white-space:pre-wrap">{{ $inboxMessage->body }}</div>

                                            @if($inboxMessage->sender)
                                                <form method="POST" action="{{ route('admin.messagerie.store') }}" class="d-grid gap-2">
                                                    @csrf
                                                    <input type="hidden" name="recipient_id" value="{{ $inboxMessage->sender_id }}">
                                                    <input type="hidden" name="subject" value="{{ \Illuminate\Support\Str::startsWith((string) $inboxMessage->subject, 'Re:') ? $inboxMessage->subject : 'Re: '.$inboxMessage->subject }}">
                                                    <textarea name="body" rows="3" class="form-control" placeholder="Répondre à {{ $inboxMessage->sender->name }}..." required></textarea>
                                                    <div class="d-flex justify-content-end">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="bx bx-send"></i>
                                                            <span class="ms-1">Répondre</span>
                                                        </button>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small">Aucun message reçu.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="admin-v6-card p-3">
                            <div style="font-weight:800" class="mb-2">Messages envoyés</div>

                            @forelse($sentMessages ?? [] as $sentMessage)
                                <div class="border rounded mb-2 p-2">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div style="min-width:0">
                                            <div class="text-truncate" style="font-weight:600">{{ $sentMessage->subject ?: 'Sans sujet' }}</div>
                                            <div class="small text-muted text-truncate">À : {{ $sentMessage->recipient->name ?? 'Utilisateur supprimé' }}</div>
                                        </div>
                                        <div class="small text-muted flex-shrink-0">{{ $sentMessage->created_at?->format('d/m/Y H:i') }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small">Aucun message envoyé.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal : nouvelle conversation (canaux) --}}
    <div class="modal fade" id="newChannelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-direct" type="button" role="tab">Direct</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reservation" type="button" role="tab">Réservation</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="tab-direct" role="tabpanel">
                            <label class="form-label">Utilisateur</label>
                            <select id="new-direct-user" class="form-select">
                                <option value="">- Choisir -</option>
                                @foreach($users ?? [] as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="tab-pane fade" id="tab-reservation" role="tabpanel">
                            <label class="form-label">Réservation</label>
                            <select id="new-reservation-id" class="form-select">
                                <option value="">- Choisir -</option>
                                @foreach($reservations ?? [] as $r)
                                    <option value="{{ $r->id }}">#{{ $r->id }} - {{ $r->client_first_name }} {{ $r->client_last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="create-channel-btn">Ouvrir</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal : nouveau message interne --}}
    <div class="modal fade" id="newInternalMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.messagerie.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau message interne</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">
                        <div>
                            <label class="form-label">Destinataire</label>
                            <select name="recipient_id" class="form-select" required>
                                <option value="">- Choisir -</option>
                                @foreach($users ?? [] as $u)
                                    @continue($u->id === auth()->id())
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Sujet</label>
                            <input type="text" name="subject" class="form-control" maxlength="255" required>
                        </div>
                        <div>
                            <label class="form-label">Message</label>
                            <textarea name="body" rows="5" class="form-control" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-send"></i>
                            <span class="ms-1">Envoyer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .internal-message--unread { border-color: #0ea5e9 !important; background: #f0f9ff; }
        .internal-message__head:focus { box-shadow: none; }
    </style>

    @push('scripts')
        <script>
            (function () {
                'use strict';

                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const $list = document.getElementById('channel-list');
                const $search = document.getElementById('channel-search');

                const $placeholder = document.getElementById('message-area-placeholder');
                const $area = document.getElementById('message-area');
                const $title = document.getElementById('channel-title');
                const $messagesContainer = document.getElementById('messages-container');
                const $messageInput = document.getElementById('message-input');
                const $sendBtn = document.getElementById('send-btn');

                const fetchChannelsUrl = '{{ url("admin/messagerie/channels") }}';
                const createChannelUrl = '{{ url("admin/messagerie/channels") }}';

                let currentChannelId = null;
                let currentChannelName = '';
                let allChannels = [];

                function escapeHtml(s) {
                    const div = document.createElement('div');
                    div.textContent = s == null ? '' : String(s);
                    return div.innerHTML;
                }

                function renderChannels(channels) {
                    $list.innerHTML = '';

                    if (!channels.length) {
                        $list.innerHTML = '<div class="text-muted small">Aucune conversation.</div>';
                        return;
                    }

                    channels.forEach(ch => {
                        const a = document.createElement('a');
                        a.href = 'javascript:void(0);';
                        a.className = 'list-group-item list-group-item-action';
                        const unread = ch.unread > 0 ? ' <span class="badge bg-danger">' + ch.unread + '</span>' : '';
                        a.innerHTML = '<div style="font-weight:800">' + escapeHtml(ch.name || 'Conversation') + unread + '</div>'
                            + '<div class="small text-muted">' + escapeHtml(ch.last_message_preview || '') + '</div>';
                        a.addEventListener('click', function(){ selectChannel(ch.id, ch.name || 'Conversation'); });
                        $list.appendChild(a);
                    });
                }

                function fetchChannels() {
                    fetch(fetchChannelsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(r => {
                            if (!r.ok) return r.json().then(d => { throw new Error(d.error || 'Erreur chargement'); });
                            return r.json();
                        })
                        .then(data => {
                            allChannels = (data.channels || []);
                            applyChannelFilter();
                        })
                        .catch(() => {
                            $list.innerHTML = '<div class="text-danger small">Erreur chargement des conversations.</div>';
                        });
                }

                function applyChannelFilter() {
                    const q = ($search.value || '').trim().toLowerCase();
                    if (!q) { renderChannels(allChannels); return; }
                    renderChannels(allChannels.filter(c => String(c.name || '').toLowerCase().includes(q)));
                }

                function selectChannel(channelId, name) {
                    currentChannelId = channelId;
                    currentChannelName = name || 'Conversation';

                    $title.textContent = currentChannelName;
                    $placeholder.classList.add('d-none');
                    $area.classList.remove('d-none');

                    loadMessages();
                }

                function loadMessages() {
                    if (!currentChannelId) return;
                    const url = '{{ url("admin/messagerie/channels") }}/' + currentChannelId + '/messages';

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(r => {
                            if (!r.ok) return r.json().then(d => { throw new Error(d.error || 'Erreur chargement'); });
                            return r.json();
                        })
                        .then(data => {
                            $messagesContainer.innerHTML = (data.messages || []).map(m => {
                                const time = new Date(m.created_at).toLocaleString('fr-FR');
                                return '<div class="mb-2">'
                                    + '<strong>' + escapeHtml(m.sender_name) + '</strong> '
                                    + '<small class="text-muted">' + escapeHtml(time) + '</small><br>'
                                    + '<span class="text-break">' + escapeHtml(m.message) + '</span>'
                                    + '</div>';
                            }).join('');
                            $messagesContainer.scrollTop = $messagesContainer.scrollHeight;
                        })
                        .catch(() => {});
                }

                function sendMessage() {
                    const text = ($messageInput.value || '').trim();
                    if (!text || !currentChannelId) return;

                    const sendUrl = '{{ url("admin/messagerie/channels") }}/' + currentChannelId + '/messages';
                    fetch(sendUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ message: text })
                    })
                        .then(r => {
                            if (!r.ok) {
                                return r.json().then(data => { throw new Error(data.message || data.error || ('Erreur ' + r.status)); });
                            }
                            return r.json();
                        })
                        .then(() => {
                            $messageInput.value = '';
                            loadMessages();
                            fetchChannels();
                        })
                        .catch(err => {
                            $messagesContainer.insertAdjacentHTML('beforeend', '<div class="text-danger small mt-2">' + escapeHtml(err.message || 'Erreur lors de l\'envoi du message.') + '</div>');
                        });
                }

                $sendBtn.addEventListener('click', sendMessage);
                $messageInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
                });

                $search.addEventListener('input', applyChannelFilter);

                document.getElementById('create-channel-btn').addEventListener('click', function() {
                    const directUser = document.getElementById('new-direct-user').value;
                    const reservationId = document.getElementById('new-reservation-id').value;
                    const activeTab = document.querySelector('#newChannelModal .nav-link.active');
                    const type = activeTab && activeTab.getAttribute('data-bs-target') === '#tab-reservation' ? 'reservation' : 'direct';

                    const body = { type };
                    if (type === 'direct' && directUser) body.user_id = parseInt(directUser, 10);
                    if (type === 'reservation' && reservationId) body.reservation_id = parseInt(reservationId, 10);

                    fetch(createChannelUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: JSON.stringify(body)
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.channel_id) {
                                bootstrap.Modal.getInstance(document.getElementById('newChannelModal')).hide();
                                fetchChannels();
                                setTimeout(function() {
                                    const name = type === 'reservation'
                                        ? ('Réservation #' + (body.reservation_id || ''))
                                        : (document.querySelector('#new-direct-user option:checked')?.textContent || 'Conversation');
                                    selectChannel(data.channel_id, name);
                                }, 300);
                            }
                        });
                });

                function openReservationChannel(reservationId) {
                    fetch(createChannelUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: JSON.stringify({ type: 'reservation', reservation_id: parseInt(reservationId, 10) })
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.channel_id) {
                                fetchChannels();
                                setTimeout(function() { selectChannel(data.channel_id, 'Réservation #' + reservationId); }, 400);
                            }
                        });
                }

                const urlParams = new URLSearchParams(window.location.search);
                const reservationId = urlParams.get('reservation_id');
                if (reservationId) {
                    openReservationChannel(reservationId);
                    window.history.replaceState({}, '', window.location.pathname);
                }

                fetchChannels();

                // Boîte interne : marquer lu à l'ouverture d'un message non lu.
                document.querySelectorAll('.internal-message__head[data-read-url]').forEach(function (head) {
                    head.addEventListener('click', function () {
                        const url = head.getAttribute('data-read-url');
                        if (!url) return;
                        head.setAttribute('data-read-url', '');

                        fetch(url, {
                            method: 'PATCH',
                            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                        }).catch(() => {});

                        const wrapper = head.closest('.internal-message');
                        if (wrapper) {
                            wrapper.classList.remove('internal-message--unread');
                            const badge = wrapper.querySelector('.internal-message__unread-badge');
                            if (badge) badge.remove();
                        }
                    }, { once: false });
                });
            })();
        </script>
    @endpush
@endsection
