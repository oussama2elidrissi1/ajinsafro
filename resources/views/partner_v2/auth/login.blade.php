@extends('partner_v2.layouts.guest')

@section('title', 'Connexion partenaire')

@section('content')
    <div class="w-full max-w-xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Connexion</h1>
                <p class="mt-1 text-sm text-gray-600">Accédez à votre espace partenaire Ajinsafro.</p>
            </div>

            <div class="px-6 sm:px-8 py-6">
                @if(session('error'))
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('partner.login.submit') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            value="{{ old('email') }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                            placeholder="ex: contact@votre-agence.ma"
                        />
                        @error('email')
                            <div class="mt-1 text-sm text-red-700">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="mt-1 block w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                            placeholder="Votre mot de passe"
                        />
                        @error('password')
                            <div class="mt-1 text-sm text-red-700">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900" {{ old('remember') ? 'checked' : '' }}>
                            Se souvenir de moi
                        </label>
                        <a class="text-sm font-medium text-gray-900 hover:underline" href="{{ rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') }}/devenir-partenaire">
                            Devenir partenaire
                        </a>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900/30">
                            Se connecter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

