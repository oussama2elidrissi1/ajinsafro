@extends('layouts.master-ajinsafro')
@section('title')
    Départs & Dates
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Départs & Dates'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
