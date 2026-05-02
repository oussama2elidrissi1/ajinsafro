<!-- ========== Left Sidebar Start (AjinsAfro) ========== -->
@php
    $adminBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $adminBrandLogoDarkUrl = \App\Models\Setting::brandLogoUrl('dark');
    $sidebarUser = Auth::user();
    $menuItems = $adminSidebarMenu ?? [];
    $profileActive = request()->routeIs('admin.profile.*');
@endphp

<div class="vertical-menu">
    <div class="h-100">
        <div class="sidebar-brand-box text-center py-4 px-3 border-bottom">
            <a href="{{ route('admin.dashboard') }}" class="d-inline-flex align-items-center justify-content-center w-100">
                <img src="{{ $adminBrandLogoDarkUrl }}" alt="{{ $adminBrandName }}" class="admin-sidebar-logo">
            </a>
        </div>

        <div class="user-wid text-center py-4">
            <div class="user-img">
                <img src="{{ $sidebarUser->avatar_url }}" alt="" class="avatar-md mx-auto rounded-circle">
            </div>

            <div class="mt-3">
                <a href="{{ route('admin.dashboard') }}" class="text-body fw-medium font-size-16">{{ $sidebarUser->name }}</a>
                <p class="text-muted mt-1 mb-0 font-size-13">{{ $sidebarUser->getRoleNames()->first() ?? 'Admin' }}</p>
            </div>
        </div>

        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu" data-managed-sidebar="1">
                <li class="menu-title">Menu</li>

                @foreach($menuItems as $node)
                    @include('layouts.partials.sidebar-ajinsafro-node', ['node' => $node])
                @endforeach

                <li class="menu-title mt-3">Compte</li>
                @can('dashboard.view')
                    <li class="{{ $profileActive ? 'mm-active' : '' }}" data-menu-open="{{ $profileActive ? '1' : '0' }}">
                        <a href="{{ route('admin.profile.edit') }}" class="waves-effect {{ $profileActive ? 'mm-active active' : '' }}" data-menu-active="{{ $profileActive ? '1' : '0' }}">
                            <i class="bx bx-user-circle"></i>
                            <span>Mon profil</span>
                        </a>
                    </li>
                @endcan

                <li>
                    <a href="{{ route('logout.get') }}" class="waves-effect text-danger">
                        <i class="bx bx-power-off"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- Left Sidebar End -->

@push('body-end')
    <script>
        (function () {
            function applyManagedSidebarState() {
                const sideMenu = document.getElementById('side-menu');
                if (!sideMenu || sideMenu.dataset.managedSidebar !== '1' || !window.jQuery) {
                    return;
                }

                sideMenu.querySelectorAll('li').forEach((item) => item.classList.remove('mm-active'));
                sideMenu.querySelectorAll('ul.sub-menu').forEach((submenu) => {
                    submenu.classList.remove('mm-show');
                    submenu.classList.add('mm-collapse');
                });
                sideMenu.querySelectorAll('a').forEach((link) => {
                    link.classList.remove('active', 'mm-active');
                    if (link.classList.contains('has-arrow')) {
                        link.setAttribute('aria-expanded', 'false');
                    }
                });

                sideMenu.querySelectorAll('[data-menu-open="1"]').forEach((element) => {
                    if (element.tagName === 'LI') {
                        element.classList.add('mm-active');
                        const trigger = element.querySelector(':scope > a.has-arrow');
                        if (trigger) {
                            trigger.classList.add('mm-active');
                            trigger.setAttribute('aria-expanded', 'true');
                        }
                    } else if (element.tagName === 'UL') {
                        element.classList.add('mm-show');
                    }
                });

                sideMenu.querySelectorAll('[data-menu-active="1"]').forEach((element) => {
                    element.classList.add('active');
                    const parent = element.parentElement;
                    if (parent && parent.tagName === 'LI') {
                        parent.classList.add('mm-active');
                    }
                });

                const $sideMenu = window.jQuery(sideMenu);
                const existing = $sideMenu.data('metisMenu');
                if (existing && typeof existing.dispose === 'function') {
                    existing.dispose();
                }
                $sideMenu.metisMenu({ toggle: true, preventDefault: true });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', applyManagedSidebarState);
            } else {
                applyManagedSidebarState();
            }
        })();
    </script>
@endpush
