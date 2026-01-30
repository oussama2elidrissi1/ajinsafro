@extends('layouts.master-ajinsafro')
@section('title')
    Confirmées
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Confirmées'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
