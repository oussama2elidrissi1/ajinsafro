@extends('layouts.master-ajinsafro')
@section('title')
    Paiements
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Paiements'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
