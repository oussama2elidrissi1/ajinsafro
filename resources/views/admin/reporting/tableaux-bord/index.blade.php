@extends('layouts.admin-v2')
@section('title')
    Tableaux de bord
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Tableaux de bord'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
