<div class="ve-tabs-wrapper ve-tabs-wrapper--workflow" data-ve-workflow>
    <div class="ve-tabs-header">
        <div>
            <p class="ve-tabs-kicker mb-1">Parcours de saisie</p>
            <h6 class="ve-tabs-title mb-0">�?dition du voyage par étapes</h6>
        </div>

        <p class="ve-tab-zone-hint text-muted small mb-0">
            �?tape actuelle: <strong class="ve-stepper-current" data-ve-current-step-label>Informations générales</strong>
        </p>
    </div>

    <div class="ve-stepper" role="tablist" aria-label="�?tapes du voyage">
        <button type="button" class="ve-stepper__step is-active"
            data-ve-step="1" data-ve-step-label="Informations générales" data-ve-step-target="#basic"
            data-ve-step-tabs="#basic,#location,#information">
            <span class="ve-stepper__index">1</span>
            <span class="ve-stepper__text">Informations générales</span>
        </button>
        <button type="button" class="ve-stepper__step"
            data-ve-step="2" data-ve-step-label="Offre commerciale" data-ve-step-target="#price"
            data-ve-step-tabs="#price,#voyage-extras,#taxonomies">
            <span class="ve-stepper__index">2</span>
            <span class="ve-stepper__text">Offre commerciale</span>
        </button>
        <button type="button" class="ve-stepper__step"
            data-ve-step="3" data-ve-step-label="Exploitation / disponibilité" data-ve-step-target="#availability"
            data-ve-step-tabs="#availability,#logistics">
            <span class="ve-stepper__index">3</span>
            <span class="ve-stepper__text">Exploitation / disponibilité</span>
        </button>
        <button type="button" class="ve-stepper__step"
            data-ve-step="4" data-ve-step-label="Contenu du voyage" data-ve-step-target="#media"
            data-ve-step-tabs="#media,#hotels,#activities,#program-days">
            <span class="ve-stepper__index">4</span>
            <span class="ve-stepper__text">Contenu du voyage</span>
        </button>
    </div>

    <div class="ve-step-sections" aria-label="Sections de l�?Tétape courante">
        <p class="ve-step-sections__title mb-0">Sections de l�?Tétape</p>
        <nav class="ve-detail-nav" data-ve-detail-nav></nav>
    </div>

    <div class="ve-stepper-nav">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-ve-step-prev>
            <i class="bx bx-chevron-left"></i> �?tape précédente
        </button>
        <button type="button" class="btn btn-primary btn-sm" data-ve-step-next>
            �?tape suivante <i class="bx bx-chevron-right"></i>
        </button>
    </div>

    <div class="ve-tab-scroll d-none" aria-hidden="true">
        <ul class="nav nav-tabs ve-nav-tabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#basic" role="tab">Fiche</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#location" role="tab">Destination</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#price" role="tab">Tarifs</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#information" role="tab">Détails</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#voyage-extras" role="tab">Extras</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#availability" role="tab">Départs</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#media" role="tab">Médias</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#taxonomies" role="tab">Classement</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#logistics" role="tab">Logistique</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#hotels" role="tab">Hôtels</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#activities" role="tab">Activités</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#program-days" role="tab">Programme</a></li>
        </ul>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\_voyage_tabs_nav.blade.php ENDPATH**/ ?>