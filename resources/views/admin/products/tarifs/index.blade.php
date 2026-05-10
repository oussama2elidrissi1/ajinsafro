@extends('layouts.admin-v2')
@section('title')
    Tarifs
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Tarifs'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
