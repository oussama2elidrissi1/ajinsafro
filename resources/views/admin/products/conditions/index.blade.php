@extends('layouts.master-ajinsafro')
@section('title')
    Conditions
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Conditions'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
