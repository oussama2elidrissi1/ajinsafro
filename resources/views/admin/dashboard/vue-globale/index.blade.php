@extends('layouts.master-ajinsafro')
@section('title')
    Vue globale
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Vue globale'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
