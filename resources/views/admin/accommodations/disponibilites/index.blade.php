@extends('layouts.admin-v6')
@section('title')
    Disponibilités
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Disponibilités'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


