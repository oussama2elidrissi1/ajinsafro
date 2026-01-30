@extends('layouts.master')
@section('title')
    Form File Upload
@endsection
@push('css')
    <!-- Plugins css -->
    <link href="{{ URL::asset('build/libs/dropzone/min/dropzone.min.css') }}" rel="stylesheet" type="text/css" />
@endpush
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Form File Upload</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Forms</a></li>
                        <li class="breadcrumb-item active">Form File Upload</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="card-title">Dropzone</h4>
                    <p class="card-title-desc">DropzoneJS is an open source library that provides
                        drag’n’drop file uploads with image previews.
                    </p>

                    <div>
                        <form action="#" class="dropzone">
                            <div class="fallback">
                                <input name="file" type="file" multiple="multiple">
                            </div>
                            <div class="dz-message needsclick">
                                <div class="mb-3">
                                    <i class="display-4 text-muted mdi mdi-upload-network-outline"></i>
                                </div>

                                <h4>Drop files here or click to upload.</h4>
                            </div>
                        </form>
                    </div>

                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-primary waves-effect waves-light">Send
                            Files</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
@endsection
@push('script')
    <!-- Plugins js -->
    <script src="{{ URL::asset('build/libs/dropzone/min/dropzone.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
