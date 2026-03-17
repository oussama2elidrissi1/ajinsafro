@extends('partner_v2.layouts.app')
@section('title', 'Messagerie interne')

@section('content')
<div class="mb-6 flex items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Messagerie interne</h1>
        <p class="text-sm text-gray-500 mt-1">Échangez avec le siège, la comptabilité et les responsables autorisés.</p>
    </div>
    <div class="flex items-center gap-2">
        <select id="pm-contact" class="bg-white border border-gray-200 text-xs font-medium text-gray-700 rounded-xl px-3 py-2 outline-none focus:border-[#0083c4]">
            <option value="">Nouveau message…</option>
            @foreach($contacts as $c)
                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
            @endforeach
        </select>
        <button id="pm-start" class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">
            Démarrer
        </button>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex h-[650px] overflow-hidden">
    <div class="w-48 border-r border-gray-100 bg-gray-50/50 flex flex-col p-4 shrink-0 hidden md:flex">
        <div class="flex flex-col gap-1 flex-grow">
            <button type="button" class="px-3 py-2.5 rounded-xl bg-[#e6f3fa] text-[#0083c4] font-bold text-xs text-left">
                Réception <span id="pm-unread-pill" class="ml-2 inline-flex items-center justify-center text-[10px] bg-[#0083c4] text-white px-2 py-0.5 rounded-full">0</span>
            </button>
            <div class="px-3 py-2.5 rounded-xl text-gray-500 font-medium text-xs">
                Envoyés / Brouillons / Corbeille<br>
                <span class="text-[10px] text-gray-400">Basé sur conversations (canaux)</span>
            </div>
        </div>
        <div class="mt-auto pt-4 border-t border-gray-200/60 text-[10px] text-gray-500">
            Accès limité à vos canaux.
        </div>
    </div>

    <div class="w-full md:w-[340px] border-r border-gray-100 flex flex-col bg-white shrink-0">
        <div class="p-4 border-b border-gray-100 bg-white">
            <div class="relative">
                <input id="pm-search" type="text" placeholder="Rechercher…" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-[#0083c4] focus:bg-white transition-colors text-[#0e3a5a]">
            </div>
        </div>
        <div id="pm-channel-list" class="flex-grow overflow-y-auto no-scrollbar flex flex-col"></div>
    </div>

    <div class="hidden md:flex flex-grow flex-col bg-white relative">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-white z-10">
            <div class="flex items-center gap-3 min-w-0">
                <h2 id="pm-title" class="text-base font-bold text-[#0e3a5a] truncate">Sélectionnez une conversation</h2>
                <span id="pm-subtitle" class="bg-gray-100 text-gray-600 text-[9px] px-2 py-0.5 rounded uppercase font-bold tracking-wider hidden">Canal</span>
            </div>
        </div>

        <div id="pm-messages" class="flex-grow p-6 overflow-y-auto no-scrollbar space-y-3"></div>

        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
            <form id="pm-send-form" class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm focus-within:border-[#0083c4] focus-within:shadow-md transition-all">
                @csrf
                <textarea id="pm-message" placeholder="Écrire un message…" class="w-full px-4 py-3 text-sm focus:outline-none resize-none h-24 text-[#0e3a5a]"></textarea>
                <div class="px-4 py-3 bg-white flex justify-end items-center">
                    <button type="submit" class="bg-[#0083c4] text-white hover:bg-[#0e3a5a] transition-colors px-6 py-2 rounded-lg text-sm font-bold flex items-center gap-2 shadow-sm">
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const channelsUrl = @json(route('partner.messages.channels'));
        const createDirectUrl = @json(route('partner.messages.direct'));
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let activeChannelId = null;
        let channels = [];

        function el(html) {
            const t = document.createElement('template');
            t.innerHTML = html.trim();
            return t.content.firstChild;
        }

        function fmtIso(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (isNaN(d.getTime())) return '';
            return d.toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
        }

        function renderChannels(filter) {
            const list = document.getElementById('pm-channel-list');
            list.innerHTML = '';
            const q = (filter || '').toLowerCase().trim();
            const filtered = channels.filter(c => !q || (c.name || '').toLowerCase().includes(q));
            filtered.forEach(c => {
                const unreadDot = c.unread > 0 ? `<span class="absolute top-4 right-4 w-2 h-2 rounded-full bg-[#0083c4]"></span>` : '';
                const isActive = String(activeChannelId) === String(c.id);
                const row = el(`
                    <div class="p-4 border-b border-gray-50 cursor-pointer ${isActive ? 'bg-blue-50/40 border-l-4 border-[#0083c4]' : 'hover:bg-gray-50 border-l-4 border-transparent'} transition-colors relative" data-id="${c.id}">
                        ${unreadDot}
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold ${isActive ? 'text-[#0e3a5a]' : 'text-gray-700'} truncate pr-4">${c.name}</h4>
                            <div class="text-[9px] text-gray-400 font-medium mt-1">${fmtIso(c.updated_at)}</div>
                        </div>
                    </div>
                `);
                row.addEventListener('click', () => openChannel(c.id, c.name));
                list.appendChild(row);
            });

            const unreadTotal = channels.reduce((sum, c) => sum + (c.unread || 0), 0);
            document.getElementById('pm-unread-pill').textContent = unreadTotal;
        }

        async function loadChannels() {
            const res = await fetch(channelsUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            const data = await res.json();
            channels = (data.channels || []);
            renderChannels(document.getElementById('pm-search').value);
        }

        async function openChannel(id, name) {
            activeChannelId = id;
            document.getElementById('pm-title').textContent = name || 'Conversation';
            document.getElementById('pm-subtitle').classList.remove('hidden');
            renderChannels(document.getElementById('pm-search').value);

            const res = await fetch(@json(url('/partner/messages/channels')) + '/' + id + '/messages', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            const data = await res.json();
            const box = document.getElementById('pm-messages');
            box.innerHTML = '';
            (data.messages || []).forEach(m => {
                box.appendChild(el(`
                    <div class="border border-gray-100 rounded-xl px-4 py-3">
                        <div class="flex items-center justify-between mb-1">
                            <div class="text-xs font-bold text-[#0e3a5a]">${m.sender_name || ''}</div>
                            <div class="text-[10px] text-gray-400 font-medium">${fmtIso(m.created_at)}</div>
                        </div>
                        <div class="text-sm text-gray-700 whitespace-pre-wrap">${(m.message || '').replace(/</g,'&lt;')}</div>
                    </div>
                `));
            });
            box.scrollTop = box.scrollHeight;

            // refresh unread
            await loadChannels();
        }

        document.getElementById('pm-search').addEventListener('input', (e) => {
            renderChannels(e.target.value);
        });

        document.getElementById('pm-start').addEventListener('click', async () => {
            const sel = document.getElementById('pm-contact');
            const userId = sel.value;
            if (!userId) return;
            const res = await fetch(createDirectUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ user_id: userId })
            });
            const data = await res.json();
            if (data.channel_id) {
                await loadChannels();
                const ch = channels.find(c => String(c.id) === String(data.channel_id));
                openChannel(data.channel_id, ch ? ch.name : 'Conversation');
            }
        });

        document.getElementById('pm-send-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!activeChannelId) return;
            const ta = document.getElementById('pm-message');
            const msg = ta.value.trim();
            if (!msg) return;
            ta.value = '';
            const res = await fetch(@json(url('/partner/messages/channels')) + '/' + activeChannelId + '/send', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ message: msg }),
            });
            if (res.ok) {
                const currentName = document.getElementById('pm-title').textContent;
                await openChannel(activeChannelId, currentName);
            }
        });

        loadChannels();
    })();
</script>
@endpush
@endsection

