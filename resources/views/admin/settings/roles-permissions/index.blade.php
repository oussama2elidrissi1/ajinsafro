@extends('layouts.master-ajinsafro')
@section('title')
    Rôles & Permissions
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Rôles & Permissions'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
