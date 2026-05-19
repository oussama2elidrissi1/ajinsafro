@extends('layouts.admin-v6')
@section('title')
    ItinÃ©raires
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'ItinÃ©raires'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

