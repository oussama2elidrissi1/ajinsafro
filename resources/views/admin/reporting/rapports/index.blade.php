@extends('layouts.master-ajinsafro')
@section('title')
    Rapports
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Rapports'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
