@extends('layouts.admin-v6')
@section('title')
    Demandes de visa
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Demandes de visa'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

