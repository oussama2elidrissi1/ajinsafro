@extends('layouts.admin-v6')
@section('title')
    Statuts
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Statuts'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

