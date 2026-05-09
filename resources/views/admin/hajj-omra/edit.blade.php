@extends('layouts.master-ajinsafro')

@section('title', 'Modifier Hajj & Omra')

@section('content')
    <x-admin.page-header
        :title="'Modifier : '.$package->title"
        subtitle="Mettez a jour les sections produit, les prix, les departs, le programme et le SEO."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Hajj & Omra', 'url' => route('admin.hajj-omra.index')],
            ['label' => $package->title],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.hajj-omra.show', $package) }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-show"></i>
                <span>Voir la fiche</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <form action="{{ route('admin.hajj-omra.update', $package) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.hajj-omra._form')
    </form>
@endsection
