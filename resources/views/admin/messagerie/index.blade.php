@extends('layouts.master-ajinsafro')
@section('title')
    Messagerie interne
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Messagerie interne</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Messagerie</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="email-leftbar card" style="max-width: 320px;">
                <button type="button" class="btn btn-primary btn-block waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#newChannelModal">
                    <i class="bx bx-plus me-1"></i> Nouvelle conversation
                </button>
                <h6 class="mt-4">Conversations</h6>
                <div id="channel-list" class="list-group mail-list mt-2">
                    <p class="text-muted small">Chargement…</p>
                </div>
            </div>

            <div class="email-rightbar mb-3">
                <div class="card">
                    <div id="message-area-placeholder" class="card-body text-center text-muted py-5">
                        <i class="bx bx-message-detail font-size-48"></i>
                        <p class="mt-2 mb-0">Sélectionnez une conversation ou créez-en une.</p>
                    </div>
                    <div id="message-area" class="card-body d-none">
                        <h5 id="channel-title" class="mb-3"></h5>
                        <div id="messages-container" style="max-height: 400px; overflow-y: auto;" class="mb-3"></div>
                        <div class="d-flex gap-2">
                            <input type="text" id="message-input" class="form-control" placeholder="Écrire un message…" maxlength="10000">
                            <button type="button" id="send-btn" class="btn btn-primary">Envoyer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newChannelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-direct">Direct</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-reservation">Réservation</a>
                        </li>
                    </ul>
                    <div class="tab-content p-3">
                        <div class="tab-pane active" id="tab-direct">
                            <label class="form-label">Utilisateur</label>
                            <select id="new-direct-user" class="form-select">
                                <option value="">– Choisir –</option>
                                @foreach($users ?? [] as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="tab-pane" id="tab-reservation">
                            <label class="form-label">Réservation</label>
                            <select id="new-reservation-id" class="form-select">
                                <option value="">– Choisir –</option>
                                @foreach($reservations ?? [] as $r)
                                    <option value="{{ $r->id }}">#{{ $r->id }} – {{ $r->client_first_name }} {{ $r->client_last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="create-channel-btn">Ouvrir</button>
                </div>
            </div>
        </div>
    </div>

    <script>
(function() {
    const csrf = '{{ csrf_token() }}';
    const channelsUrl = '{{ route("admin.messagerie.channels") }}';
    const createChannelUrl = '{{ route("admin.messagerie.channels.create") }}';
    let currentChannelId = null;
    let pollTimer = null;

    const $list = document.getElementById('channel-list');
    const $placeholder = document.getElementById('message-area-placeholder');
    const $messageArea = document.getElementById('message-area');
    const $channelTitle = document.getElementById('channel-title');
    const $messagesContainer = document.getElementById('messages-container');
    const $messageInput = document.getElementById('message-input');
    const $sendBtn = document.getElementById('send-btn');

    function fetchChannels() {
        fetch(channelsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (!data.channels || data.channels.length === 0) {
                    $list.innerHTML = '<p class="text-muted small">Aucune conversation.</p>';
                    return;
                }
                $list.innerHTML = data.channels.map(ch => {
                    const unread = ch.unread ? `<span class="badge bg-danger float-end">${ch.unread}</span>` : '';
                    return `<a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center channel-item ${currentChannelId == ch.id ? 'active' : ''}" data-channel-id="${ch.id}" data-name="${(ch.name || ch.display_name || '').replace(/"/g, '&quot;')}">${ch.name || ch.display_name} ${unread}</a>`;
                }).join('');
                document.querySelectorAll('.channel-item').forEach(el => {
                    el.addEventListener('click', function(e) { e.preventDefault(); selectChannel(parseInt(this.dataset.channelId, 10), this.dataset.name); });
                });
            })
            .catch(() => { $list.innerHTML = '<p class="text-danger small">Erreur chargement.</p>'; });
    }

    function selectChannel(id, name) {
        currentChannelId = id;
        $channelTitle.textContent = name || 'Conversation';
        $placeholder.classList.add('d-none');
        $messageArea.classList.remove('d-none');
        $messageInput.focus();
        document.querySelectorAll('.channel-item').forEach(el => {
            el.classList.toggle('active', parseInt(el.dataset.channelId, 10) === id);
        });
        loadMessages();
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(loadMessages, 10000);
    }

    function loadMessages() {
        if (!currentChannelId) return;
        const messagesUrl = '{{ url("admin/messagerie/channels") }}/' + currentChannelId + '/messages';
        fetch(messagesUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(r => {
                if (!r.ok) return r.json().then(d => { throw new Error(d.error || 'Erreur chargement'); });
                return r.json();
            })
            .then(data => {
                $messagesContainer.innerHTML = (data.messages || []).map(m => {
                    const time = new Date(m.created_at).toLocaleString('fr-FR');
                    return '<div class="mb-2"><strong>' + escapeHtml(m.sender_name) + '</strong> <small class="text-muted">' + time + '</small><br><span class="text-break">' + escapeHtml(m.message) + '</span></div>';
                }).join('');
                $messagesContainer.scrollTop = $messagesContainer.scrollHeight;
            })
            .catch(() => {});
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
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
                    return r.json().then(data => { throw new Error(data.message || data.error || 'Erreur ' + r.status); });
                }
                return r.json();
            })
            .then((data) => {
                $messageInput.value = '';
                loadMessages();
                fetchChannels();
            })
            .catch(err => {
                alert(err.message || 'Erreur lors de l\'envoi du message.');
            });
    }

    $sendBtn.addEventListener('click', sendMessage);
    $messageInput.addEventListener('keydown', function(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } });

    document.getElementById('create-channel-btn').addEventListener('click', function() {
        const directUser = document.getElementById('new-direct-user').value;
        const reservationId = document.getElementById('new-reservation-id').value;
        const activeTab = document.querySelector('#newChannelModal .nav-link.active');
        const type = activeTab && activeTab.getAttribute('href') === '#tab-reservation' ? 'reservation' : 'direct';
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
                        const name = type === 'reservation' ? 'Réservation #' + (body.reservation_id || '') : (document.querySelector('#new-direct-user option:checked')?.textContent || 'Conversation');
                        selectChannel(data.channel_id, name);
                    }, 300);
                } else if (data.error) {
                    alert(data.error);
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
})();
    </script>
@endsection
