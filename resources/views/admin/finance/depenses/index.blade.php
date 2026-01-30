@extends('layouts.master-ajinsafro')
@section('title')
    Dépenses
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Dépenses'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
