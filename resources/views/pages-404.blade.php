@extends('layouts.master-without-nav')
@section('title')
    404
@endsection
@section('content')
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">

                        <div class="card-body">

                            <div class="text-center p-3">

                                <div class="img">
                                    <img src="{{ URL::asset('build/images/error-img.png') }}" class="img-fluid" alt="">
                                </div>

                                <h1 class="error-page mt-5"><span>404!</span></h1>
                                <h4 class="mb-4 mt-5">Sorry, page not found</h4>
                                <p class="mb-4 w-75 mx-auto">It will be as simple as Occidental in fact, it will
                                    Occidental to an English person</p>
                                <a class="btn btn-primary mb-4 waves-effect waves-light" href="index"><i
                                        class="mdi mdi-home"></i> Back to Dashboard</a>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @endsection
    @push('script')
        <script src="{{ URL::asset('build/js/app.js') }}"></script>
    @endpush
    