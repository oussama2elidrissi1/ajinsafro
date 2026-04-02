@extends('layouts.master-ajinsafro')

@section('title', 'Messagerie')

@section('content')
    @php
        $folders = [
            'inbox' => 'Boîte',
            'sent' => 'Envoyés',
            'drafts' => 'Brouillons',
            'trash' => 'Corbeille',
        ];
    @endphp

    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0e3a5a] sm:text-3xl">Messagerie interne</h1>
            <p class="mt-1 text-sm text-gray-500">Vue Gmail intégrée au dashboard agent.</p>
        </div>
        @if(session('status'))
            <div class="rounded-xl border border-[#0e3a5a]/10 bg-white px-4 py-2 text-sm font-medium text-[#0e3a5a] shadow-sm">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="gmail-shell overflow-hidden rounded-[28px] border border-[#d7e3f4] bg-[#f8fbff] shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
        <div class="flex h-[calc(100vh-120px)] overflow-hidden">
            <aside class="hidden w-[280px] shrink-0 border-r border-[#d7e3f4] bg-[#f1f6fd] p-5 lg:flex lg:flex-col">
                <button type="button"
                        data-modal-open="modal-nouveau"
                        class="mb-6 inline-flex items-center justify-center gap-2 rounded-2xl bg-[#c2e7ff] px-5 py-4 text-sm font-semibold text-[#0b3558] shadow-sm transition hover:shadow-md">
                    <i class="fas fa-pen"></i>
                    Nouveau message
                </button>

                <nav class="space-y-1 text-sm">
                    @foreach($folders as $key => $label)
                        @php $active = $folder === $key; @endphp
                        <a href="{{ route($routeBase.'.index', array_filter(['folder' => $key, 'q' => $search ?: null])) }}"
                           class="flex items-center justify-between rounded-2xl px-4 py-3 transition {{ $active ? 'bg-white font-semibold text-[#0b3558] shadow-sm' : 'text-gray-600 hover:bg-white/80 hover:text-[#0b3558]' }}">
                            <span>{{ $label }}</span>
                            @if($key === 'inbox' && $unreadCount > 0)
                                <span class="rounded-full bg-[#0b57d0] px-2 py-0.5 text-xs font-semibold text-white">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </aside>

            <section class="flex min-w-0 flex-1 flex-col overflow-hidden bg-white">
                <div class="border-b border-[#d7e3f4] px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <form method="GET" action="{{ route($routeBase.'.index') }}" class="min-w-0 flex-1">
                            <input type="hidden" name="folder" value="{{ $folder }}">
                            <div class="relative">
                                <i class="fas fa-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                                <input type="search"
                                       name="q"
                                       value="{{ $search }}"
                                       placeholder="Rechercher dans la messagerie"
                                       class="w-full rounded-full border border-transparent bg-[#eef3fd] py-3 pl-11 pr-4 text-sm text-gray-700 outline-none ring-0 transition focus:border-[#c2e7ff] focus:bg-white focus:shadow-[0_0_0_4px_rgba(194,231,255,0.5)]">
                            </div>
                        </form>

                        <div class="flex items-center justify-between gap-3 text-sm text-gray-500 xl:justify-end">
                            <span>{{ $rangeLabel }}</span>
                            <div class="flex items-center gap-2">
                                @if($messages->onFirstPage())
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-300">
                                        <i class="fas fa-chevron-left text-xs"></i>
                                    </span>
                                @else
                                    <a href="{{ $messages->previousPageUrl() }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#eef3fd] text-[#0b3558] transition hover:bg-[#dfe9fb]">
                                        <i class="fas fa-chevron-left text-xs"></i>
                                    </a>
                                @endif

                                @if($messages->hasMorePages())
                                    <a href="{{ $messages->nextPageUrl() }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#eef3fd] text-[#0b3558] transition hover:bg-[#dfe9fb]">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </a>
                                @else
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-300">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="gmail-scroll min-h-0 flex-1 overflow-y-auto">
                    @forelse($messages as $message)
                        @php
                            $senderName = $message->sender?->name ?? 'Message';
                            $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(\Illuminate\Support\Str::of($senderName)->replace(' ', ''), 0, 2));
                        @endphp
                        <div class="group border-b border-[#edf2fa] px-4 py-3 transition hover:relative hover:z-[1] hover:shadow-[0_10px_26px_rgba(15,23,42,0.08)] {{ $message->read ? 'bg-white' : 'bg-[#f3f8ff]' }} sm:px-6"
                             data-message-row
                             data-message-url="{{ route($routeBase.'.show', $message) }}">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#0083c4] text-xs font-bold text-white">
                                    {{ $initials }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 flex-1 cursor-pointer">
                                            <p class="truncate text-sm {{ !$message->read ? 'font-bold text-[#0b3558]' : 'font-medium text-[#3c4043]' }}">
                                                {{ $senderName }}
                                            </p>
                                            <a href="{{ route($routeBase.'.show', $message) }}"
                                               class="mt-1 block truncate text-sm {{ !$message->read ? 'font-bold text-[#0b3558]' : 'font-medium text-[#3c4043]' }}">
                                                {{ $message->subject }}
                                                <span class="font-normal text-gray-500"> - {{ $message->preview }}</span>
                                            </a>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1 pl-0 lg:pl-4">
                                            <a href="{{ route($routeBase.'.show', $message) }}"
                                               class="hidden h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-[#eef3fd] hover:text-[#0b57d0] group-hover:inline-flex"
                                               title="Ouvrir le message">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                            <form method="POST" action="{{ route($routeBase.'.star', $message) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-[#eef3fd] hover:text-[#fbbc04]" title="Favori">
                                                    <i class="{{ $message->starred ? 'fas text-[#fbbc04]' : 'far' }} fa-star text-xs"></i>
                                                </button>
                                            </form>

                                            @if(!$message->read && (int) $message->recipient_id === (int) auth()->id())
                                                <form method="POST" action="{{ route($routeBase.'.read', $message) }}" class="hidden group-hover:block">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-[#eef3fd] hover:text-[#0b57d0]" title="Marquer comme lu">
                                                        <i class="fas fa-envelope-open-text text-xs"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route($routeBase.'.destroy', $message) }}" class="hidden group-hover:block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-[#fff1f0] hover:text-[#d93025]" title="Corbeille">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </button>
                                            </form>

                                            <span class="ml-2 text-xs {{ !$message->read ? 'font-bold text-[#0b3558]' : 'font-medium text-gray-500' }}">
                                                {{ $message->created_at->isToday() ? $message->created_at->format('H:i') : $message->created_at->format('d M') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full min-h-[260px] items-center justify-center px-6 py-12">
                            <div class="text-center">
                                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#eef3fd] text-[#0b57d0]">
                                    <i class="fas fa-inbox text-xl"></i>
                                </div>
                                <h2 class="text-lg font-semibold text-[#0e3a5a]">Aucun message</h2>
                                <p class="mt-2 text-sm text-gray-500">Aucun résultat pour ce dossier ou cette recherche.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <div id="modal-nouveau" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-sm">
        <div class="absolute inset-0" data-modal-close="modal-nouveau"></div>
        <div class="relative z-10 w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-xl font-bold text-[#0e3a5a]">Nouveau message</h2>
                <button type="button" data-modal-close="modal-nouveau" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route($routeBase.'.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="recipient_id" class="mb-2 block text-sm font-semibold text-[#0e3a5a]">Destinataire</label>
                    <select id="recipient_id" name="recipient_id" class="w-full rounded-2xl border border-[#d7e3f4] px-4 py-3 text-sm text-gray-700 focus:border-[#0083c4] focus:outline-none">
                        <option value="">Choisir un contact</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}" @selected(old('recipient_id') == $contact->id)>{{ $contact->name }} ({{ ucfirst($contact->role) }})</option>
                        @endforeach
                    </select>
                    @error('recipient_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="subject" class="mb-2 block text-sm font-semibold text-[#0e3a5a]">Sujet</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="w-full rounded-2xl border border-[#d7e3f4] px-4 py-3 text-sm text-gray-700 focus:border-[#0083c4] focus:outline-none">
                    @error('subject')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="body" class="mb-2 block text-sm font-semibold text-[#0e3a5a]">Message</label>
                    <textarea id="body" name="body" rows="7" class="w-full rounded-2xl border border-[#d7e3f4] px-4 py-3 text-sm text-gray-700 focus:border-[#0083c4] focus:outline-none">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" data-modal-close="modal-nouveau" class="rounded-2xl border border-gray-200 px-5 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit" class="rounded-2xl bg-[#0083c4] px-5 py-3 text-sm font-semibold text-white hover:opacity-95">
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    @if(($routeBase ?? '') === 'admin.messagerie')
        @vite(['resources/css/partner-v2.css'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @endif
    <style>
        .gmail-scroll {
            scrollbar-gutter: stable;
        }

        .gmail-scroll::-webkit-scrollbar {
            width: 10px;
        }

        .gmail-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
            border: 2px solid #fff;
        }

        .gmail-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const openModal = function (id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = function (id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.querySelectorAll('[data-modal-open]').forEach(function (button) {
                button.addEventListener('click', function () {
                    openModal(this.dataset.modalOpen);
                });
            });

            document.querySelectorAll('[data-modal-close]').forEach(function (button) {
                button.addEventListener('click', function () {
                    closeModal(this.dataset.modalClose);
                });
            });

            document.querySelectorAll('[data-message-row]').forEach(function (row) {
                row.addEventListener('click', function (event) {
                    if (event.target.closest('a, button, form, input, select, textarea, label')) {
                        return;
                    }

                    const url = row.dataset.messageUrl;
                    if (url) {
                        window.location.href = url;
                    }
                });
            });

            @if($errors->any())
                openModal('modal-nouveau');
            @endif
        });
    </script>
@endpush
