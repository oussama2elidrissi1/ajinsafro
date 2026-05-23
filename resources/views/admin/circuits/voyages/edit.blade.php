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
    // Résolution destination : priorité meta WP address > multi_location > Laravel destination
    $veDestination = null;
    if ($veWpId > 0) {
        $wpAddress = null;
        try {
            $wpPost = \App\Models\Wp\WpPost::tours()->find($veWpId);
            if ($wpPost) {
                $wpAddress = $wpPost->getMeta('address');
            }
        } catch (\Throwable $e) {
            \Log::warning('edit.blade: failed reading WP address meta', ['wp_post_id' => $veWpId, 'error' => $e->getMessage()]);
        }
        if (is_string($wpAddress) && trim($wpAddress) !== '') {
            $veDestination = trim(preg_split('/[,;|]/', $wpAddress)[0] ?? $wpAddress);
        } else {
            try {
                $multiLoc = $wpPost ? $wpPost->getMeta('multi_location') : null;
                $locNames = app(\App\Services\Wp\WpTourRepository::class)->getLocationNamesFromMultiLocation($multiLoc);
                if ($locNames !== '') {
                    $veDestination = $locNames;
                }
            } catch (\Throwable $e) {
                \Log::warning('edit.blade: failed reading WP locations', ['wp_post_id' => $veWpId, 'error' => $e->getMessage()]);
            }
        }
    }
    if (! $veDestination && $laravelV) {
        $laravelDest = data_get($laravelV, 'destination');
        if ($laravelDest !== null && trim((string) $laravelDest) !== '') {
            $veDestination = trim((string) $laravelDest);
        }
    }
    $veDatesCount = isset($travelDates) && $travelDates instanceof \Illuminate\Support\Collection ? $travelDates->count() : 0;
@endphp
@extends('layouts.admin-v6')

@section('title')
    {{ $isCreate ? 'Creer un tour WordPress' : 'Modifier le tour WordPress' }}
@endsection

@push('styles')
    <link href="{{ URL::asset('css/voyage-edit.css?v=' . $voyageEditCssVersion) }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/flight-options-new.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/voyage-edit-fix.css') }}?v={{ filemtime(public_path('css/voyage-edit-fix.css')) }}">
@endpush

@section('content')
<div class="voyage-edit-page voyage-workflow-page workflow-collapsed">
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

    <div class="ve-shell">
        <div id="ve-tab-guard-alert" class="alert alert-warning d-none mb-3 ve-tab-guard-alert" role="alert"></div>
    </div>

    <form action="{{ $isCreate ? route('admin.circuits.voyages.store') : route('admin.circuits.voyages.update', $voyage->ID) }}" method="POST" id="edit-voyage-form" data-voyage-id="{{ $voyage->ID ?? 0 }}" novalidate>
        @csrf
        @if (!$isCreate)
            @method('PUT')
        @endif
        <textarea name="programme_days_payload" id="programme-days-payload" class="d-none" aria-hidden="true"></textarea>

        <div class="ve-shell">
            <div class="voyage-editor-grid">
                <aside class="workflow-sidebar ve-left">
                    @include('admin.circuits.voyages.partials._voyage_tabs_nav')
                </aside>

                <div class="voyage-editor-main workflow-main editor-main ve-content">
                    <div class="ve-editor-frame">
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

                            <div class="editor-save-bar">
                                <button type="button" class="btn btn-outline-primary" data-ve-step-next-secondary>
                                    �?tape suivante : Tarifs & capacité <i class="bx bx-chevron-right"></i>
                                </button>
                                <button type="submit" form="edit-voyage-form" class="btn btn-primary" id="edit-voyage-submit-btn">
                                    <i class="bx bx-save"></i> Enregistrer cette étape
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if (!$isCreate && $laravelV)
        @include('admin.circuits.voyages.partials.room_availability.modal', [
            'voyage' => $laravelV,
            'wpTourPostId' => $veWpId,
            'serverWpTravelDatesCount' => isset($travelDates) && $travelDates instanceof \Illuminate\Support\Collection ? $travelDates->count() : 0,
            'serverLaravelDeparturesCount' => (int) ($laravelV->departures()->count() ?? 0),
        ])
    @endif

    @if (!$isCreate)
        <form id="delete-voyage-form" action="{{ route('admin.circuits.voyages.destroy', $voyage->ID) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif

</div>
@endsection

@push('scripts')
    @include('admin.circuits.voyages.partials._voyage_page_bootstrap')
    <script src="{{ URL::asset('build/libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="{{ URL::asset('js/voyage-editor-runtime.js') }}"></script>
    <script src="{{ URL::asset('js/voyage-edit-page.js?v=' . $voyageEditJsVersion) }}"></script>
    <script src="{{ URL::asset('js/voyage-room-availability-modal.js') }}"></script>
    <script src="{{ URL::asset('js/flight-options-fix.js') }}"></script>
    <script src="{{ URL::asset('js/flight-options-manager.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var page = document.querySelector('.voyage-edit-page');
            var btn = document.getElementById('workflowToggleBtn');

            if (!page) {
                console.error('voyage-edit-page introuvable');
                return;
            }

            page.classList.add('workflow-collapsed');

            if (!btn) {
                console.warn('Workflow toggle introuvable', { page: page, btn: btn });
            } else {
                btn.addEventListener('click', function () {
                    page.classList.toggle('workflow-collapsed');
                });
            }

            var workflow = document.querySelector('[data-ve-workflow]');
            if (!workflow) return;

            var stepButtons = Array.prototype.slice.call(workflow.querySelectorAll('[data-ve-step]'));
            var prevBtn = workflow.querySelector('[data-ve-step-prev]');
            var nextBtn = workflow.querySelector('[data-ve-step-next]');
            var nextSecondaryButtons = Array.prototype.slice.call(document.querySelectorAll('[data-ve-step-next-secondary]'));
            var resumeWorkflowLinks = Array.prototype.slice.call(document.querySelectorAll('[data-ve-resume-workflow]'));
            var currentLabel = workflow.querySelector('[data-ve-current-step-label]');
            var tabContent = document.querySelector('.ve-tab-content');
            var allPanes = tabContent ? Array.prototype.slice.call(tabContent.querySelectorAll('.tab-pane')).filter(function (pane) {
                return pane.parentElement === tabContent;
            }) : [];
            var detailNav = workflow.querySelector('[data-ve-detail-nav]');
            var form = document.getElementById('edit-voyage-form');
            var guardAlert = document.getElementById('ve-tab-guard-alert');
            var submitBtn = document.getElementById('edit-voyage-submit-btn');
            var submitting = false;
            var paneSnapshots = {};
            var sectionLabels = {
                '#basic': 'Basique',
                '#location': 'Destination',
                '#price': 'Prix & Paiement',
                '#information': 'Détails',
                '#voyage-extras': 'Extras',
                '#availability': 'Départs',
                '#flights': 'Vols',
                '#media': 'Médias',
                '#taxonomies': 'Classement',
                '#logistics': 'Logistique',
                '#hotels': 'Hôtels',
                '#activities': 'Activités',
                '#program-days': 'Programme'
            };

            function updateWorkflowStepStatuses() {
                var steps = Array.prototype.slice.call(workflow.querySelectorAll('.ve-stepper__step'));
                if (!steps.length) return;

                var activeIndex = -1;
                steps.forEach(function (step, index) {
                    if (step.classList.contains('is-active')) {
                        activeIndex = index;
                    }
                });

                steps.forEach(function (step, index) {
                    var statusEl = step.querySelector('.step-status');
                    if (!statusEl) return;
                    statusEl.textContent = activeIndex >= 0 && index < activeIndex ? 'Validée' : 'À compléter';
                });
            }
            updateWorkflowStepStatuses();

            function escapeHtml(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function paneTarget(pane) {
                return pane && pane.id ? ('#' + pane.id) : null;
            }

            function getPane(target) {
                return target ? document.querySelector(target) : null;
            }

            function getActiveTarget() {
                var activeLink = document.querySelector('.ve-nav-tabs .nav-link.active[data-bs-toggle="tab"]');
                if (activeLink && activeLink.getAttribute('href')) {
                    return activeLink.getAttribute('href');
                }
                var activePane = document.querySelector('.ve-tab-content .tab-pane.active');
                return activePane && activePane.id ? ('#' + activePane.id) : '#basic';
            }

            function stepButtonForTarget(target) {
                return stepButtons.find(function (btn) {
                    return String(btn.getAttribute('data-ve-step-target') || '') === target;
                }) || null;
            }

            function detailButtonForTarget(target) {
                if (!detailNav) return null;
                return detailNav.querySelector('[data-ve-detail-target="' + target + '"]');
            }

            function labelForTarget(target) {
                var pane = getPane(target);
                if (sectionLabels[target]) {
                    return sectionLabels[target];
                }
                if (pane) {
                    var paneLabel = pane.getAttribute('data-ve-pane-title');
                    if (paneLabel && paneLabel.trim() !== '') {
                        return paneLabel.trim();
                    }
                }
                return target ? target.replace('#', '') : '';
            }

            function fieldValue(field) {
                if (!field) return '';
                if (field.type === 'checkbox') {
                    return field.checked ? '1' : '0';
                }
                if (field.type === 'radio') {
                    return field.checked ? String(field.value || '') : '';
                }
                if (field.tagName === 'SELECT' && field.multiple) {
                    return Array.prototype.slice.call(field.selectedOptions).map(function (option) {
                        return option.value;
                    }).join('|');
                }
                return String(field.value == null ? '' : field.value);
            }

            function snapshotPane(pane) {
                return Array.prototype.slice.call(pane.querySelectorAll('input,select,textarea')).filter(function (field) {
                    return !!field.name && field.type !== 'button' && field.type !== 'submit' && field.type !== 'reset';
                }).map(function (field) {
                    return field.name + '=' + fieldValue(field);
                }).join('\u0001');
            }

            function capturePaneSnapshot(pane) {
                var target = paneTarget(pane);
                if (!target) return;
                paneSnapshots[target] = snapshotPane(pane);
            }

            function clearFieldValidation(field) {
                if (!field) return;
                field.classList.remove('is-invalid');
                field.removeAttribute('aria-invalid');
            }

            function firstInvalidField(pane) {
                var fields = Array.prototype.slice.call(pane.querySelectorAll('input,select,textarea')).filter(function (field) {
                    return !!field.name && !field.disabled && field.type !== 'hidden' && field.type !== 'button' && field.type !== 'submit' && field.type !== 'reset';
                });

                for (var i = 0; i < fields.length; i++) {
                    var field = fields[i];
                    if (typeof field.checkValidity === 'function' && !field.checkValidity()) {
                        return field;
                    }
                }

                return null;
            }

            function isPaneDirty(pane) {
                var target = paneTarget(pane);
                if (!target) return false;
                if (typeof paneSnapshots[target] === 'undefined') return false;
                return snapshotPane(pane) !== (paneSnapshots[target] || '');
            }

            function applyTabState(target, state) {
                var link = tabLink(target);
                var stepBtn = stepButtonForTarget(target);
                var detailBtn = detailButtonForTarget(target);

                [link, stepBtn, detailBtn].forEach(function (el) {
                    if (!el) return;
                    el.classList.remove('ve-tab-state--dirty', 've-tab-state--invalid');
                    if (state === 'dirty') {
                        el.classList.add('ve-tab-state--dirty');
                    } else if (state === 'invalid') {
                        el.classList.add('ve-tab-state--invalid');
                    }
                });
            }

            function updatePaneState(pane) {
                if (!pane) return { state: 'clean', field: null };
                var target = paneTarget(pane);
                var invalidField = firstInvalidField(pane);
                var state = 'clean';

                if (invalidField) {
                    state = 'invalid';
                    invalidField.classList.add('is-invalid');
                    invalidField.setAttribute('aria-invalid', 'true');
                } else if (isPaneDirty(pane)) {
                    state = 'dirty';
                }

                pane.dataset.veState = state;
                applyTabState(target, state);
                return { state: state, field: invalidField };
            }

            function syncAllPaneStates() {
                allPanes.forEach(function (pane) {
                    updatePaneState(pane);
                });
            }

            function showGuardMessage(kind, label) {
                if (!guardAlert) return;
                var message, alertClass, iconClass;
                if (kind === 'invalid') {
                    message = 'La section \u00ab\u00a0' + label + '\u00a0\u00bb contient des champs obligatoires non remplis.';
                    alertClass = 'alert-danger';
                    iconClass = 'bx-error-circle';
                } else if (kind === 'success') {
                    message = 'Enregistr\u00e9. Vous \u00eates maintenant dans\u00a0: ' + label + '.';
                    alertClass = 'alert-success';
                    iconClass = 'bx-check-circle';
                } else {
                    message = 'Vous avez des modifications non enregistr\u00e9es dans \u00ab\u00a0' + label + '\u00a0\u00bb. Veuillez enregistrer avant de changer de section.';
                    alertClass = 'alert-warning';
                    iconClass = 'bx-info-circle';
                }
                guardAlert.classList.remove('d-none', 'alert-danger', 'alert-warning', 'alert-info', 'alert-success');
                guardAlert.classList.add(alertClass);
                guardAlert.innerHTML = '<i class="bx ' + iconClass + ' me-2"></i><span>' + escapeHtml(message) + '</span>';
                guardAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function hideGuardMessage() {
                if (!guardAlert) return;
                guardAlert.classList.add('d-none');
                guardAlert.classList.remove('alert-danger', 'alert-warning', 'alert-info', 'alert-success');
                guardAlert.innerHTML = '';
            }

            function scrollToField(field) {
                if (!field) return;
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                try {
                    field.focus({ preventScroll: true });
                } catch (e) {
                    field.focus();
                }
            }

            function requestTabChange(target) {
                if (!target) return false;
                if (target === getActiveTarget()) return true;

                var activeTarget = getActiveTarget();
                var activePane = getPane(activeTarget);
                if (activePane) {
                    var state = updatePaneState(activePane);
                    if (state.state !== 'clean') {
                        showGuardMessage(state.state, labelForTarget(activeTarget));
                        if (state.field) {
                            scrollToField(state.field);
                        }
                        return false;
                    }
                }

                hideGuardMessage();
                return showTab(target);
            }

            function firstInvalidFieldInForm() {
                for (var i = 0; i < allPanes.length; i++) {
                    var invalidField = firstInvalidField(allPanes[i]);
                    if (invalidField) {
                        return { pane: allPanes[i], field: invalidField };
                    }
                }

                return null;
            }

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

            function getNextSubSectionTarget(fromTarget) {
                var activeTarget = fromTarget || getActiveTarget();
                var currentStep = getStepForTarget(activeTarget);
                if (!currentStep) return null;
                var tabs = getTabsForStep(currentStep);
                var idx = tabs.indexOf(activeTarget);
                // Next sub-section within same step
                if (idx >= 0 && idx < tabs.length - 1) return tabs[idx + 1];
                // Last sub-section �?" first tab of next step
                var stepIndex = stepButtons.indexOf(currentStep);
                if (stepIndex >= 0 && stepIndex < stepButtons.length - 1) {
                    var nextStep = stepButtons[stepIndex + 1];
                    var nextTabs = getTabsForStep(nextStep);
                    return nextTabs.length > 0 ? nextTabs[0] : null;
                }
                return null;
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
                        var changed = requestTabChange(target);
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
                updateWorkflowStepStatuses();

                paintStepPanes(step, activeTarget);
                renderDetailNav(step, activeTarget);
                if (activeTarget) activateDetailItem(activeTarget);
                syncAllPaneStates();

                if (currentLabel) {
                    currentLabel.textContent = step.getAttribute('data-ve-step-label') || '';
                }

                var currentIndex = stepButtons.indexOf(step);
                var tabs = getTabsForStep(step);
                var subIdx = tabs.indexOf(activeTarget);
                if (prevBtn) prevBtn.disabled = (currentIndex <= 0 && subIdx <= 0);
                if (nextBtn) nextBtn.disabled = (getNextSubSectionTarget(activeTarget) === null);
            }

            stepButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = btn.getAttribute('data-ve-step-target');
                    if (!target) return;
                    var changed = requestTabChange(target);
                    if (!changed) return;
                    syncUIFromTarget(target);
                });
            });

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    var activeTarget = getActiveTarget();
                    var currentStep = getStepForTarget(activeTarget);
                    if (!currentStep) return;

                    var tabs = getTabsForStep(currentStep);
                    var idx = tabs.indexOf(activeTarget);

                    var prevTarget;
                    if (idx > 0) {
                        // Previous sub-section within same step
                        prevTarget = tabs[idx - 1];
                    } else {
                        // First sub-section of step �?" go to last sub-section of previous step
                        var stepIndex = stepButtons.indexOf(currentStep);
                        if (stepIndex <= 0) return;
                        var prevStep = stepButtons[stepIndex - 1];
                        var prevTabs = getTabsForStep(prevStep);
                        prevTarget = prevTabs.length > 0 ? prevTabs[prevTabs.length - 1] : prevStep.getAttribute('data-ve-step-target');
                    }

                    if (!prevTarget) return;
                    hideGuardMessage();
                    showTab(prevTarget);
                    syncUIFromTarget(prevTarget);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    var activeTarget = getActiveTarget();
                    var nextTarget = getNextSubSectionTarget(activeTarget);
                    if (!nextTarget) return;

                    var activePane = getPane(activeTarget);
                    if (activePane) {
                        var state = updatePaneState(activePane);
                        if (state.state === 'invalid') {
                            showGuardMessage('invalid', labelForTarget(activeTarget));
                            if (state.field) scrollToField(state.field);
                            return;
                        }
                        if (state.state === 'dirty') {
                            // Store where to go after save, then submit form
                            try { sessionStorage.setItem('ve_pending_next_tab', nextTarget); } catch (e) {}
                            if (form) {
                                if (typeof form.requestSubmit === 'function') {
                                    form.requestSubmit();
                                } else if (submitBtn) {
                                    submitBtn.click();
                                } else {
                                    form.submit();
                                }
                            }
                            return;
                        }
                    }

                    // Pane is clean �?" navigate directly
                    hideGuardMessage();
                    showTab(nextTarget);
                    syncUIFromTarget(nextTarget);
                    showGuardMessage('success', labelForTarget(nextTarget));
                    setTimeout(function () { hideGuardMessage(); }, 3500);
                });
            }

            if (nextSecondaryButtons.length) {
                nextSecondaryButtons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (nextBtn) {
                            nextBtn.click();
                        }
                    });
                });
            }

            if (resumeWorkflowLinks.length) {
                resumeWorkflowLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var target = '#basic';
                        var changed = requestTabChange(target);
                        if (!changed) return;
                        syncUIFromTarget(target);
                        var workflowTop = workflow.getBoundingClientRect().top + window.pageYOffset - 90;
                        window.scrollTo({ top: Math.max(0, workflowTop), behavior: 'smooth' });
                    });
                });
            }

            document.addEventListener('click', function (e) {
                var link = e.target && e.target.closest ? e.target.closest('.ve-nav-tabs .nav-link[data-bs-toggle="tab"]') : null;
                if (!link) return;
                var target = link.getAttribute('href');
                if (!target || target === getActiveTarget()) return;
                if (!requestTabChange(target)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);

            if (form) {
                form.addEventListener('input', function (e) {
                    var field = e.target;
                    var pane = field && field.closest ? field.closest('.tab-pane') : null;
                    if (!pane) return;
                    clearFieldValidation(field);
                    updatePaneState(pane);
                }, true);

                form.addEventListener('change', function (e) {
                    var field = e.target;
                    var pane = field && field.closest ? field.closest('.tab-pane') : null;
                    if (!pane) return;
                    clearFieldValidation(field);
                    updatePaneState(pane);
                }, true);

                form.addEventListener('submit', function (e) {
                    if (submitting) {
                        e.preventDefault();
                        return;
                    }

                    syncAllPaneStates();

                    var firstInvalid = firstInvalidFieldInForm();
                    if (firstInvalid) {
                        e.preventDefault();
                        submitting = false;
                        if (form) {
                            form.dataset.isSubmitting = '0';
                        }
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('disabled');
                            submitBtn.removeAttribute('disabled');
                        }
                        var invalidTarget = paneTarget(firstInvalid.pane);
                        showGuardMessage('invalid', labelForTarget(invalidTarget));
                        if (invalidTarget && invalidTarget !== getActiveTarget() && showTab(invalidTarget)) {
                            syncUIFromTarget(invalidTarget);
                        }
                        updatePaneState(firstInvalid.pane);
                        window.requestAnimationFrame(function () {
                            scrollToField(firstInvalid.field);
                        });
                        return;
                    }

                    hideGuardMessage();
                    submitting = true;
                    if (submitBtn) {
                        submitBtn.disabled = true;
                    }
                });
            }

            function initialisePaneTracking() {
                allPanes.forEach(function (pane) {
                    capturePaneSnapshot(pane);
                });

                syncAllPaneStates();

                if (window.MutationObserver) {
                    allPanes.forEach(function (pane) {
                        var observer = new MutationObserver(function () {
                            updatePaneState(pane);
                        });
                        observer.observe(pane, { childList: true, subtree: true });
                    });
                }
            }

            document.addEventListener('shown.bs.tab', function (e) {
                var target = e && e.target ? e.target.getAttribute('href') : null;
                if (!target || target.charAt(0) !== '#') return;
                syncUIFromTarget(target);
            });

            // After-save pending navigation: if a save was triggered by "�?tape suivante",
            // redirect to the target section that was stored before submit.
            var _pendingTab = (function () {
                try { return sessionStorage.getItem('ve_pending_next_tab'); } catch (e) { return null; }
            })();
            if (_pendingTab) {
                try { sessionStorage.removeItem('ve_pending_next_tab'); } catch (e) {}
            }

            var activeTab = document.querySelector('.ve-nav-tabs .nav-link.active[data-bs-toggle="tab"]');
            var _initialTarget = _pendingTab && targetExists(_pendingTab) ? _pendingTab : (activeTab ? activeTab.getAttribute('href') : '#basic');
            syncUIFromTarget(_initialTarget);
            if (_pendingTab && targetExists(_pendingTab)) {
                showTab(_pendingTab);
            }
            initialisePaneTracking();

            if (_pendingTab && targetExists(_pendingTab)) {
                setTimeout(function () {
                    showGuardMessage('success', labelForTarget(_pendingTab));
                    setTimeout(function () { hideGuardMessage(); }, 4000);
                }, 400);
            }
        });
    </script>
@endpush


