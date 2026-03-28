<?php

return [
    'items' => [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'bx bx-home-circle',
            'route' => 'partner.dashboard',
        ],
        [
            'key' => 'catalogue',
            'label' => 'Catalogue voyages',
            'icon' => 'bx bx-map',
            'route' => 'partner.catalogue.index',
        ],
        [
            'key' => 'reservations',
            'label' => 'Mes réservations',
            'icon' => 'bx bx-calendar-check',
            'route' => 'partner.reservations.index',
        ],
        [
            'key' => 'clients',
            'label' => 'Mes clients',
            'icon' => 'bx bx-user',
            'route' => 'partner.clients.index',
        ],
        [
            'key' => 'commissions',
            'label' => 'Commissions',
            'icon' => 'bx bx-wallet',
            'route' => 'partner.commissions.index',
        ],
        [
            'key' => 'documents',
            'label' => 'Documents',
            'icon' => 'bx bx-file',
            'route' => 'partner.documents.index',
        ],
    ],
];
