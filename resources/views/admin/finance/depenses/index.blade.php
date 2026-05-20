@extends('layouts.admin-v6')
@section('title')
    Dépenses
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Dépenses'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


