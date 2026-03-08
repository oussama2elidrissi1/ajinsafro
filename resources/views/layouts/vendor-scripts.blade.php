<!-- JAVASCRIPT -->
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/node-waves/waves.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/jquery-sparkline/jquery.sparkline.min.js') }}"></script>
@stack('script')
{{-- Éviter "An invalid form control with name='...' is not focusable" : champs required dans modals/onglets cachés --}}
<script>
(function() {
    var RESTORE_ATTR = 'data-required-restore';

    function isElementHidden(el) {
        if (!el || !(el instanceof Element)) return false;

        if (el.closest('[hidden]')) return true;
        if (el.closest('.d-none')) return true;

        var modal = el.closest('.modal');
        if (modal && !modal.classList.contains('show')) return true;

        var offcanvas = el.closest('.offcanvas');
        if (offcanvas && !offcanvas.classList.contains('show')) return true;

        var tabPane = el.closest('.tab-pane');
        if (tabPane && !tabPane.classList.contains('active')) return true;

        var collapse = el.closest('.collapse');
        if (collapse && !collapse.classList.contains('show')) return true;

        var current = el;
        while (current && current !== document.documentElement) {
            var style = window.getComputedStyle(current);
            if (style.display === 'none' || style.visibility === 'hidden') {
                return true;
            }
            current = current.parentElement;
        }

        return el.getClientRects().length === 0;
    }

    function stripRequiredFromHiddenInForm(form) {
        var list = [];
        if (!form) return list;

        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function(el) {
            if (el.disabled) return;
            if (!isElementHidden(el)) return;

            list.push(el);
            el.removeAttribute('required');
            el.setAttribute(RESTORE_ATTR, '1');
        });

        return list;
    }

    function restoreRequired(list) {
        list.forEach(function(el) {
            if (el && el.getAttribute && el.getAttribute(RESTORE_ATTR) === '1') {
                el.setAttribute('required', 'required');
                el.removeAttribute(RESTORE_ATTR);
            }
        });
    }

    function queueRestore(form, delayMs) {
        if (!form) return;

        if (form.__requiredRestoreTimer) {
            clearTimeout(form.__requiredRestoreTimer);
        }

        form.__requiredRestoreTimer = setTimeout(function() {
            var list = form.__requiredRestoreList || [];
            restoreRequired(list);
            form.__requiredRestoreList = [];
            form.__requiredRestoreTimer = null;
        }, delayMs);
    }

    function prepareForm(form) {
        if (!form) return;

        var stripped = stripRequiredFromHiddenInForm(form);
        if (!form.__requiredRestoreList) {
            form.__requiredRestoreList = [];
        }

        if (stripped.length > 0) {
            form.__requiredRestoreList = form.__requiredRestoreList.concat(stripped);
            queueRestore(form, 200);
        }
    }

    function resolveSubmitterForm(submitter) {
        if (!submitter) return null;
        if (submitter.form) return submitter.form;

        var formId = submitter.getAttribute('form');
        return formId ? document.getElementById(formId) : null;
    }

    document.addEventListener('click', function(event) {
        var target = event.target;
        if (!target || !target.closest) return;

        var submitter = target.closest('button[type="submit"], input[type="submit"], button:not([type])');
        if (!submitter) return;

        prepareForm(resolveSubmitterForm(submitter));
    }, true);

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Enter' || event.defaultPrevented || event.isComposing) return;

        var target = event.target;
        if (!target) return;
        if (target.tagName === 'TEXTAREA' || target.isContentEditable) return;

        var form = target.form || (target.closest ? target.closest('form') : null);
        prepareForm(form);
    }, true);

    document.addEventListener('submit', function(event) {
        if (event.target && event.target.tagName === 'FORM') {
            prepareForm(event.target);
            queueRestore(event.target, 200);
        }
    }, true);

    document.addEventListener('invalid', function(event) {
        var form = event.target && event.target.form;
        if (form) queueRestore(form, 0);
    }, true);

    if (
        typeof HTMLFormElement !== 'undefined' &&
        HTMLFormElement.prototype &&
        typeof HTMLFormElement.prototype.requestSubmit === 'function' &&
        !HTMLFormElement.prototype.__hiddenRequiredPatched
    ) {
        var nativeRequestSubmit = HTMLFormElement.prototype.requestSubmit;

        HTMLFormElement.prototype.requestSubmit = function(submitter) {
            prepareForm(this);
            try {
                return nativeRequestSubmit.call(this, submitter);
            } finally {
                queueRestore(this, 200);
            }
        };

        HTMLFormElement.prototype.__hiddenRequiredPatched = true;
    }
})();
</script>
