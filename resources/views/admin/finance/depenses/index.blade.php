@extends('layouts.admin-v6')
@section('title')
    DÃ©penses
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'DÃ©penses'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

