
<?php $__env->startSection('title'); ?>
    Apex Charts
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Apex charts</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Charts</a></li>
                        <li class="breadcrumb-item active">Apex charts</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Line with Data Labels</h4>

                    <div id="line_chart_datalabel" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
            <!--end card-->
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Dashed Line</h4>

                    <div id="line_chart_dashed" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
            <!--end card-->
        </div>
    </div>
    <!-- end row -->

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Spline Area</h4>

                    <div id="spline_area" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
            <!--end card-->
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Column Charts</h4>

                    <div id="column_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
            <!--end card-->
        </div>
    </div>
    <!-- end row -->

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Column with Data Labels</h4>

                    <div id="column_chart_datalabel" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
            <!--end card-->
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Bar Chart</h4>

                    <div id="bar_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
            <!--end card-->
        </div>
    </div>
    <!-- end row -->

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Line, Column & Area Chart</h4>

                    <div id="mixed_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
            <!--end card-->
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Radial Chart</h4>

                    <div id="radial_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
            <!--end card-->

        </div>
    </div>
    <!-- end row -->

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Pie Chart</h4>

                    <div id="pie_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Donut Chart</h4>

                    <div id="donut_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
<?php $__env->stopSection(); ?>
<?php $__env->startPush('script'); ?>
    <!-- apexcharts -->
    <script src="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.min.js')); ?>"></script>

    <!-- apexcharts init -->
    <script src="<?php echo e(URL::asset('build/js/pages/apexcharts.init.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\charts-apex.blade.php ENDPATH**/ ?>