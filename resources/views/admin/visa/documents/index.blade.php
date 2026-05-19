@extends('layouts.admin-v6')
@section('title')
    Documents
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Documents'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

