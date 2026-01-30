@extends('layouts.master-ajinsafro')
@section('title')
    Itinéraires
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Itinéraires'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
