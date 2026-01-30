@extends('layouts.master-ajinsafro')
@section('title')
    Chambres
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Chambres'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
