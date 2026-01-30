@extends('layouts.master-ajinsafro')
@section('title')
    Fournisseurs
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Fournisseurs'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
