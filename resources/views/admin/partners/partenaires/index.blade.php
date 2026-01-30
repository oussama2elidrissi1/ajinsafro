@extends('layouts.master-ajinsafro')
@section('title')
    Partenaires
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Partenaires'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
