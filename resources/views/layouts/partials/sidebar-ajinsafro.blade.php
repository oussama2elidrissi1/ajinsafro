<!-- ========== Left Sidebar Start (AjinsAfro) ========== -->
<!-- DIAG_MARKER: SIDEBAR_AJINSAFRO_RENDER_BEGIN [{{ \Illuminate\Support\Str::random(6) }}] file=resources/views/layouts/partials/sidebar-ajinsafro.blade.php -->
<div class="vertical-menu">
    <div class="h-100">
        @include('admin.partials.sidebar-v2', ['sidebarContext' => 'admin-classic'])
    </div>
</div>
<!-- DIAG_MARKER: SIDEBAR_AJINSAFRO_RENDER_END -->
<!-- Left Sidebar End -->

@push('body-end')
    <script src="{{ URL::asset('js/admin-sidebar-v2.js') }}"></script>
@endpush
