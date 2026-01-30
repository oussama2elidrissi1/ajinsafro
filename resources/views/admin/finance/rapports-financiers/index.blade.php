@extends('layouts.master-ajinsafro')
@section('title')
    Rapports financiers
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Rapports financiers'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
