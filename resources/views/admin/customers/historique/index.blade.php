@extends('layouts.admin-v2')
@section('title')
    Historique
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Historique'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
