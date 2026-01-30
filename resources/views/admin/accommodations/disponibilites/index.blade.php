@extends('layouts.master-ajinsafro')
@section('title')
    Disponibilités
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Disponibilités'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
