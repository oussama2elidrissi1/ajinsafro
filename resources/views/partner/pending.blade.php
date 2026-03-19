@extends('partner_v2.layouts.guest')

@section('title', 'Compte en attente')

@section('content')
    <div class="w-full max-w-xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Compte en cours de validation</h1>
                <p class="mt-1 text-sm text-gray-600">Votre accès partenaire sera activé dès validation administrative.</p>
            </div>

            <div class="px-6 sm:px-8 py-6">
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Votre demande d’inscription a bien été reçue. <strong>Votre compte partenaire doit être validé par un administrateur.</strong>
                </div>

                <p class="text-sm text-gray-600 mb-6">
                    Vous recevrez un email dès que votre compte sera activé. Vous pourrez alors accéder à votre espace partenaire
                    (réservations, clients, factures et messagerie).
                </p>

                <form method="POST" action="{{ route('partner.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
