@php
    $defaults = [
        'email' => 'contact@ajinsafro.ma',
        'phone' => '+212 5 39 32 38 74',
        'socials' => [
            'instagram' => '#',
        ],
        'login_url' => rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/login',
        'signup_url' => rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/register',
    ];

    $raw = \App\Models\Setting::getValue('wp_header');
    $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    $headerData = is_array($decoded) ? array_replace_recursive($defaults, $decoded) : $defaults;

    $instagramUrl = (string) data_get($headerData, 'socials.instagram', '#');
    $user = request()->user();

    $profileUrl = null;
    if ($user) {
        if (method_exists($user, 'isPartner') && $user->isPartner() && \Illuminate\Support\Facades\Route::has('partner.profile.show')) {
            $profileUrl = route('partner.profile.show');
        } elseif (\Illuminate\Support\Facades\Route::has('admin.profile.edit')) {
            $profileUrl = route('admin.profile.edit');
        } elseif (\Illuminate\Support\Facades\Route::has('partner.profile.show')) {
            $profileUrl = route('partner.profile.show');
        }
    }

    $usesPartnerLogout = $user
        && method_exists($user, 'isPartner')
        && $user->isPartner()
        && \Illuminate\Support\Facades\Route::has('partner.logout');
@endphp

<div class="aj-topbar aj-topbar--internal-v2" role="banner">
    <div class="aj-container aj-topbar__inner">
        <div class="aj-topbar__left">
            <div class="aj-topbar__socials">
                <a href="{{ $instagramUrl !== '' ? $instagramUrl : '#' }}" class="aj-topbar__social-link" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.8A3.95 3.95 0 0 0 3.8 7.75v8.5A3.95 3.95 0 0 0 7.75 20.2h8.5a3.95 3.95 0 0 0 3.95-3.95v-8.5a3.95 3.95 0 0 0-3.95-3.95h-8.5Zm8.95 1.35a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.8a3.2 3.2 0 1 0 0 6.4 3.2 3.2 0 0 0 0-6.4Z" />
                    </svg>
                </a>
            </div>
            <div class="aj-topbar__contact">
                <span class="aj-topbar__item">{{ data_get($headerData, 'email') }}</span>
                <span class="aj-topbar__item">{{ data_get($headerData, 'phone') }}</span>
            </div>
        </div>

        <div class="aj-topbar__right">
            <div class="aj-topbar__auth">
                @if($user)
                    @if($profileUrl)
                        <a href="{{ $profileUrl }}" class="aj-topbar__auth-link">Profil</a>
                    @endif

                    @if($usesPartnerLogout)
                        <form method="POST" action="{{ route('partner.logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="aj-topbar__auth-link aj-topbar__auth-link--signup border-0">D&eacute;connexion</button>
                        </form>
                    @elseif(\Illuminate\Support\Facades\Route::has('logout.get'))
                        <a href="{{ route('logout.get') }}" class="aj-topbar__auth-link aj-topbar__auth-link--signup">D&eacute;connexion</a>
                    @endif
                @else
                    <a href="{{ data_get($headerData, 'login_url') }}" class="aj-topbar__auth-link">SE CONNECTER</a>
                    <a href="{{ data_get($headerData, 'signup_url') }}" class="aj-topbar__auth-link aj-topbar__auth-link--signup">S&rsquo;INSCRIRE</a>
                @endif
            </div>
        </div>
    </div>
</div>