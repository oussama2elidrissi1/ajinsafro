<p class="ve-tab-zone-hint text-muted small mb-2 d-none d-lg-flex align-items-center gap-2">
    <i class="bx bx-folder-open"></i>
    <span>Onglets : Basique, Info, Disponibilite, Medias... defilez pour Vols, Hotels, Programme.</span>
</p>

<div class="card ve-pane-card ve-form-actions-card">
    <div class="card-body ve-form-actions-body">
        <div>
            <h5 class="mb-1">Actions</h5>
            <p class="text-muted small mb-0">Enregistrez vos modifications a tout moment sans quitter la page.</p>
        </div>
        <div class="ve-form-actions-buttons">
            <button type="submit" form="edit-voyage-form" class="btn btn-primary" id="edit-voyage-submit-btn">
                <i class="bx bx-save"></i> Enregistrer
            </button>
            <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-x"></i> Annuler
            </a>
        </div>
    </div>
</div>
