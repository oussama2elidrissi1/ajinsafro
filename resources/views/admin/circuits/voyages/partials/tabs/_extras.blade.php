<div class="tab-pane" id="voyage-extras" role="tabpanel">
    <div class="card ve-pane-card">
        <div class="card-body">
            <p class="ve-section-kicker mb-2">Services additionnels</p>
            <h4 class="card-title mb-2">Extras de reservation</h4>
            <p class="text-muted small mb-3">Configurez les options, supplements et activites proposes au moment de la reservation pour ce voyage.</p>

            @include('admin.circuits.voyages.partials._voyage_extras', ['voyageExtras' => $voyageExtras ?? collect()])
        </div>
    </div>
</div>
