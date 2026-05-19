<?php $__env->startSection('title', 'Commissions'); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        .commission-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .commission-kpi { background: #fff; border: 1px solid #e6edf5; border-radius: 18px; padding: 1.2rem; box-shadow: 0 12px 30px rgba(19, 38, 77, .06); }
        .commission-kpi__label { color: #64748b; font-size: .8rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        .commission-kpi__value { color: #0f172a; font-size: 1.8rem; font-weight: 800; margin-top: .4rem; }
        .commission-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: .32rem .72rem; font-size: .78rem; font-weight: 700; }
        .commission-badge--estimated { background: #fff7ed; color: #c2410c; }
        .commission-badge--confirmed { background: #eff6ff; color: #1d4ed8; }
        .commission-badge--payable { background: #ecfeff; color: #0f766e; }
        .commission-badge--paid { background: #ecfdf3; color: #15803d; }
        .commission-badge--cancelled, .commission-badge--reversed { background: #fef2f2; color: #b91c1c; }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Ledger des commissions agents</h4>
                <p class="text-muted mb-0">Pilotage finance, suivi des statuts et actions de paiement.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo e(route('admin.finance.commissions.export.excel', request()->query())); ?>" class="btn btn-outline-primary">Export Excel</a>
                <a href="<?php echo e(route('admin.finance.commissions.export.pdf', request()->query())); ?>" class="btn btn-primary">Export PDF</a>
            </div>
        </div>

        <div class="commission-kpis mb-4">
            <div class="commission-kpi"><div class="commission-kpi__label">Total a payer</div><div class="commission-kpi__value"><?php echo e(number_format((float) $kpis['payable_total'], 2, ',', ' ')); ?> DH</div></div>
            <div class="commission-kpi"><div class="commission-kpi__label">Total paye</div><div class="commission-kpi__value"><?php echo e(number_format((float) $kpis['paid_total'], 2, ',', ' ')); ?> DH</div></div>
            <div class="commission-kpi"><div class="commission-kpi__label">Total en attente</div><div class="commission-kpi__value"><?php echo e(number_format((float) $kpis['pending_total'], 2, ',', ' ')); ?> DH</div></div>
            <div class="commission-kpi"><div class="commission-kpi__label">Annule / reverse</div><div class="commission-kpi__value"><?php echo e(number_format((float) $kpis['cancelled_total'], 2, ',', ' ')); ?> DH</div></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-2"><label class="form-label">Mois</label><input type="month" name="month" class="form-control" value="<?php echo e($filters['month'] ?? ''); ?>"></div>
                    <div class="col-md-2">
                        <label class="form-label">Statut commission</label>
                        <select name="commission_status" class="form-select">
                            <option value="">Tous</option>
                            <?php $__currentLoopData = [\App\Models\AgentCommissionEntry::STATUS_ESTIMATED => 'Estimee', \App\Models\AgentCommissionEntry::STATUS_CONFIRMED => 'Confirmee', \App\Models\AgentCommissionEntry::STATUS_PAYABLE => 'Payable', \App\Models\AgentCommissionEntry::STATUS_PAID => 'Payee', \App\Models\AgentCommissionEntry::STATUS_CANCELLED => 'Annulee', \App\Models\AgentCommissionEntry::STATUS_REVERSED => 'Reversee']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(($filters['commission_status'] ?? null) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Agent</label>
                        <select name="agent_id" class="form-select">
                            <option value="">Tous</option>
                            <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($agent->id); ?>" <?php if((int) ($filters['agent_id'] ?? 0) === (int) $agent->id): echo 'selected'; endif; ?>><?php echo e($agent->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Point de vente</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Tous</option>
                            <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($branch->id); ?>" <?php if((int) ($filters['branch_id'] ?? 0) === (int) $branch->id): echo 'selected'; endif; ?>><?php echo e($branch->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Agence</label>
                        <select name="agency_type" class="form-select">
                            <option value="">Toutes</option>
                            <?php $__currentLoopData = \App\Models\Branch::agencyTypeLabels(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(($filters['agency_type'] ?? null) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Voyage</label>
                        <select name="voyage_id" class="form-select">
                            <option value="">Tous</option>
                            <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($voyage->id); ?>" <?php if((int) ($filters['voyage_id'] ?? 0) === (int) $voyage->id): echo 'selected'; endif; ?>><?php echo e($voyage->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Date depart</label><input type="date" name="departure_date" class="form-control" value="<?php echo e($filters['departure_date'] ?? ''); ?>"></div>
                    <div class="col-md-2 d-grid"><button class="btn btn-primary">Filtrer</button></div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="mb-3">Top agents du mois</h5>
                <div class="row g-3">
                    <?php $__empty_1 = true; $__currentLoopData = $kpis['top_agents']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agentRow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold"><?php echo e($agentRow->agent?->name ?: 'Agent supprime'); ?></div>
                                <div class="text-muted small mt-1"><?php echo e(number_format((float) $agentRow->total_amount, 2, ',', ' ')); ?> DH</div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="col-12 text-muted">Aucun agent sur le mois en cours.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">Date</th>
                            <th>Agent</th>
                            <th>Voyage</th>
                            <th>Depart</th>
                            <th>Client</th>
                            <th>Reservation</th>
                            <th>Commission</th>
                            <th>Statut reservation</th>
                            <th>Statut paiement</th>
                            <th>Statut commission</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php $departureDate = $entry->reservation?->departure?->start_date?->format('d/m/Y') ?? $entry->travelDate?->date?->format('d/m/Y') ?? '—'; ?>
                            <tr>
                                <td class="px-3"><?php echo e(optional($entry->calculated_at)->format('d/m/Y')); ?></td>
                                <td><?php echo e($entry->agent?->name ?: 'Agent non renseigne'); ?></td>
                                <td><?php echo e($entry->voyage?->name ?: 'Voyage non renseigne'); ?></td>
                                <td><?php echo e($departureDate); ?></td>
                                <td><?php echo e($entry->client_name ?: 'Client non renseigne'); ?></td>
                                <td><?php echo e(number_format((float) $entry->reservation_total, 2, ',', ' ')); ?> DH</td>
                                <td class="fw-semibold"><?php echo e(number_format((float) $entry->commission_total, 2, ',', ' ')); ?> DH</td>
                                <td><?php echo e($entry->reservation?->statusLabelFr() ?? ucfirst((string) $entry->reservation_status)); ?></td>
                                <td><?php echo e($entry->reservation?->paymentStatusLabelFr() ?? ucfirst((string) $entry->payment_status)); ?></td>
                                <td><span class="commission-badge commission-badge--<?php echo e($entry->commission_status); ?>"><?php echo e($entry->statusLabelFr()); ?></span></td>
                                <td class="text-end px-3">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                        <a href="<?php echo e(route('admin.finance.commissions.show', $entry)); ?>" class="btn btn-sm btn-outline-secondary">Voir</a>
                                        <?php if($entry->commission_status === \App\Models\AgentCommissionEntry::STATUS_ESTIMATED): ?>
                                            <form method="POST" action="<?php echo e(route('admin.finance.commissions.confirm', $entry)); ?>"><?php echo csrf_field(); ?><button class="btn btn-sm btn-outline-primary">Confirmer</button></form>
                                        <?php endif; ?>
                                        <?php if(in_array($entry->commission_status, [\App\Models\AgentCommissionEntry::STATUS_ESTIMATED, \App\Models\AgentCommissionEntry::STATUS_CONFIRMED], true)): ?>
                                            <form method="POST" action="<?php echo e(route('admin.finance.commissions.payable', $entry)); ?>"><?php echo csrf_field(); ?><button class="btn btn-sm btn-outline-info">Payable</button></form>
                                        <?php endif; ?>
                                        <?php if($entry->commission_status !== \App\Models\AgentCommissionEntry::STATUS_PAID): ?>
                                            <form method="POST" action="<?php echo e(route('admin.finance.commissions.paid', $entry)); ?>"><?php echo csrf_field(); ?><button class="btn btn-sm btn-outline-success">Payer</button></form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">Aucune commission a afficher.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <?php echo e($entries->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\finance\commissions\index.blade.php ENDPATH**/ ?>