@extends('layouts.admin-v6')
@section('title')
    Rapports
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Rapports'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

