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
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $brandLogoUrl = \App\Models\Setting::brandLogoUrl('light')
        ?: \App\Models\Setting::brandLogoUrl('dark')
        ?: URL::asset('build/images/logo-light.png');

    $isAgentRoute = request()->routeIs('agent.*');
    $profileUrl = null;
    if ($user) {
        if ($isAgentRoute && \Illuminate\Support\Facades\Route::has('agent.profile')) {
            $profileUrl = route('agent.profile');
        } elseif (method_exists($user, 'isPartner') && $user->isPartner() && \Illuminate\Support\Facades\Route::has('partner.profile.show')) {
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

    $agentNotifications = collect();
    $agentNotificationsUnread = 0;
    if ($user && $isAgentRoute) {
        $agentNotifications = \App\Models\ClientNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get();
        $agentNotificationsUnread = \App\Models\ClientNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }
@endphp

@if($user && $isAgentRoute)
    <style>
        .aj-agent-notification-trigger {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            color: #fff;
            font-size: 18px;
        }
        .aj-agent-notification-trigger:hover {
            background: rgba(255, 255, 255, .2);
            color: #fff;
        }
        .aj-agent-notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border: 2px solid #087bb7;
            border-radius: 999px;
            background: #ff5a1f;
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
        }
        .aj-agent-notification-modal .modal-content {
            border: 1px solid #d8e3ef;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .18);
        }
        .aj-agent-notification-list {
            display: grid;
            gap: 10px;
        }
        .aj-agent-notification-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border: 1px solid #e1eaf4;
            border-radius: 8px;
            background: #fff;
        }
        .aj-agent-notification-item.is-unread {
            border-color: #a7d8fb;
            background: #f2f9ff;
        }
        .aj-agent-notification-item h3 {
            margin: 0;
            color: #10253b;
            font-size: 14px;
            font-weight: 800;
        }
        .aj-agent-notification-item p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
        }
        .aj-agent-notification-date {
            display: block;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
        }
        .aj-agent-notification-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 8px 12px;
            border: 1px solid #cfdce9;
            border-radius: 8px;
            background: #fff;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }
        .aj-agent-notification-action.is-primary {
            border-color: #0086c9;
            background: #0086c9;
            color: #fff;
        }
        @media (max-width: 640px) {
            .aj-agent-notification-item {
                grid-template-columns: 1fr;
            }
            .aj-agent-notification-action {
                width: 100%;
            }
        }
    </style>
@endif

<div class="aj-topbar aj-topbar--internal-v2 {{ $isAgentRoute ? 'aj-topbar--agent-mode' : '' }}" role="banner">
    <div class="aj-container aj-topbar__inner">
        <div class="aj-topbar__left">
            @if($isAgentRoute)
                <div class="aj-topbar__brand">
                    <span class="aj-topbar__brand-logo-wrap">
                        <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="aj-topbar__brand-logo">
                    </span>
                    <span class="aj-topbar__brand-name">Ajinsafro Agent</span>
                </div>
            @else
                <div class="aj-topbar__socials">
                    <a href="{{ $instagramUrl !== '' ? $instagramUrl : '#' }}" class="aj-topbar__social-link" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.8A3.95 3.95 0 0 0 3.8 7.75v8.5A3.95 3.95 0 0 0 7.75 20.2h8.5a3.95 3.95 0 0 0 3.95-3.95v-8.5a3.95 3.95 0 0 0-3.95-3.95h-8.5Zm8.95 1.35a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.8a3.2 3.2 0 1 0 0 6.4 3.2 3.2 0 0 0 0-6.4Z" />
                        </svg>
                    </a>
                </div>
            @endif
            <div class="aj-topbar__contact">
                <span class="aj-topbar__item">{{ data_get($headerData, 'email') }}</span>
                <span class="aj-topbar__item">{{ data_get($headerData, 'phone') }}</span>
            </div>
        </div>

        <div class="aj-topbar__right">
            <div class="aj-topbar__auth">
                @if($user)
                    @if($isAgentRoute)
                        <button type="button" class="aj-agent-notification-trigger" data-bs-toggle="modal" data-bs-target="#agentNotificationsModal" aria-label="Notifications">
                            <i class="bx bx-bell"></i>
                            @if($agentNotificationsUnread > 0)
                                <span class="aj-agent-notification-badge">{{ $agentNotificationsUnread > 99 ? '99+' : $agentNotificationsUnread }}</span>
                            @endif
                        </button>
                    @endif

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

@if($user && $isAgentRoute)
    <div class="modal fade aj-agent-notification-modal" id="agentNotificationsModal" tabindex="-1" aria-labelledby="agentNotificationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="agentNotificationsModalLabel">Notifications</h5>
                        <small class="text-muted">{{ $agentNotificationsUnread }} non lue(s)</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($agentNotificationsUnread > 0 && \Illuminate\Support\Facades\Route::has('agent.notifications.read-all'))
                            <form method="POST" action="{{ route('agent.notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="aj-agent-notification-action">Tout marquer lu</button>
                            </form>
                        @endif
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                </div>
                <div class="modal-body">
                    @if($agentNotifications->count())
                        <div class="aj-agent-notification-list">
                            @foreach($agentNotifications as $notification)
                                <div class="aj-agent-notification-item {{ $notification->is_read ? '' : 'is-unread' }}">
                                    <div>
                                        <h3>{{ $notification->title }}</h3>
                                        <p>{{ \Illuminate\Support\Str::limit($notification->message, 150) }}</p>
                                        <span class="aj-agent-notification-date">{{ $notification->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <div>
                                        @if(! $notification->is_read && \Illuminate\Support\Facades\Route::has('agent.notifications.read'))
                                            <form method="POST" action="{{ route('agent.notifications.read', $notification) }}">
                                                @csrf
                                                <button type="submit" class="aj-agent-notification-action is-primary">
                                                    {{ $notification->link ? 'Ouvrir' : 'Marquer lu' }}
                                                </button>
                                            </form>
                                        @elseif($notification->link)
                                            <a href="{{ $notification->link }}" class="aj-agent-notification-action">Ouvrir</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <strong>Aucune notification</strong>
                            <p class="text-muted mb-0">Les nouvelles demandes, devis et modifications apparaitront ici.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
