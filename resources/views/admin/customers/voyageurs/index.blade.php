@extends('layouts.master-ajinsafro')
@section('title')
    Voyageurs
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Voyageurs'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
