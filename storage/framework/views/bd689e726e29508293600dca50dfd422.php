
<?php $__env->startSection('title'); ?>
    Vector Maps
<?php $__env->stopSection(); ?>
<?php $__env->startPush('css'); ?>
    <!-- jquery.vectormap css -->
    <link href="<?php echo e(URL::asset('build/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css')); ?>" rel="stylesheet"
        type="text/css" />
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Vector Maps</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Maps</a></li>
                        <li class="breadcrumb-item active">Vector Maps</li>
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
                    <h4 class="card-title">World Vector Map</h4>
                    <p class="card-title-dsec">Example of world vector maps.</p>
                    <div id="world-map-markers" style="height: 420px"></div>
                </div>
                <!-- end card-body-->
            </div>
            <!-- end card-->
        </div>
        <!-- end col-->
    </div>
    <!-- end row-->

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">USA Vector Map</h4>
                    <p class="card-title-dsec">Example of united states of ameria vector maps.</p>
                    <div id="usa-vectormap" style="height: 350px"></div>
                </div>
                <!-- end card-body-->
            </div>
            <!-- end card-->
        </div>
        <!-- end col-->

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">India Vector Map</h4>
                    <p class="card-title-dsec">Example of india vector maps.</p>
                    <div id="india-vectormap" style="height: 350px"></div>
                </div>
                <!-- end card-body-->
            </div>
            <!-- end card-->
        </div>
        <!-- end col-->
    </div>
    <!-- end row-->

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Australia Vector Map</h4>
                    <p class="card-title-dsec">Example of australia vector maps.</p>
                    <div id="australia-vectormap" style="height: 350px"></div>
                </div>
                <!-- end card-body-->
            </div>
            <!-- end card-->
        </div>
        <!-- end col-->

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Chicago Vector Map</h4>
                    <p class="card-title-dsec">Example chicago of vector maps.</p>
                    <div id="chicago-vectormap" style="height: 350px"></div>
                </div>
                <!-- end card-body-->
            </div>
            <!-- end card-->
        </div>
        <!-- end col-->
    </div>
    <!-- end row-->

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">UK Vector Map</h4>
                    <p class="card-title-dsec">Example of united kingdom vector maps.</p>
                    <div id="uk-vectormap" style="height: 350px"></div>
                </div>
                <!-- end card-body-->
            </div>
            <!-- end card-->
        </div>
        <!-- end col-->

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Canada Vector Map</h4>
                    <p class="card-title-dsec">Example canada of vector maps.</p>
                    <div id="canada-vectormap" style="height: 350px"></div>
                </div>
                <!-- end card-body-->
            </div>
            <!-- end card-->
        </div>
        <!-- end col-->
    </div>
    <!-- end row-->
<?php $__env->stopSection(); ?>
<?php $__env->startPush('script'); ?>
    <!-- Plugins js-->
    <script src="<?php echo e(URL::asset('build/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-us-merc-en.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-in-mill-en.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-au-mill-en.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-us-il-chicago-mill-en.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-uk-mill-en.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-ca-lcc-en.js')); ?>"></script>

    <!-- Init js-->
    <script src="<?php echo e(URL::asset('build/js/pages/vector-maps.init.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\maps-vector.blade.php ENDPATH**/ ?>