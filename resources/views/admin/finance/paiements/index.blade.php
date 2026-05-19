@extends('layouts.admin-v6')
@section('title')
    Paiements
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Paiements'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

