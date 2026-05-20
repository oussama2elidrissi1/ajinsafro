<?php $__env->startSection('title', 'Demande a la carte'); ?>
<?php $__env->startSection('page_title', 'Demande a la carte'); ?>
<?php $__env->startSection('hide_admin_footer', '1'); ?>

<?php
    use App\Models\TailorMadeRequest;

    $req = $req ?? null;
    $voyage = $voyage ?? null;
    $imageUrl = $imageUrl ?? null;
    $backUrl = $backUrl ?? route('admin.tailor-made-requests.index');
    $statusOptions = $statusOptions ?? TailorMadeRequest::statusOptions();

    $title = $voyage?->name ?: ($req?->tour_title ?: 'Voyage');
    $title = html_entity_decode((string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>

<?php $__env->startPush('styles'); ?>
<style>
    .tmr-show {
        padding-bottom: 24px;
    }
    .tmr-show .tmr-card {
        background: #fff;
        border: 1px solid #e5edf6;
        border-radius: 20px;
        box-shadow: 0 8px 24px rgba(16, 42, 67, 0.06);
        padding: 22px;
        margin-bottom: 16px;
    }
    .tmr-hero {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 16px;
        align-items: center;
    }
    .tmr-hero__img {
        width: 220px;
        height: 140px;
        border-radius: 16px;
        overflow: hidden;
        background: #f5f8fc;
    }
    .tmr-hero__img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .tmr-hero__title {
        margin: 0;
        font-size: 20px;
        font-weight: 900;
        color: #102a43;
        line-height: 1.2;
    }
    .tmr-meta {
        color: #6b7a90;
        font-weight: 700;
        margin-top: 6px;
        font-size: 13px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .tmr-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
    }
    .tmr-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        border: 1px solid transparent;
        background: #f8fbff;
        color: #07598f;
        border-color: #cfe7ff;
    }
    .tmr-kv {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .tmr-kv .kv {
        background: #f8fbff;
        border: 1px solid #e5edf6;
        border-radius: 14px;
        padding: 12px 14px;
    }
    .tmr-kv .kv span {
        display: block;
        color: #6b7a90;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .tmr-kv .kv strong {
        display: block;
        color: #102a43;
        font-weight: 900;
        font-size: 14px;
        line-height: 1.25;
        word-break: break-word;
    }
    @media (max-width: 900px) {
        .tmr-hero { grid-template-columns: 1fr; }
        .tmr-hero__img { width: 100%; height: 180px; }
        .tmr-kv { grid-template-columns: 1fr; }
        .tmr-actions { justify-content: flex-start; }
    }
    @media print {
        .tmr-actions, .aj-topbar, .aj-sidebar-v2, .page-title-box { display: none !important; }
        .tmr-card { box-shadow: none !important; }
        body { background: #fff !important; }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="tmr-show">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?php echo e($backUrl); ?>" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bx bx-left-arrow-alt"></i> Retour</a>
            <span class="tmr-badge">Demande a la carte</span>
            <span class="tmr-badge"><?php echo e($req->reference); ?></span>
        </div>
        <div class="tmr-actions">
            <a href="<?php echo e(route('admin.tailor-made-requests.show', $req)); ?>?print=1" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bx bx-printer"></i> Imprimer</a>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.delete')): ?>
                <form method="POST" action="<?php echo e(route('admin.tailor-made-requests.destroy', $req)); ?>" onsubmit="return confirm('Supprimer cette demande ?');">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"><i class="bx bx-trash"></i> Supprimer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="tmr-card">
        <div class="tmr-hero">
            <div class="tmr-hero__img">
                <?php if($imageUrl): ?>
                    <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($title); ?>">
                <?php endif; ?>
            </div>
            <div>
                <h1 class="tmr-hero__title"><?php echo e($title); ?></h1>
                <div class="tmr-meta">
                    <?php if($voyage?->destination): ?><span><i class="bx bx-map"></i> <?php echo e($voyage->destination); ?></span><?php endif; ?>
                    <?php if($req->tour_url): ?><span><i class="bx bx-link-external"></i> <a href="<?php echo e($req->tour_url); ?>" target="_blank" rel="noopener">Page publique</a></span><?php endif; ?>
                    <?php if($req->voyage_id): ?><span><i class="bx bx-hash"></i> Voyage ID: <?php echo e($req->voyage_id); ?></span><?php endif; ?>
                    <?php if($req->wp_post_id): ?><span><i class="bx bx-hash"></i> WP post ID: <?php echo e($req->wp_post_id); ?></span><?php endif; ?>
                    <span><i class="bx bx-time"></i> Cree le: <?php echo e($req->created_at?->format('d/m/Y H:i')); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="tmr-card">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h2 class="h5 m-0">Informations demande</h2>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.update')): ?>
                <form method="POST" action="<?php echo e(route('admin.tailor-made-requests.status', $req)); ?>" class="d-flex gap-2 align-items-center">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <label class="text-muted small m-0 fw-bold">Statut</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if((string) $req->status === (string) $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>

        <div class="tmr-kv">
            <div class="kv"><span>Lieu de depart demande</span><strong><?php echo e($req->custom_departure_place ?: '-'); ?></strong></div>
            <div class="kv"><span>Date de depart demandee</span><strong><?php echo e($req->custom_departure_date ? $req->custom_departure_date->format('d/m/Y') : '-'); ?></strong></div>
            <div class="kv"><span>Voyageurs</span><strong><?php echo e($req->travellers_total ?: (($req->adults ?? 0)+($req->children ?? 0))); ?></strong></div>
            <div class="kv"><span>Prix</span><strong>
                <?php if($req->price_total): ?>
                    <?php echo e(number_format((float) $req->price_total, 0, ',', ' ')); ?> <?php echo e($req->price_currency ?: 'MAD'); ?>

                    <?php if($req->price_per_person): ?> <span class="text-muted"> (<?php echo e(number_format((float) $req->price_per_person, 0, ',', ' ')); ?> / pers.)</span><?php endif; ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </strong></div>
            <div class="kv"><span>Source</span><strong><?php echo e($req->source ?: '-'); ?></strong></div>
            <div class="kv"><span>Type</span><strong><?php echo e($req->type ?: 'demande_a_la_carte'); ?></strong></div>
        </div>

        <div class="mt-3">
            <span class="text-muted small fw-bold d-block mb-1">Message / note client</span>
            <div class="p-3 rounded-3" style="background:#f8fbff;border:1px solid #e5edf6;white-space:pre-wrap;">
                <?php echo e($req->message ?: '-'); ?>

            </div>
        </div>
    </div>

    <div class="tmr-card">
        <h2 class="h5 m-0 mb-2">Informations client</h2>
        <div class="tmr-kv">
            <div class="kv"><span>Nom</span><strong><?php echo e(trim(($req->client_first_name ?? '').' '.($req->client_last_name ?? '')) ?: '-'); ?></strong></div>
            <div class="kv"><span>Telephone</span><strong><?php echo e($req->client_phone ?: '-'); ?></strong></div>
            <div class="kv"><span>Email</span><strong><?php echo e($req->client_email ?: '-'); ?></strong></div>
            <div class="kv"><span>Contact</span><strong>
                <?php
                    $mail = $req->client_email ? 'mailto:'.$req->client_email : null;
                    $digits = preg_replace('/\\D+/', '', (string) ($req->client_phone ?? ''));
                    if ($digits && str_starts_with($digits, '0')) $digits = '212'.ltrim($digits, '0');
                    if ($digits && str_starts_with($digits, '00')) $digits = substr($digits, 2);
                    $wa = $digits ? 'https://wa.me/'.$digits : null;
                ?>
                <?php if($mail): ?><a href="<?php echo e($mail); ?>">Email</a><?php else: ?> - <?php endif; ?>
                <?php if($wa): ?><span class="mx-2">|</span><a href="<?php echo e($wa); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
            </strong></div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\tailor-made-requests\show.blade.php ENDPATH**/ ?>