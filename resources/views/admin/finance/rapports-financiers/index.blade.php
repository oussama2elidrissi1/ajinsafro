@extends('layouts.admin-v6')
@section('title')
    Rapports financiers
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Rapports financiers'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

