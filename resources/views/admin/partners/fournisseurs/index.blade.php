@extends('layouts.admin-v2')
@section('title')
    Fournisseurs
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Fournisseurs'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
