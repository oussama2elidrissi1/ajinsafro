@extends('layouts.master-ajinsafro')
@section('title')
    Politiques & Conditions
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Politiques & Conditions'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
