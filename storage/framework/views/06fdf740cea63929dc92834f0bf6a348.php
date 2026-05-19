

<?php $__env->startSection('title', 'Affectations'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .aj-page-head { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
    .aj-page-head h1 { margin:0; font-size:28px; font-weight:900; color:#172b4d; }
    .aj-page-head p { margin:6px 0 0; color:#71829a; font-weight:600; }
    .aj-card { background:#fff; border:1px solid #e6edf5; border-radius:18px; box-shadow:0 12px 35px rgba(15,45,75,.08); }
    .aj-card-head { padding:18px 20px 0; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .aj-card-body { padding:20px; }
    .aj-filter-grid { display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:12px; }
    .aj-form-control, .aj-select, .aj-textarea { width:100%; border-radius:12px; border:1px solid #dce8f3; background:#fff; padding:0 14px; font-weight:700; color:#172b4d; }
    .aj-form-control, .aj-select { height:44px; }
    .aj-textarea { min-height:92px; padding:12px 14px; }
    .aj-table { width:100%; border-collapse:collapse; }
    .aj-table th { text-align:left; background:#f7fbff; color:#66758a; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:900; padding:12px 14px; border-bottom:1px solid #e6edf5; white-space:nowrap; }
    .aj-table td { padding:14px; border-bottom:1px solid #edf2f7; vertical-align:middle; }
    .aj-badge { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:900; }
    .aj-badge.ok { background:#e8fff4; color:#19b982; }
    .aj-badge.off { background:#fff0ef; color:#ef4d45; }
    .aj-badge.soft { background:#eef4ff; color:#005792; }
    .aj-btn { display:inline-flex; align-items:center; gap:8px; height:42px; padding:0 14px; border-radius:12px; border:1px solid #dce8f3; background:#fff; color:#06345c; font-weight:800; text-decoration:none; }
    .aj-btn.primary { background:#005792; color:#fff; border-color:#005792; }
    .aj-subtle { color:#71829a; font-size:12px; font-weight:600; }
    @media (max-width: 1200px) { .aj-filter-grid { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width: 768px) { .aj-filter-grid { grid-template-columns:1fr; } .aj-table { min-width:1050px; } }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="aj-page-head">
    <div>
        <h1>Affectations</h1>
        <p>Attribuer un point de vente, un agent et un chef commercial aux reservations.</p>
    </div>
    <?php if(Route::has('admin.reservations.workspace')): ?>
        <a href="<?php echo e(route('admin.reservations.workspace')); ?>" class="aj-btn"><i class="bx bx-calendar-check"></i> Workspace</a>
    <?php endif; ?>
</div>

<div class="aj-card" style="margin-bottom:18px;">
    <div class="aj-card-body">
        <form method="GET" action="<?php echo e(route('admin.assignments.index')); ?>">
            <div class="aj-filter-grid">
                <input type="text" name="search" class="aj-form-control" value="<?php echo e($filters['search'] ?? ''); ?>" placeholder="RÃ©servation, client, email">
                <select name="branch_id" class="aj-select">
                    <option value="">Tous les points de vente</option>
                    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($branch->id); ?>" <?php if((int)($filters['branch_id'] ?? 0) === $branch->id): echo 'selected'; endif; ?>><?php echo e($branch->display_name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <select name="agent_id" class="aj-select">
                    <option value="">Tous les agents</option>
                    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <optgroup label="<?php echo e($branch->display_name); ?>">
                            <?php $__currentLoopData = ($agentsByBranch[$branch->id] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($agent['id']); ?>" <?php if((int)($filters['agent_id'] ?? 0) === (int) $agent['id']): echo 'selected'; endif; ?>><?php echo e($agent['name']); ?> <?php if(!empty($agent['job_title'])): ?> â€” <?php echo e($agent['job_title']); ?> <?php endif; ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </optgroup>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <select name="sales_manager_id" class="aj-select">
                    <option value="">Tous les chefs commerciaux</option>
                    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <optgroup label="<?php echo e($branch->display_name); ?>">
                            <?php $__currentLoopData = ($agentsByBranch[$branch->id] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($agent['id']); ?>" <?php if((int)($filters['sales_manager_id'] ?? 0) === (int) $agent['id']): echo 'selected'; endif; ?>><?php echo e($agent['name']); ?> <?php if(!empty($agent['job_title'])): ?> â€” <?php echo e($agent['job_title']); ?> <?php endif; ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </optgroup>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <select name="status" class="aj-select">
                    <option value="">Tous les statuts</option>
                    <option value="pending" <?php if(($filters['status'] ?? '') === 'pending'): echo 'selected'; endif; ?>>En attente</option>
                    <option value="confirmed" <?php if(($filters['status'] ?? '') === 'confirmed'): echo 'selected'; endif; ?>>ConfirmÃ©e</option>
                    <option value="cancelled" <?php if(($filters['status'] ?? '') === 'cancelled'): echo 'selected'; endif; ?>>AnnulÃ©e</option>
                    <option value="paid" <?php if(($filters['status'] ?? '') === 'paid'): echo 'selected'; endif; ?>>PayÃ©e</option>
                </select>
                <select name="priority" class="aj-select">
                    <option value="">PrioritÃ©</option>
                    <option value="low" <?php if(($filters['priority'] ?? '') === 'low'): echo 'selected'; endif; ?>>Basse</option>
                    <option value="normal" <?php if(($filters['priority'] ?? '') === 'normal'): echo 'selected'; endif; ?>>Normale</option>
                    <option value="high" <?php if(($filters['priority'] ?? '') === 'high'): echo 'selected'; endif; ?>>Haute</option>
                    <option value="urgent" <?php if(($filters['priority'] ?? '') === 'urgent'): echo 'selected'; endif; ?>>Urgente</option>
                </select>
            </div>
            <div style="display:flex;gap:14px;justify-content:flex-end;align-items:center;margin-top:14px;flex-wrap:wrap;">
                <label class="form-check-label" style="display:flex;align-items:center;gap:8px;font-weight:800;color:#172b4d;">
                    <input class="form-check-input" type="checkbox" name="unassigned" value="1" <?php if(!empty($filters['unassigned'])): echo 'checked'; endif; ?>>
                    Non affectÃ©es seulement
                </label>
                <button type="submit" class="aj-btn primary"><i class="bx bx-filter-alt"></i> Filtrer</button>
                <a href="<?php echo e(route('admin.assignments.index')); ?>" class="aj-btn"><i class="bx bx-reset"></i> RÃ©initialiser</a>
            </div>
        </form>
    </div>
</div>

<div class="aj-card" style="margin-bottom:18px;">
    <div class="aj-card-body">
        <form method="POST" action="<?php echo e(route('admin.assignments.bulk')); ?>" id="bulk-assign-form">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-lg-3">
                    <select name="branch_id" class="aj-select assignment-branch-select" id="bulk-branch">
                        <option value="">Point de vente</option>
                        <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($branch->id); ?>"><?php echo e($branch->display_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <select name="agent_id" class="aj-select assignment-agent-select">
                        <option value="">Agent rÃ©servation</option>
                        <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $__currentLoopData = ($agentsByBranch[$branch->id] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option data-branch-id="<?php echo e($branch->id); ?>" value="<?php echo e($agent['id']); ?>"><?php echo e($agent['name']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <select name="sales_manager_id" class="aj-select assignment-manager-select">
                        <option value="">Chef commercial</option>
                        <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $__currentLoopData = ($agentsByBranch[$branch->id] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option data-branch-id="<?php echo e($branch->id); ?>" value="<?php echo e($agent['id']); ?>"><?php echo e($agent['name']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <select name="assignment_priority" class="aj-select">
                        <option value="">PrioritÃ©</option>
                        <option value="low">Basse</option>
                        <option value="normal">Normale</option>
                        <option value="high">Haute</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
                <div class="col-lg-12">
                    <textarea name="assignment_note" class="aj-textarea" placeholder="Note interne / contexte d'affectation"></textarea>
                </div>
                <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="aj-subtle">Cochez les rÃ©servations Ã  affecter puis appliquez en masse.</div>
                    <button type="submit" class="aj-btn primary"><i class="bx bx-layer-plus"></i> Affecter la sÃ©lection</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="aj-card">
    <div class="aj-card-head">
        <div>
            <strong style="font-size:16px;color:#172b4d;">RÃ©servations</strong>
            <div class="aj-subtle"><?php echo e($reservations->total()); ?> rÃ©servation(s)</div>
        </div>
    </div>
    <div class="aj-card-body" style="padding-top:14px;overflow-x:auto;">
        <table class="aj-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>#</th>
                    <th>Client</th>
                    <th>Voyage</th>
                    <th>Point de vente</th>
                    <th>Agent</th>
                    <th>Chef commercial</th>
                    <th>Statut</th>
                    <th>PrioritÃ©</th>
                    <th>Mis Ã  jour</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><input type="checkbox" name="reservation_ids[]" value="<?php echo e($reservation->id); ?>" form="bulk-assign-form" class="reservation-checkbox"></td>
                        <td>#<?php echo e($reservation->id); ?></td>
                        <td><?php echo e(trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: ($reservation->client_email ?? 'â€”')); ?></td>
                        <td><?php echo e($reservation->tour?->name ?? 'â€”'); ?></td>
                        <td><?php echo e($reservation->branch?->display_name ?? 'â€”'); ?></td>
                        <td><?php echo e($reservation->agent?->name ?? 'â€”'); ?></td>
                        <td><?php echo e($reservation->salesManager?->name ?? 'â€”'); ?></td>
                        <td><span class="aj-badge soft"><?php echo e(ucfirst((string) $reservation->status)); ?></span></td>
                        <td><span class="aj-badge <?php echo e(in_array($reservation->assignment_priority, ['high','urgent'], true) ? 'off' : 'ok'); ?>"><?php echo e(ucfirst((string) ($reservation->assignment_priority ?? 'normal'))); ?></span></td>
                        <td><?php echo e($reservation->updated_at?->timezone('Africa/Casablanca')?->format('d/m/Y H:i') ?? 'â€”'); ?></td>
                        <td>
                            <button type="button" class="aj-btn assignment-open" data-bs-toggle="modal" data-bs-target="#assignmentModal"
                                data-reservation-id="<?php echo e($reservation->id); ?>"
                                data-branch-id="<?php echo e($reservation->branch_id); ?>"
                                data-agent-id="<?php echo e($reservation->agent_id); ?>"
                                data-manager-id="<?php echo e($reservation->sales_manager_id); ?>"
                                data-priority="<?php echo e($reservation->assignment_priority); ?>"
                                data-note="<?php echo e(e($reservation->assignment_note ?? '')); ?>">
                                Affecter
                            </button>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="11" style="padding:28px;text-align:center;color:#71829a;font-weight:700;">Aucune rÃ©servation trouvÃ©e.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="aj-card-body" style="padding-top:0;">
        <?php echo e($reservations->links()); ?>

    </div>
</div>

<div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px;">
            <form method="POST" id="assignment-form" action="<?php echo e(route('admin.assignments.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Affecter la rÃ©servation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reservation_id" id="assignment-reservation-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Point de vente</label>
                            <select name="branch_id" id="assignment-branch" class="aj-select">
                                <option value="">Selectionner un point de vente</option>
                                <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($branch->id); ?>"><?php echo e($branch->display_name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Agent rÃ©servation</label>
                            <select name="agent_id" id="assignment-agent" class="aj-select">
                                <option value="">Aucun</option>
                                <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $__currentLoopData = ($agentsByBranch[$branch->id] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option data-branch-id="<?php echo e($branch->id); ?>" value="<?php echo e($agent['id']); ?>"><?php echo e($agent['name']); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chef commercial</label>
                            <select name="sales_manager_id" id="assignment-manager" class="aj-select">
                                <option value="">Aucun</option>
                                <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $__currentLoopData = ($agentsByBranch[$branch->id] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option data-branch-id="<?php echo e($branch->id); ?>" value="<?php echo e($agent['id']); ?>"><?php echo e($agent['name']); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">PrioritÃ©</label>
                            <select name="assignment_priority" id="assignment-priority" class="aj-select">
                                <option value="">Normale</option>
                                <option value="low">Basse</option>
                                <option value="normal">Normale</option>
                                <option value="high">Haute</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Note interne</label>
                            <textarea name="assignment_note" id="assignment-note" class="aj-textarea" placeholder="Note interne"></textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notify_agent" value="1" id="assignment-notify-agent">
                                <label class="form-check-label" for="assignment-notify-agent">Notifier l'agent</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="aj-btn" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="aj-btn primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const modal = document.getElementById('assignmentModal');
    const form = document.getElementById('assignment-form');
    const branchSelect = document.getElementById('assignment-branch');
    const agentSelect = document.getElementById('assignment-agent');
    const managerSelect = document.getElementById('assignment-manager');
    const prioritySelect = document.getElementById('assignment-priority');
    const noteField = document.getElementById('assignment-note');
    const reservationInput = document.getElementById('assignment-reservation-id');
    const bulkBranch = document.getElementById('bulk-branch');
    const selectAll = document.getElementById('select-all');

    function filterByBranch(select, branchId) {
        Array.from(select.options).forEach(function (option) {
            if (!option.dataset.branchId) {
                option.hidden = false;
                return;
            }

            option.hidden = branchId !== '' && option.dataset.branchId !== branchId;
        });
    }

    if (branchSelect) {
        branchSelect.addEventListener('change', function () {
            filterByBranch(agentSelect, branchSelect.value);
            filterByBranch(managerSelect, branchSelect.value);
        });
    }

    if (bulkBranch) {
        bulkBranch.addEventListener('change', function () {
            const value = bulkBranch.value;
            document.querySelectorAll('.assignment-agent-select, .assignment-manager-select').forEach(function (select) {
                filterByBranch(select, value);
            });
        });
    }

    document.querySelectorAll('.assignment-open').forEach(function (button) {
        button.addEventListener('click', function () {
            const reservationId = button.dataset.reservationId || '';
            const branchId = button.dataset.branchId || '';
            const agentId = button.dataset.agentId || '';
            const managerId = button.dataset.managerId || '';
            const priority = button.dataset.priority || '';
            const note = button.dataset.note || '';

            reservationInput.value = reservationId;
            form.action = '<?php echo e(route('admin.assignments.store')); ?>';
            branchSelect.value = branchId;
            filterByBranch(agentSelect, branchId);
            filterByBranch(managerSelect, branchId);
            agentSelect.value = agentId;
            managerSelect.value = managerId;
            prioritySelect.value = priority;
            noteField.value = note;
        });
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.reservation-checkbox').forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
        });
    }

    filterByBranch(agentSelect, branchSelect ? branchSelect.value : '');
    filterByBranch(managerSelect, branchSelect ? branchSelect.value : '');
})();
</script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\assignments\index.blade.php ENDPATH**/ ?>