@extends('layouts.master-ajinsafro')
@section('title')
    Guides & Chauffeurs
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Guides & Chauffeurs'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
