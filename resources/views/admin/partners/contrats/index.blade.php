@extends('layouts.master-ajinsafro')
@section('title')
    Contrats
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Contrats'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
