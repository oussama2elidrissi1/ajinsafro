@extends('layouts.admin-v2')
@section('title')
    Exports
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Exports'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
