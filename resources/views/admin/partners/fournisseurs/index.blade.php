@extends('layouts.admin-v6')
@section('title')
    Fournisseurs
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Fournisseurs'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

