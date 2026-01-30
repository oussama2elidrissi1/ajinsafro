@extends('layouts.master-ajinsafro')
@section('title')
    Exports
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Exports'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
