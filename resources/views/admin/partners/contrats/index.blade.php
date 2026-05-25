@extends('layouts.admin-v6')
@section('title')
    Contrats
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Contrats'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

