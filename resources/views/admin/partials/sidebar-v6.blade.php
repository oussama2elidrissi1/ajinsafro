<div class="admin-v6-sidebar-head">
    <button type="button" class="admin-v6-sidebar-toggle" id="adminV6SidebarToggle" aria-label="Réduire / ouvrir la sidebar">
        <i class="bx bx-menu"></i>
    </button>
</div>
@include('admin.partials.sidebar-v2', ['sidebarContext' => $sidebarContext ?? 'admin-v6'])
