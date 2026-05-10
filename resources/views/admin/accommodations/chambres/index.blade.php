@extends('layouts.admin-v2')
@section('title')
    Chambres
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Chambres'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
