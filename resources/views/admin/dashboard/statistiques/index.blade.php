@extends('layouts.master-ajinsafro')
@section('title')
    Statistiques
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Statistiques'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
