@extends('layouts.admin-v2')

@section('title', 'Nouvelle offre Hajj & Omra')

@section('content')
    <x-admin.page-header
        title="Nouvelle offre Hajj & Omra"
        subtitle="Structurez l offre, les chambres, les departs, le programme et les medias."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Hajj & Omra', 'url' => route('admin.hajj-omra.index')],
            ['label' => 'Creation'],
        ]"
    />

    <x-admin.flash-messages />

    <form action="{{ route('admin.hajj-omra.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.hajj-omra._form')
    </form>
@endsection
