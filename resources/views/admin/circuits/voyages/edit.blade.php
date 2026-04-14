@php
    $isCreate = isset($voyage->ID) && (int) $voyage->ID === 0;
    $laravelV = $laravelVoyage ?? null;
    $voyageEditCssPath = public_path('css/voyage-edit.css');
    $voyageEditCssVersion = file_exists($voyageEditCssPath) ? (string) filemtime($voyageEditCssPath) : '1';
    $voyageEditJsPath = public_path('js/voyage-edit-page.js');
    $voyageEditJsVersion = file_exists($voyageEditJsPath) ? (string) filemtime($voyageEditJsPath) : '1';
    $veWpId = isset($voyage->ID) ? (int) $voyage->ID : 0;
    $veAdultRaw = $meta['adult_price'] ?? (method_exists($voyage, 'getMeta') ? $voyage->getMeta('adult_price') : null);
    $vePriceLabel = null;
    if ($veAdultRaw !== null && $veAdultRaw !== '') {
        $vePriceLabel = is_numeric($veAdultRaw)
            ? number_format((float) $veAdultRaw, 0, ',', ' ').' MAD'
            : trim((string) $veAdultRaw);
    } elseif ($laravelV) {
        $priceFrom = data_get($laravelV, 'price_from');
        if ($priceFrom !== null && $priceFrom !== '' && is_numeric($priceFrom) && (float) $priceFrom > 0) {
            $cur = trim((string) (data_get($laravelV, 'currency')
                ?: data_get($laravelV, 'currency_symbol')
                ?: ''));
            $vePriceLabel = number_format((float) $priceFrom, 0, ',', ' ').' '.($cur !== '' ? $cur : 'MAD');
        }
    }
    $veDestinationRaw = $laravelV ? data_get($laravelV, 'destination') : null;
    $veDestination = ($veDestinationRaw !== null && trim((string) $veDestinationRaw) !== '')
        ? trim((string) $veDestinationRaw)
        : null;
    $veDatesCount = isset($travelDates) && $travelDates instanceof \Illuminate\Support\Collection ? $travelDates->count() : 0;
@endphp
@extends('layouts.master-ajinsafro')

@section('title')
    {{ $isCreate ? 'Creer un tour WordPress' : 'Modifier le tour WordPress' }}
@endsection

@push('styles')
    <link href="{{ URL::asset('css/voyage-edit.css?v=' . $voyageEditCssVersion) }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="voyage-edit-page">
    <div class="ve-shell">
        @include('admin.circuits.voyages.partials._voyage_page_header', [
            'isCreate' => $isCreate,
            'voyage' => $voyage,
            'veWpId' => $veWpId,
            'laravelV' => $laravelV,
            'vePriceLabel' => $vePriceLabel,
            'veDestination' => $veDestination,
            'deleteFormId' => 'delete-voyage-form',
        ])
    </div>

    <div class="ve-shell">
        @include('admin.circuits.voyages.partials._voyage_form_alerts')
    </div>

    <form action="{{ $isCreate ? route('admin.circuits.voyages.store') : route('admin.circuits.voyages.update', $voyage->ID) }}" method="POST" id="edit-voyage-form" data-voyage-id="{{ $voyage->ID ?? 0 }}">
        @csrf
        @if (!$isCreate)
            @method('PUT')
        @endif
        <textarea name="programme_days_payload" id="programme-days-payload" class="d-none" aria-hidden="true"></textarea>

        <div class="ve-shell">
            <div class="ve-page-layout">
                <div class="ve-main-col">
                    <div class="ve-editor-frame">
                        @include('admin.circuits.voyages.partials._voyage_actions_bar')
                        @include('admin.circuits.voyages.partials._voyage_tabs_nav')

                        <div class="ve-editor-body">
                            <div class="tab-content ve-tab-content pt-4">
                                @include('admin.circuits.voyages.partials.tabs._basic')
                                @include('admin.circuits.voyages.partials.tabs._location')
                                @include('admin.circuits.voyages.partials.tabs._pricing')
                                @include('admin.circuits.voyages.partials.tabs._information')
                                @include('admin.circuits.voyages.partials.tabs._extras')
                                @include('admin.circuits.voyages.partials.tabs._availability')
                                @include('admin.circuits.voyages.partials.tabs._media')
                                @include('admin.circuits.voyages.partials.tabs._taxonomies')
                                @include('admin.circuits.voyages.partials.tabs._logistics')
                                @include('admin.circuits.voyages.partials.tabs._hotels')
                                @include('admin.circuits.voyages.partials.tabs._transfers')
                                @include('admin.circuits.voyages.partials.tabs._activities')
                                @include('admin.circuits.voyages.partials.tabs._programme')
                            </div>

                            @if (!$isCreate)
                                <div class="card ve-pane-card ve-danger-zone-card mt-4">
                                    <div class="card-body">
                                        <p class="ve-danger-zone-title"><i class="bx bx-error-circle"></i> Suppression definitive</p>
                                        <p class="ve-danger-zone-text">Supprimez le voyage et ses donnees uniquement si ce dossier ne doit plus etre utilise.</p>
                                        <button type="submit" form="delete-voyage-form" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Supprimer definitivement ce tour WordPress ? Cette action est irreversible.')">
                                            <i class="bx bx-trash"></i> Supprimer ce voyage
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if (!$isCreate)
        <form id="delete-voyage-form" action="{{ route('admin.circuits.voyages.destroy', $voyage->ID) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif

</div>
@endsection

@push('script')
    @include('admin.circuits.voyages.partials._voyage_page_bootstrap')
    <script src="{{ URL::asset('build/libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="{{ URL::asset('js/voyage-editor-runtime.js') }}"></script>
    <script src="{{ URL::asset('js/voyage-edit-page.js?v=' . $voyageEditJsVersion) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var workflow = document.querySelector('[data-ve-workflow]');
            if (!workflow) return;

            var stepButtons = Array.prototype.slice.call(workflow.querySelectorAll('[data-ve-step]'));
            var prevBtn = workflow.querySelector('[data-ve-step-prev]');
            var nextBtn = workflow.querySelector('[data-ve-step-next]');
            var currentLabel = workflow.querySelector('[data-ve-current-step-label]');
            var tabContent = document.querySelector('.ve-tab-content');
            var allPanes = tabContent ? Array.prototype.slice.call(tabContent.querySelectorAll('.tab-pane')).filter(function (pane) {
                return pane.parentElement === tabContent;
            }) : [];
            var detailNav = workflow.querySelector('[data-ve-detail-nav]');

            function tabLink(target) {
                return document.querySelector('a[href="' + target + '"][data-bs-toggle="tab"]');
            }

            function showTab(target) {
                var link = tabLink(target);
                if (!link) return false;
                if (window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(link).show();
                    return true;
                }
                // Fallback when Bootstrap JS is not exposed globally.
                var navLinks = Array.prototype.slice.call(document.querySelectorAll('.ve-nav-tabs .nav-link[data-bs-toggle="tab"]'));
                navLinks.forEach(function (nav) {
                    var isActive = nav === link;
                    nav.classList.toggle('active', isActive);
                    nav.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
                try {
                    link.dispatchEvent(new Event('shown.bs.tab', { bubbles: true }));
                } catch (e) {
                    // Ignore legacy browsers: UI is synced by direct call in handlers.
                }
                return true;
            }

            function getStepForTarget(target) {
                return stepButtons.find(function (btn) {
                    var tabs = String(btn.getAttribute('data-ve-step-tabs') || '').split(',').map(function (s) { return s.trim(); });
                    return tabs.indexOf(target) >= 0;
                }) || null;
            }

            function getTabsForStep(stepBtn) {
                return String(stepBtn.getAttribute('data-ve-step-tabs') || '')
                    .split(',')
                    .map(function (s) { return s.trim(); })
                    .filter(function (s) { return s.charAt(0) === '#'; });
            }

            function targetExists(target) {
                return !!(target && document.querySelector(target));
            }

            function normalizeTargetForStep(stepBtn, target) {
                var tabs = getTabsForStep(stepBtn);
                if (target && tabs.indexOf(target) >= 0 && targetExists(target)) {
                    return target;
                }
                var firstExisting = tabs.find(function (tabTarget) {
                    return targetExists(tabTarget);
                });
                if (firstExisting) return firstExisting;
                var firstPane = allPanes[0] || null;
                return firstPane && firstPane.id ? ('#' + firstPane.id) : null;
            }

            function sectionIdForTarget(target) {
                return 've-sec-' + String(target || '').replace('#', '');
            }

            function stickyOffset() {
                var tabsWrap = workflow.closest('.ve-tabs-wrapper--workflow') || workflow;
                var tabsHeight = tabsWrap ? tabsWrap.getBoundingClientRect().height : 0;
                return Math.max(84, Math.round(tabsHeight + 30));
            }

            function ensureSectionAnchors(stepBtn) {
                var targets = getTabsForStep(stepBtn);
                targets.forEach(function (target) {
                    var pane = document.querySelector(target);
                    if (!pane) return;
                    var secId = sectionIdForTarget(target);
                    var existing = pane.querySelector('.ve-step-anchor[data-ve-anchor="' + secId + '"]');
                    if (!existing) {
                        var anchor = document.createElement('span');
                        anchor.className = 've-step-anchor';
                        anchor.setAttribute('data-ve-anchor', secId);
                        anchor.setAttribute('id', secId);
                        anchor.setAttribute('aria-hidden', 'true');
                        pane.insertBefore(anchor, pane.firstChild);
                    }
                });
            }

            function titleForTarget(target) {
                var pane = document.querySelector(target);
                if (!pane) return target.replace('#', '');
                var t = pane.getAttribute('data-ve-pane-title');
                return t && t.trim() !== '' ? t : target.replace('#', '');
            }

            function activateDetailItem(target) {
                if (!detailNav) return;
                detailNav.querySelectorAll('[data-ve-detail-target]').forEach(function (item) {
                    item.classList.toggle('is-active', item.getAttribute('data-ve-detail-target') === target);
                });
            }

            function scrollToTarget(target) {
                if (!target) return;
                var section = document.getElementById(sectionIdForTarget(target))
                    || document.querySelector(target + '.ve-step-visible')
                    || document.querySelector(target);
                if (!section) return;
                var top = section.getBoundingClientRect().top + window.pageYOffset - stickyOffset();
                window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
            }

            function renderDetailNav(stepBtn, activeTarget) {
                if (!detailNav) return;
                var targets = getTabsForStep(stepBtn);
                if (!targets.length) {
                    detailNav.innerHTML = '';
                    return;
                }
                ensureSectionAnchors(stepBtn);
                detailNav.innerHTML = targets.map(function (target) {
                    var isActive = target === activeTarget;
                    return '<button type="button" class="ve-detail-nav__item' + (isActive ? ' is-active' : '') + '" data-ve-detail-target="' + target + '">' +
                        '<span class="ve-detail-nav__dot"></span>' +
                        '<span class="ve-detail-nav__label">' + titleForTarget(target) + '</span>' +
                    '</button>';
                }).join('');

                detailNav.querySelectorAll('[data-ve-detail-target]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var target = btn.getAttribute('data-ve-detail-target');
                        if (!target) return;
                        var changed = showTab(target);
                        if (!changed) return;
                        syncUIFromTarget(target);
                        window.requestAnimationFrame(function () {
                            scrollToTarget(target);
                        });
                    });
                });
            }

            function paintStepPanes(stepBtn, activeTarget) {
                if (!tabContent || !allPanes.length || !stepBtn) return;
                tabContent.classList.add('ve-tab-content--workflow');
                var visibleTargets = getTabsForStep(stepBtn);
                allPanes.forEach(function (pane) {
                    var id = pane.getAttribute('id') || '';
                    var target = id ? ('#' + id) : '';
                    var visible = visibleTargets.indexOf(target) >= 0 && target === activeTarget;
                    pane.classList.toggle('show', visible);
                    pane.classList.toggle('active', visible);
                    pane.classList.toggle('ve-step-visible', visible);
                    pane.setAttribute('aria-hidden', visible ? 'false' : 'true');
                });
            }

            function syncUIFromTarget(target) {
                var step = getStepForTarget(target) || stepButtons[0] || null;
                if (!step) return;
                var activeTarget = normalizeTargetForStep(step, target);

                stepButtons.forEach(function (btn) {
                    btn.classList.toggle('is-active', btn === step);
                });

                paintStepPanes(step, activeTarget);
                renderDetailNav(step, activeTarget);
                if (activeTarget) activateDetailItem(activeTarget);

                if (currentLabel) {
                    currentLabel.textContent = step.getAttribute('data-ve-step-label') || '';
                }

                var currentIndex = stepButtons.indexOf(step);
                if (prevBtn) prevBtn.disabled = currentIndex <= 0;
                if (nextBtn) nextBtn.disabled = currentIndex >= stepButtons.length - 1;
            }

            stepButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = btn.getAttribute('data-ve-step-target');
                    if (!target) return;
                    var changed = showTab(target);
                    if (!changed) return;
                    syncUIFromTarget(target);
                });
            });

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    var active = workflow.querySelector('[data-ve-step].is-active');
                    var index = stepButtons.indexOf(active);
                    if (index <= 0) return;
                    var prevStep = stepButtons[index - 1];
                    var target = prevStep ? prevStep.getAttribute('data-ve-step-target') : null;
                    if (target && showTab(target)) {
                        syncUIFromTarget(target);
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    var active = workflow.querySelector('[data-ve-step].is-active');
                    var index = stepButtons.indexOf(active);
                    if (index < 0 || index >= stepButtons.length - 1) return;
                    var nextStep = stepButtons[index + 1];
                    var target = nextStep ? nextStep.getAttribute('data-ve-step-target') : null;
                    if (target && showTab(target)) {
                        syncUIFromTarget(target);
                    }
                });
            }

            document.addEventListener('shown.bs.tab', function (e) {
                var target = e && e.target ? e.target.getAttribute('href') : null;
                if (!target || target.charAt(0) !== '#') return;
                syncUIFromTarget(target);
            });

            var activeTab = document.querySelector('.ve-nav-tabs .nav-link.active[data-bs-toggle="tab"]');
            syncUIFromTarget(activeTab ? activeTab.getAttribute('href') : '#basic');
        });
    </script>
@endpush
