@extends('layouts.admin-v2')
@section('title')
    Hôtels
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Hôtels'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
