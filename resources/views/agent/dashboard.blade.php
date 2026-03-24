@extends('layouts.master')

@section('title')
    Dashboard Agent
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Agent
        @endslot
        @slot('title')
            Dashboard Agent
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-0">Dashboard Agent</h4>
                </div>
            </div>
        </div>
    </div>
@endsection
