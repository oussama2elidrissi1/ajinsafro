@extends('layouts.master-ajinsafro')
@section('title')
    Demandes de visa
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Demandes de visa'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
