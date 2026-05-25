@extends('layouts.admin-v6')
@section('title')
    Itinéraires
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Itinéraires'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


