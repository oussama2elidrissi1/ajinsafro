<?php $__env->startSection('title', 'Demandes a la carte'); ?>
<?php $__env->startSection('page_title', 'Demandes à la carte'); ?>

<?php
    $badgeClass = function (?string $status) {
        return match ($status) {
            'draft' => 'crq-badge-neutral',
            'new' => 'crq-badge-info',
            'in_review' => 'crq-badge-blue',
            'quoted' => 'crq-badge-warn',
            'accepted' => 'crq-badge-ok',
            'converted' => 'crq-badge-green',
            'cancelled' => 'crq-badge-danger',
            default => 'crq-badge-neutral',
        };
    };
    $serviceLabels = $serviceOptions ?? [];
?>

<?php $__env->startPush('styles'); ?>
<style>
    .crq-list { display: grid; gap: 18px; }
    .crq-list-head { display:flex;justify-content:space-between;gap:14px;align-items:center;background:linear-gradient(135deg,#073b5c,#0f5f8f);color:#fff;border-radius:20px;padding:22px;box-shadow:0 14px 30px rgba(15,39,66,.16); }
    .crq-list-head h2 { margin:0;font-size:22px;font-weight:900; }
    .crq-list-head p { margin:6px 0 0;color:rgba(255,255,255,.78);font-weight:600; }
    .crq-btn { border:0;border-radius:12px;padding:10px 14px;font-weight:900;display:inline-flex;align-items:center;gap:8px;text-decoration:none;white-space:nowrap; }
    .crq-btn-primary { background:#ff7a1a;color:#fff; }
    .crq-btn-soft { background:#eef6fb;color:#0f5f8f;border:1px solid #d9e8f2; }
    .crq-kpis { display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px; }
    .crq-kpi { background:#fff;border:1px solid #dce8f1;border-radius:16px;padding:16px;box-shadow:0 8px 22px rgba(15,39,66,.05); }
    .crq-kpi span { display:block;color:#6b7f95;font-size:12px;font-weight:900;text-transform:uppercase; }
    .crq-kpi strong { display:block;margin-top:6px;color:#0f2742;font-size:24px;font-weight:900; }
    .crq-panel { background:#fff;border:1px solid #dce8f1;border-radius:20px;box-shadow:0 10px 26px rgba(15,39,66,.06);padding:18px; }
    .crq-filters { display:grid;grid-template-columns:1.2fr repeat(5,minmax(0,1fr)) auto;gap:10px;align-items:end; }
    .crq-field label { display:block;color:#334155;font-size:11px;font-weight:900;text-transform:uppercase;margin-bottom:6px; }
    .crq-field input,.crq-field select { width:100%;border:1px solid #dce8f1;border-radius:12px;padding:10px 11px;font-weight:700;color:#0f2742; }
    .crq-table-wrap { overflow:auto;border:1px solid #e6edf5;border-radius:16px;margin-top:16px; }
    .crq-table { width:100%;min-width:980px;border-collapse:collapse; }
    .crq-table th { background:#f3f9fd;color:#5f7892;font-size:11px;text-transform:uppercase;font-weight:900;padding:12px;border-bottom:1px solid #e6edf5; }
    .crq-table td { padding:13px 12px;border-bottom:1px solid #edf3f8;color:#334155;font-weight:650;vertical-align:middle; }
    .crq-ref { color:#0f5f8f;font-weight:900; }
    .crq-muted { color:#7a8da5;font-size:12px;font-weight:700; }
    .crq-badge { display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:11px;font-weight:900; }
    .crq-badge-neutral { background:#f1f5f9;color:#475569; }
    .crq-badge-info { background:#e0f2fe;color:#0369a1; }
    .crq-badge-blue { background:#dbeafe;color:#1d4ed8; }
    .crq-badge-warn { background:#fff7ed;color:#c2410c; }
    .crq-badge-ok,.crq-badge-green { background:#dcfce7;color:#15803d; }
    .crq-badge-danger { background:#fee2e2;color:#b91c1c; }
    .crq-actions { display:flex;gap:8px;justify-content:flex-end;align-items:center; }
    .crq-status-form { display:flex;gap:6px;align-items:center; }
    .crq-status-form select { border:1px solid #dce8f1;border-radius:10px;padding:7px;font-size:12px;font-weight:800; }
    .crq-empty { text-align:center;padding:38px;color:#64748b;font-weight:800; }
    @media (max-width:1200px){ .crq-kpis{grid-template-columns:repeat(2,1fr)} .crq-filters{grid-template-columns:repeat(2,1fr)} }
    @media (max-width:720px){ .crq-list-head{flex-direction:column;align-items:stretch}.crq-kpis,.crq-filters{grid-template-columns:1fr} }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="crq-list">
    <div class="crq-list-head">
        <div>
            <h2>Demandes à la carte</h2>
            <p>Demandes personnalisées sans liaison obligatoire à un voyage existant.</p>
        </div>
        <a href="<?php echo e(route('admin.reservations.custom-requests.create')); ?>" class="crq-btn crq-btn-primary"><i class="bx bx-plus"></i> Nouvelle demande à la carte</a>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success mb-0"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <div class="crq-kpis">
        <div class="crq-kpi"><span>Total demandes</span><strong><?php echo e(number_format($stats['total'] ?? 0, 0, ',', ' ')); ?></strong></div>
        <div class="crq-kpi"><span>Nouvelles</span><strong><?php echo e(number_format($stats['new'] ?? 0, 0, ',', ' ')); ?></strong></div>
        <div class="crq-kpi"><span>En traitement</span><strong><?php echo e(number_format($stats['in_review'] ?? 0, 0, ',', ' ')); ?></strong></div>
        <div class="crq-kpi"><span>Devis envoyés</span><strong><?php echo e(number_format($stats['quoted'] ?? 0, 0, ',', ' ')); ?></strong></div>
        <div class="crq-kpi"><span>Converties</span><strong><?php echo e(number_format($stats['converted'] ?? 0, 0, ',', ' ')); ?></strong></div>
    </div>

    <div class="crq-panel">
        <form method="GET" class="crq-filters">
            <div class="crq-field"><label>Téléphone / nom client</label><input name="search" value="<?php echo e($filters['search'] ?? ''); ?>" placeholder="Client, téléphone, référence"></div>
            <div class="crq-field"><label>Statut</label><select name="status"><option value="">Tous</option><?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(($filters['status'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div class="crq-field"><label>Priorité</label><select name="priority"><option value="">Toutes</option><?php $__currentLoopData = $priorityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(($filters['priority'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div class="crq-field"><label>Agent</label><select name="assigned_to"><option value="">Tous</option><?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($agent->id); ?>" <?php if((int)($filters['assigned_to'] ?? 0) === (int)$agent->id): echo 'selected'; endif; ?>><?php echo e($agent->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div class="crq-field"><label>Destination</label><input name="destination" value="<?php echo e($filters['destination'] ?? ''); ?>"></div>
            <div class="crq-field"><label>Créée du</label><input type="date" name="created_from" value="<?php echo e($filters['created_from'] ?? ''); ?>"></div>
            <div class="crq-field"><label>Au</label><input type="date" name="created_to" value="<?php echo e($filters['created_to'] ?? ''); ?>"></div>
            <button class="crq-btn crq-btn-soft" type="submit"><i class="bx bx-filter"></i> Filtrer</button>
        </form>

        <div class="crq-table-wrap">
            <table class="crq-table">
                <thead>
                    <tr>
                        <th>Référence</th><th>Client</th><th>Destination</th><th>Services demandés</th><th>Budget</th><th>Statut</th><th>Agent</th><th>Date</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requestRow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $services = collect($requestRow->services ?: [])->keys()->map(fn($key) => $serviceLabels[$key] ?? $key)->implode(', ');
                            $budget = ($requestRow->budget_min || $requestRow->budget_max)
                                ? trim(($requestRow->budget_min ? number_format((float)$requestRow->budget_min, 0, ',', ' ') : '-').' - '.($requestRow->budget_max ? number_format((float)$requestRow->budget_max, 0, ',', ' ') : '-').' '.$requestRow->currency)
                                : '-';
                        ?>
                        <tr>
                            <td><a class="crq-ref" href="<?php echo e(route('admin.reservations.custom-requests.show', $requestRow)); ?>"><?php echo e($requestRow->reference); ?></a><div class="crq-muted"><?php echo e($requestRow->priorityLabel()); ?></div></td>
                            <td><?php echo e($requestRow->client_name); ?><div class="crq-muted"><?php echo e($requestRow->client_phone); ?></div></td>
                            <td><?php echo e($requestRow->destination_text ?: '-'); ?><div class="crq-muted"><?php echo e($requestRow->departure_city_text ?: ''); ?></div></td>
                            <td><?php echo e($services ?: '-'); ?></td>
                            <td><?php echo e($budget); ?></td>
                            <td><span class="crq-badge <?php echo e($badgeClass($requestRow->status)); ?>"><?php echo e($requestRow->statusLabel()); ?></span></td>
                            <td><?php echo e($requestRow->assignedTo?->name ?: '-'); ?></td>
                            <td><?php echo e(optional($requestRow->created_at)->format('d/m/Y')); ?></td>
                            <td>
                                <div class="crq-actions">
                                    <a href="<?php echo e(route('admin.reservations.custom-requests.show', $requestRow)); ?>" class="crq-btn crq-btn-soft">Voir</a>
                                    <a href="<?php echo e(route('admin.reservations.custom-requests.edit', $requestRow)); ?>" class="crq-btn crq-btn-soft">Modifier</a>
                                    <form method="POST" action="<?php echo e(route('admin.reservations.custom-requests.status', $requestRow)); ?>" class="crq-status-form">
                                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                        <select name="status" onchange="this.form.submit()"><?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if($requestRow->status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                                    </form>
                                    <form method="POST" action="<?php echo e(route('admin.reservations.custom-requests.convert-to-reservation', $requestRow)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button class="crq-btn crq-btn-primary" type="submit">Convertir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="9" class="crq-empty">Aucune demande à la carte trouvée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3"><?php echo e($requests->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\custom-requests\index.blade.php ENDPATH**/ ?>