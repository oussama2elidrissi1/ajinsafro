@extends('layouts.admin-v6')

@section('title'){{ $isEdit ? 'Modifier rôle' : 'Créer rôle' }} �?" Rôles & Permissions@endsection

@push('styles')
    <style>
        .roles-permissions-shell {
            max-width: 1360px;
            margin: 0 auto;
        }

        .permissions-toolbar,
        .permissions-footer {
            position: sticky;
            z-index: 15;
            backdrop-filter: blur(10px);
        }

        .permissions-toolbar {
            top: 0.75rem;
        }

        .permissions-footer {
            bottom: 1rem;
        }

        .permissions-section {
            border: 1px solid rgba(76, 85, 107, 0.16);
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .permissions-section-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(76, 85, 107, 0.12);
            background: #f8fafc;
        }

        .permissions-section-body {
            padding: 1.25rem;
        }

        .permissions-module {
            border: 1px solid rgba(76, 85, 107, 0.14);
            border-radius: 0.9rem;
            background: #fff;
        }

        .permissions-module-header {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid rgba(76, 85, 107, 0.1);
            background: #fcfcfd;
        }

        .permissions-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 0.85rem;
        }

        .permission-item {
            border: 1px solid rgba(76, 85, 107, 0.12);
            border-radius: 0.8rem;
            padding: 0.85rem 0.95rem;
            background: #fff;
            min-height: 74px;
        }

        .permission-item .form-check {
            min-height: 100%;
        }

        .permission-item .form-check-input {
            width: 1.1rem;
            height: 1.1rem;
            margin-top: 0.15rem;
        }

        .permission-item .form-check-label {
            display: block;
            padding-left: 0.15rem;
            font-weight: 600;
            color: #1f2937;
            line-height: 1.35;
        }

        .permission-item .permission-key {
            display: block;
            margin-top: 0.3rem;
            color: #94a3b8;
            font-size: 0.75rem;
            line-height: 1.25;
        }

        .section-collapsed .permissions-section-body {
            display: none;
        }

        .module-collapsed .permissions-module-body {
            display: none;
        }

        .section-toggle[aria-expanded="false"] .toggle-label::after,
        .module-toggle[aria-expanded="false"] .toggle-label::after {
            content: 'Afficher';
        }

        .section-toggle[aria-expanded="true"] .toggle-label::after,
        .module-toggle[aria-expanded="true"] .toggle-label::after {
            content: 'Masquer';
        }

        .permission-hidden,
        .module-hidden,
        .section-hidden {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    @php
        $selectedPermissions = array_values(array_unique(old('permissions', $selectedPermissions ?? [])));
        $sections = $permissionSections ?? [];
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">{{ $isEdit ? 'Modifier rôle' : 'Créer rôle' }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.roles-permissions') }}">Rôles & Permissions</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Modifier' : 'Créer' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="roles-permissions-shell">
        <form method="POST" action="{{ $isEdit ? route('admin.settings.roles-permissions.update', $roleModel) : route('admin.settings.roles-permissions.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="permissions-toolbar card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Nom du rôle</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $roleModel->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Recherche de permission</label>
                            <input type="search" id="permission-search" class="form-control" placeholder="Rechercher un module, un libellé ou une clé technique">
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                <button type="button" class="btn btn-primary" id="check-all">Tout cocher</button>
                                <button type="button" class="btn btn-outline-secondary" id="uncheck-all">Tout décocher</button>
                                <span class="badge bg-soft-primary text-primary align-self-center px-3 py-2" id="permissions-count">0 sélectionnée(s)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @error('permissions')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
            @error('permissions.*')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div class="d-grid gap-3" id="permissions-sections">
                @foreach($sections as $section)
                    @php
                        $sectionKey = $section['key'];
                        $sectionPermissions = $section['permissions'] ?? [];
                        $sectionModules = $section['modules'] ?? [];
                    @endphp
                    <section class="permissions-section" data-section="{{ $sectionKey }}" data-search="{{ mb_strtolower($section['label']) }}">
                        <div class="permissions-section-header">
                            <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <h5 class="mb-0">{{ $section['label'] }}</h5>
                                        <span class="badge bg-soft-dark text-dark section-count" data-count-for="section:{{ $sectionKey }}">0 / 0</span>
                                    </div>
                                    <p class="text-muted small mb-0 mt-1">Permissions alignées sur la sidebar réelle de l�?Tadmin.</p>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary check-section" data-section="{{ $sectionKey }}">Cocher section</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary uncheck-section" data-section="{{ $sectionKey }}">Décocher section</button>
                                    <button type="button" class="btn btn-sm btn-light section-toggle" data-section="{{ $sectionKey }}" aria-expanded="true">
                                        <span class="toggle-label"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="permissions-section-body d-grid gap-3">
                            @if($sectionPermissions !== [])
                                <div class="permissions-list">
                                    @foreach($sectionPermissions as $permission)
                                        @php $id = 'perm_' . md5($sectionKey . '_' . $permission['name']); @endphp
                                        <div class="permission-item permission-entry"
                                             data-search="{{ mb_strtolower($section['label'] . ' ' . $permission['label'] . ' ' . $permission['name']) }}"
                                             data-section="{{ $sectionKey }}">
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input permission-checkbox"
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission['name'] }}"
                                                    id="{{ $id }}"
                                                    data-section="{{ $sectionKey }}"
                                                    {{ in_array($permission['name'], $selectedPermissions, true) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label" for="{{ $id }}">
                                                    {{ $permission['label'] }}
                                                    <span class="permission-key">{{ $permission['name'] }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @foreach($sectionModules as $module)
                                @php $moduleKey = $sectionKey . ':' . $module['key']; @endphp
                                <div class="permissions-module" data-module="{{ $moduleKey }}" data-search="{{ mb_strtolower($section['label'] . ' ' . $module['label']) }}">
                                    <div class="permissions-module-header">
                                        <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <h6 class="mb-0">{{ $module['label'] }}</h6>
                                                <span class="badge bg-soft-info text-info module-count" data-count-for="module:{{ $moduleKey }}">0 / 0</span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary check-module" data-module="{{ $moduleKey }}">Cocher module</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary uncheck-module" data-module="{{ $moduleKey }}">Décocher module</button>
                                                <button type="button" class="btn btn-sm btn-light module-toggle" data-module="{{ $moduleKey }}" aria-expanded="true">
                                                    <span class="toggle-label"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="permissions-module-body p-3">
                                        <div class="permissions-list">
                                            @foreach($module['permissions'] as $permission)
                                                @php $id = 'perm_' . md5($moduleKey . '_' . $permission['name']); @endphp
                                                <div class="permission-item permission-entry"
                                                     data-search="{{ mb_strtolower($section['label'] . ' ' . $module['label'] . ' ' . $permission['label'] . ' ' . $permission['name']) }}"
                                                     data-section="{{ $sectionKey }}"
                                                     data-module="{{ $moduleKey }}">
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input permission-checkbox"
                                                            type="checkbox"
                                                            name="permissions[]"
                                                            value="{{ $permission['name'] }}"
                                                            id="{{ $id }}"
                                                            data-section="{{ $sectionKey }}"
                                                            data-module="{{ $moduleKey }}"
                                                            {{ in_array($permission['name'], $selectedPermissions, true) ? 'checked' : '' }}
                                                        >
                                                        <label class="form-check-label" for="{{ $id }}">
                                                            {{ $permission['label'] }}
                                                            <span class="permission-key">{{ $permission['name'] }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="permissions-footer mt-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <div class="fw-semibold">Enregistrement du rôle</div>
                            <div class="text-muted small">Les permissions existantes non touchées sont conservées jusqu�?Tà la validation.</div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.settings.roles-permissions') }}" class="btn btn-light">Annuler</a>
                            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Mettre à jour' : 'Créer' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        (function () {
            const searchInput = document.getElementById('permission-search');
            const checkboxes = Array.from(document.querySelectorAll('.permission-checkbox'));
            const sectionEls = Array.from(document.querySelectorAll('.permissions-section'));
            const moduleEls = Array.from(document.querySelectorAll('.permissions-module'));
            const countEl = document.getElementById('permissions-count');

            function updateCounter(selector, countFor) {
                const target = document.querySelector(selector + '[data-count-for="' + countFor + '"]');
                if (!target) return;

                const scopeSelector = countFor.startsWith('section:')
                    ? '.permission-checkbox[data-section="' + countFor.replace('section:', '') + '"]'
                    : '.permission-checkbox[data-module="' + countFor.replace('module:', '') + '"]';

                const scoped = Array.from(document.querySelectorAll(scopeSelector));
                const total = scoped.length;
                const checked = scoped.filter((item) => item.checked).length;
                target.textContent = checked + ' / ' + total;
            }

            function updateCounters() {
                const checked = checkboxes.filter((checkbox) => checkbox.checked).length;
                countEl.textContent = checked + ' sélectionnée(s)';

                sectionEls.forEach((section) => {
                    updateCounter('.section-count', 'section:' + section.dataset.section);
                });

                moduleEls.forEach((module) => {
                    updateCounter('.module-count', 'module:' + module.dataset.module);
                });
            }

            function setScopeState(selector, state) {
                document.querySelectorAll(selector).forEach((checkbox) => {
                    checkbox.checked = state;
                });
                updateCounters();
            }

            function applySearch() {
                const term = (searchInput.value || '').trim().toLowerCase();

                document.querySelectorAll('.permission-entry').forEach((entry) => {
                    const text = entry.dataset.search || '';
                    entry.classList.toggle('permission-hidden', term !== '' && !text.includes(term));
                });

                moduleEls.forEach((module) => {
                    const moduleText = module.dataset.search || '';
                    const visiblePermissions = module.querySelectorAll('.permission-entry:not(.permission-hidden)').length;
                    const shouldShow = term === '' || visiblePermissions > 0 || moduleText.includes(term);
                    module.classList.toggle('module-hidden', !shouldShow);
                });

                sectionEls.forEach((section) => {
                    const sectionText = section.dataset.search || '';
                    const visiblePermissions = section.querySelectorAll('.permission-entry:not(.permission-hidden)').length;
                    const visibleModules = section.querySelectorAll('.permissions-module:not(.module-hidden)').length;
                    const shouldShow = term === '' || visiblePermissions > 0 || visibleModules > 0 || sectionText.includes(term);
                    section.classList.toggle('section-hidden', !shouldShow);
                });
            }

            document.getElementById('check-all').addEventListener('click', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = true;
                });
                updateCounters();
            });

            document.getElementById('uncheck-all').addEventListener('click', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = false;
                });
                updateCounters();
            });

            document.querySelectorAll('.check-section').forEach((button) => {
                button.addEventListener('click', () => setScopeState('.permission-checkbox[data-section="' + button.dataset.section + '"]', true));
            });

            document.querySelectorAll('.uncheck-section').forEach((button) => {
                button.addEventListener('click', () => setScopeState('.permission-checkbox[data-section="' + button.dataset.section + '"]', false));
            });

            document.querySelectorAll('.check-module').forEach((button) => {
                button.addEventListener('click', () => setScopeState('.permission-checkbox[data-module="' + button.dataset.module + '"]', true));
            });

            document.querySelectorAll('.uncheck-module').forEach((button) => {
                button.addEventListener('click', () => setScopeState('.permission-checkbox[data-module="' + button.dataset.module + '"]', false));
            });

            document.querySelectorAll('.section-toggle').forEach((button) => {
                button.addEventListener('click', () => {
                    const section = document.querySelector('.permissions-section[data-section="' + button.dataset.section + '"]');
                    const expanded = button.getAttribute('aria-expanded') === 'true';
                    button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    section.classList.toggle('section-collapsed', expanded);
                });
            });

            document.querySelectorAll('.module-toggle').forEach((button) => {
                button.addEventListener('click', () => {
                    const module = document.querySelector('.permissions-module[data-module="' + button.dataset.module + '"]');
                    const expanded = button.getAttribute('aria-expanded') === 'true';
                    button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    module.classList.toggle('module-collapsed', expanded);
                });
            });

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateCounters);
            });

            if (searchInput) {
                searchInput.addEventListener('input', applySearch);
            }

            updateCounters();
            applySearch();
        })();
    </script>
@endpush


