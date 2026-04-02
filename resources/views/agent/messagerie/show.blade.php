@extends('layouts.master-ajinsafro')

@section('title', 'Message')

@section('content')
    <div class="relative z-[80] mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="pointer-events-auto">
            <a href="{{ route($routeBase.'.index') }}"
               onclick="window.location.href='{{ route($routeBase.'.index') }}'; return false;"
               class="inline-flex items-center gap-2 text-sm font-semibold text-[#0083c4] hover:underline">
                <i class="fas fa-arrow-left"></i>
                Retour a la messagerie
            </a>
            <h1 class="mt-3 text-2xl font-bold text-[#0e3a5a] sm:text-3xl">{{ $message->subject }}</h1>
        </div>

        <button type="button"
                data-modal-open="modal-reponse"
                onclick="window.__messagerieOpenModal && window.__messagerieOpenModal('modal-reponse')"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#0083c4] px-5 py-3 text-sm font-semibold text-white hover:opacity-95">
            <i class="fas fa-reply"></i>
            Repondre
        </button>
    </div>

    <div class="rounded-[28px] border border-[#d7e3f4] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)] sm:p-8">
        <div class="mb-6 border-b border-[#edf2fa] pb-6">
            <p class="text-sm text-gray-500">De</p>
            <p class="mt-1 text-lg font-semibold text-[#0e3a5a]">{{ $message->sender?->name }}</p>
            <p class="mt-4 text-sm text-gray-500">Recu le</p>
            <p class="mt-1 text-sm font-medium text-[#3c4043]">{{ $message->created_at->format('d M Y H:i') }}</p>
        </div>

        <div class="prose prose-sm max-w-none text-[#3c4043]">
            {!! nl2br(e($message->body)) !!}
        </div>
    </div>

    <div id="modal-reponse" class="fixed inset-0 z-[120] hidden items-center justify-center bg-black/30 p-4 backdrop-blur-sm">
        <div class="absolute inset-0" data-modal-close="modal-reponse"></div>
        <div class="relative z-10 w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-xl font-bold text-[#0e3a5a]">Repondre</h2>
                <button type="button" data-modal-close="modal-reponse" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route($routeBase.'.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="recipient_id" value="{{ old('recipient_id', $replyRecipient?->id) }}">

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#0e3a5a]">Destinataire</label>
                    <div class="rounded-2xl border border-[#d7e3f4] bg-[#f8fbff] px-4 py-3 text-sm text-gray-700">
                        {{ $replyRecipient?->name }} @if($replyRecipient?->email)<span class="text-gray-500">({{ $replyRecipient->email }})</span>@endif
                    </div>
                    @error('recipient_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="subject" class="mb-2 block text-sm font-semibold text-[#0e3a5a]">Sujet</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject', 'Re: '.$message->subject) }}" class="w-full rounded-2xl border border-[#d7e3f4] px-4 py-3 text-sm text-gray-700 focus:border-[#0083c4] focus:outline-none">
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
                    <button type="button" data-modal-close="modal-reponse" class="rounded-2xl border border-gray-200 px-5 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50">
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

            window.__messagerieOpenModal = openModal;
            window.__messagerieCloseModal = closeModal;

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

            @if($errors->any())
                openModal('modal-reponse');
            @endif
        });
    </script>
@endpush
