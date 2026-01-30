@extends('layouts.master-ajinsafro')
@section('title')
    Tarifs
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Tarifs'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
