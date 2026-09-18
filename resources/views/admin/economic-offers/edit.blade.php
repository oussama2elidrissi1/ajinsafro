@extends('layouts.admin-v6')

@push('styles')
    <link href="{{ URL::asset('css/admin-economic-offer-form.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'Modifier offre économique')

@section('content')
    @php
        $completion = $report['completion'];
        $gaugeTone = $completion === 100 ? 'is-full' : ($completion >= 70 ? 'is-mid' : 'is-low');
    @endphp

    <x-admin.page-header
        :title="$offer->title"
        subtitle="Mettez à jour le contenu, les départs et les tarifs de cette offre."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule économique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'Modification'],
        ]"
    >
        <x-slot name="actions">
            <span class="oef-completion">
                <span class="oef-gauge">
                    <span class="oef-gauge__fill {{ $gaugeTone }}" style="width:{{ $completion }}%"></span>
                </span>
                <span>Fiche complétée à {{ $completion }} %</span>
            </span>
            <a href="{{ route('admin.economic-offers.show', $offer) }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-show"></i>
                <span>Voir la fiche</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <form action="{{ route('admin.economic-offers.update', $offer) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.economic-offers._form', ['submitLabel' => 'Mettre à jour l’offre'])
    </form>
@endsection

@push('scripts')
    <script src="{{ URL::asset('js/admin-economic-offer-form.js') }}"></script>
@endpush
