<!-- ========== Left Sidebar Start (AjinsAfro) ========== -->
<div class="vertical-menu">

    <div class="h-100">

        <div class="user-wid text-center py-4">
            <div class="user-img">
                <img src="{{ Auth::user()->avatar_url }}" alt="" class="avatar-md mx-auto rounded-circle">
            </div>

            <div class="mt-3">

                <a href="{{ route('admin.dashboard') }}" class="text-body fw-medium font-size-16">{{ Auth::user()->name }}</a>
                <p class="text-muted mt-1 mb-0 font-size-13">Admin</p>

            </div>
        </div>

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-home-circle"></i><span>Dashboard</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.dashboard.vue-globale') }}">Vue globale</a></li>
                        <li><a href="{{ route('admin.dashboard.statistiques') }}">Statistiques</a></li>
                        <li><a href="{{ route('admin.dashboard.alertes') }}">Alertes</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-calendar-check"></i><span>Réservations</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.reservations.toutes') }}">Toutes les réservations</a></li>
                        <li><a href="{{ route('admin.reservations.en-attente') }}">En attente</a></li>
                        <li><a href="{{ route('admin.reservations.confirmees') }}">Confirmées</a></li>
                        <li><a href="{{ route('admin.reservations.annulees') }}">Annulées</a></li>
                        <li><a href="{{ route('admin.reservations.calendrier') }}">Calendrier</a></li>
                        <li><a href="{{ route('admin.reservations.paiements') }}">Paiements</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-user"></i><span>Clients</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.customers.clients') }}">Clients</a></li>
                        <li><a href="{{ route('admin.customers.voyageurs') }}">Voyageurs</a></li>
                        <li><a href="{{ route('admin.customers.historique') }}">Historique</a></li>
                        <li><a href="{{ route('admin.customers.avis-clients') }}">Avis clients</a></li>
                        <li><a href="{{ route('admin.customers.fidelite') }}">Fidélité</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-store"></i><span>Produits</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.products.services') }}">Services</a></li>
                        <li><a href="{{ route('admin.products.options') }}">Options</a></li>
                        <li><a href="{{ route('admin.products.tarifs') }}">Tarifs</a></li>
                        <li><a href="{{ route('admin.products.conditions') }}">Conditions</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-map"></i><span>Circuits</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.circuits.voyages.index') }}">Voyages</a></li>
                        <li><a href="{{ route('admin.circuits.circuits') }}">Circuits</a></li>
                        <li><a href="{{ route('admin.circuits.itineraires') }}">Itinéraires</a></li>
                        <li><a href="{{ route('admin.circuits.departs-dates') }}">Départs & Dates</a></li>
                        <li><a href="{{ route('admin.circuits.options') }}">Options</a></li>
                        <li><a href="{{ route('admin.circuits.politiques-conditions') }}">Politiques & Conditions</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-hotel"></i><span>Hébergements</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.accommodations.hotels') }}">Hôtels</a></li>
                        <li><a href="{{ route('admin.accommodations.chambres') }}">Chambres</a></li>
                        <li><a href="{{ route('admin.accommodations.tarifs-saisonniers') }}">Tarifs saisonniers</a></li>
                        <li><a href="{{ route('admin.accommodations.disponibilites') }}">Disponibilités</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-briefcase-alt-2"></i><span>Opérations Terrain</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.operations.planning') }}">Planning</a></li>
                        <li><a href="{{ route('admin.operations.guides-chauffeurs') }}">Guides & Chauffeurs</a></li>
                        <li><a href="{{ route('admin.operations.vehicules') }}">Véhicules</a></li>
                        <li><a href="{{ route('admin.operations.logistique') }}">Logistique</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-id-card"></i><span>Visa</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.visa.demandes-visa') }}">Demandes de visa</a></li>
                        <li><a href="{{ route('admin.visa.statuts') }}">Statuts</a></li>
                        <li><a href="{{ route('admin.visa.documents') }}">Documents</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-wallet"></i><span>Finance</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.finance.factures') }}">Factures</a></li>
                        <li><a href="{{ route('admin.finance.paiements') }}">Paiements</a></li>
                        <li><a href="{{ route('admin.finance.depenses') }}">Dépenses</a></li>
                        <li><a href="{{ route('admin.finance.commissions') }}">Commissions</a></li>
                        <li><a href="{{ route('admin.finance.rapports-financiers') }}">Rapports financiers</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-group"></i><span>Partenaires</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.partners.partenaires') }}">Partenaires</a></li>
                        <li><a href="{{ route('admin.partners.fournisseurs') }}">Fournisseurs</a></li>
                        <li><a href="{{ route('admin.partners.contrats') }}">Contrats</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-bar-chart-alt-2"></i><span>Reporting</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.reporting.rapports') }}">Rapports</a></li>
                        <li><a href="{{ route('admin.reporting.tableaux-bord') }}">Tableaux de bord</a></li>
                        <li><a href="{{ route('admin.reporting.exports') }}">Exports</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-cog"></i><span>Paramètres</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('admin.settings.utilisateurs') }}">Utilisateurs</a></li>
                        <li><a href="{{ route('admin.settings.roles-permissions') }}">Rôles & Permissions</a></li>
                        <li><a href="{{ route('admin.settings.parametres-generaux') }}">Paramètres généraux</a></li>
                        <li><a href="{{ route('admin.settings.securite') }}">Sécurité</a></li>
                    </ul>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
