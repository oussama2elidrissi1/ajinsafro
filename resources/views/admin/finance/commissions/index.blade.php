@extends('layouts.master-ajinsafro')
@section('title')
    Commissions
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Commissions'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
