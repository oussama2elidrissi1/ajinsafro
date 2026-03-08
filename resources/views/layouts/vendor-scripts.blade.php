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
    function stripRequiredFromHiddenInForm(form) {
        var list = [];
        if (!form) return list;
        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function(el) {
            var inHiddenModal = el.closest('.modal') && !el.closest('.modal').classList.contains('show');
            var inHiddenOffcanvas = el.closest('.offcanvas') && !el.closest('.offcanvas').classList.contains('show');
            var inInactiveTab = el.closest('.tab-pane') && !el.closest('.tab-pane').classList.contains('active');
            var inHidden = el.closest('[hidden]');
            if (inHiddenModal || inHiddenOffcanvas || inInactiveTab || inHidden) {
                list.push(el);
                el.removeAttribute('required');
                el.setAttribute('data-required-restore', '1');
            }
        });
        return list;
    }
    function restoreRequired(list) {
        list.forEach(function(el) {
            if (el.getAttribute('data-required-restore') === '1') {
                el.setAttribute('required', 'required');
                el.removeAttribute('data-required-restore');
            }
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(function(form) {
            var btn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (btn) {
                btn.addEventListener('click', function(e) {
                    var toRestore = stripRequiredFromHiddenInForm(form);
                    if (toRestore.length > 0) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                        setTimeout(function() { restoreRequired(toRestore); }, 100);
                        return false;
                    }
                }, true);
            }
        });
    });
})();
</script>