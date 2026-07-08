@php
    $agentVoyageMode = (bool) ($agentVoyageMode ?? request()->routeIs('agent.voyages.*'));
    $voyageBackUrl = $voyageBackUrl ?? ($agentVoyageMode ? route('agent.catalogue') : route('admin.circuits.voyages.index'));
@endphp

<div class="ve-form-toolbar ve-pane-card">
    <div class="ve-form-actions-body">
        <div class="ve-form-toolbar__copy">
            <p class="ve-form-toolbar__eyebrow mb-0">Sauvegarde rapide</p>
            <h5 class="mb-0">Enregistrer sans quitter l?Tédition</h5>
        </div>

        <div class="ve-form-actions-buttons">
            <a href="{{ $voyageBackUrl }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back"></i> Retour a la liste
            </a>

            <button type="submit" form="edit-voyage-form" class="btn btn-primary" id="edit-voyage-submit-btn">
                <i class="bx bx-save"></i> Enregistrer
            </button>
        </div>
    </div>
</div>
