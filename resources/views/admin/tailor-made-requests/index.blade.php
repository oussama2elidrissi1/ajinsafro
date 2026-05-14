@extends('layouts.admin-v2')

@section('title', 'Demande à la carte')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-sm-0">Demande à la carte</h4>
                        <p class="text-muted mb-0 mt-1">Espace dédié aux demandes personnalisées.</p>
                    </div>
                    <span class="badge rounded-pill bg-warning-subtle text-warning px-3 py-2">Page en cours de préparation</span>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-7 col-lg-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5 text-center">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 72px; height: 72px; background: #e6f3fa; color: #0083c4;">
                            <i class="bx bx-edit-alt fs-1"></i>
                        </div>
                        <h1 class="h3 mb-2">Demande à la carte</h1>
                        <p class="text-muted mb-4">
                            Cette page sera utilisée pour qualifier et suivre les demandes de voyages personnalisés.
                        </p>
                        <div class="alert alert-info border-0 mb-0" style="background: #eef8fd; color: #0e3a5a;">
                            Page en cours de préparation
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
