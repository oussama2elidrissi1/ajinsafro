@extends('layouts.master-ajinsafro')
@section('title')
    Alertes
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Alertes'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
