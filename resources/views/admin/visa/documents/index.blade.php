@extends('layouts.master-ajinsafro')
@section('title')
    Documents
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Documents'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
