@extends('layouts.master-ajinsafro')
@section('title')
    Statuts
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Statuts'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
