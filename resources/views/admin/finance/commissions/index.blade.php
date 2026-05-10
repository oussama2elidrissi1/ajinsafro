@extends('layouts.admin-v2')
@section('title')
    Commissions
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Commissions'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
