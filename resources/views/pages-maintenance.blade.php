@extends('layouts.master-without-nav')
@section('title')
    Maintenance
@endsection
@section('content')
    <div class="home-btn d-none d-sm-block">
        <a href="index" class="text-reset"><i class="fas fa-home h2"></i></a>
    </div>

    <section class="my-5 pt-sm-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <div class="home-wrapper">
                        <div class="mb-5">
                            <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="logo" height="24" />
                        </div>

                        <div class="row justify-content-center">
                            <div class="col-sm-4">
                                <div class="maintenance-img">
                                    <img src="{{ URL::asset('build/images/maintenance.png') }}" alt=""
                                        class="img-fluid mx-auto d-block">
                                </div>
                            </div>
                        </div>
                        <h3 class="mt-5">Site is Under Maintenance</h3>
                        <p>If several languages coalesce, the grammar of the resulting language is more simple and
                            <br> regular than that of the individual languages.
                        </p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mt-4 maintenance-box">
                                    <div class="card-body">
                                        <h5 class="font-size-15 text-uppercase">Why is the Site Down?</h5>
                                        <p class="text-muted mb-0">There are many variations of passages of Lorem Ipsum
                                            available, but the majority have suffered alteration.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mt-4 maintenance-box">
                                    <div class="card-body">
                                        <h5 class="font-size-15 text-uppercase">
                                            What is the Downtime?</h5>
                                        <p class="text-muted mb-0">Contrary to popular belief, Lorem Ipsum is not simply
                                            random text. It has roots in a piece of classical.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mt-4 maintenance-box">
                                    <div class="card-body">
                                        <h5 class="font-size-15 text-uppercase">
                                            Do you need Support?</h5>
                                        <p class="text-muted mb-0">If you are going to use a passage of Lorem Ipsum, you
                                            need to be sure there isn't anything embar.. <a
                                                href="mailto:no-reply@domain.com">no-reply@domain.com</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
