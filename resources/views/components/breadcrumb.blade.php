<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0 font-size-18">{{ $title ?? '' }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    @if(!empty($li_1 ?? null))
                        <li class="breadcrumb-item">{!! $li_1 !!}</li>
                    @endif
                    <li class="breadcrumb-item active">{{ $title ?? '' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
