@extends('layouts.master-ajinsafro')
@section('title')
    Tableaux de bord
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Tableaux de bord'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
