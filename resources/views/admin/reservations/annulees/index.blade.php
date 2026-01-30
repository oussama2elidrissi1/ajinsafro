@extends('layouts.master-ajinsafro')
@section('title')
    Annulées
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Annulées'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
