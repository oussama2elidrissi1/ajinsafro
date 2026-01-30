@extends('layouts.master-ajinsafro')
@section('title')
    Historique
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Historique'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
