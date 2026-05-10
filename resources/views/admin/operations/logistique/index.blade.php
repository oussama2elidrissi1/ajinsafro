@extends('layouts.admin-v2')
@section('title')
    Logistique
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Logistique'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
