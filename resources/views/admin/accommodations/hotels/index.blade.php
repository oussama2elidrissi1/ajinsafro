@extends('layouts.master-ajinsafro')
@section('title')
    Hôtels
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Hôtels'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
