@extends('layouts.master-ajinsafro')
@section('title')
    Calendrier
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Calendrier'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
