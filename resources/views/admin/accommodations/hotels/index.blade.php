@extends('layouts.admin-v6')
@section('title')
    HÃ´tels
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'HÃ´tels'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

