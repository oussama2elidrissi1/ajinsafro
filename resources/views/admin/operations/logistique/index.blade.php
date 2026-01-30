@extends('layouts.master-ajinsafro')
@section('title')
    Logistique
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Logistique'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
