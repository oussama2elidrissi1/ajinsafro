@extends('layouts.admin-v6')

@section('title', 'Modifier l\'offre Hajj & Omra')

@section('content')
    <x-admin.page-header
        :title="'Modifier : '.$package->title"
        subtitle="Offre, tarifs, départs, hébergement, programme, prestations, médias et publication."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Hajj & Omra', 'url' => route('admin.hajj-omra.index')],
            ['label' => $package->title],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.hajj-omra.preview', $package) }}" target="_blank" rel="noopener" class="aj-btn aj-btn-soft">
                <i class="bx bx-show"></i>
                <span>Prévisualiser</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    {{-- Le formulaire est porte par le partial : pas de balise <form> ici, sous peine d'imbrication. --}}
    @include('admin.hajj-omra._form')
@endsection
