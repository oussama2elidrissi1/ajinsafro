@extends('layouts.admin-v2')
@section('title')
    Confirmées
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Confirmées'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
