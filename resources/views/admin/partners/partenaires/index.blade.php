@extends('layouts.admin-v6')
@section('title')
    Partenaires
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Partenaires'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

