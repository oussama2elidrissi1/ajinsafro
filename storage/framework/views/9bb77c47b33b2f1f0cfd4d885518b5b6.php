<div class="modal fade" id="reject-modal-<?php echo e($partner->id); ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo e(route('admin.partner-accounts.reject', $partner)); ?>" method="post">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Refuser le partenaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Refuser la demande de <strong><?php echo e($partner->display_name); ?></strong> (<?php echo e($partner->email); ?>) ?</p>
                    <label class="form-label">Motif (optionnel)</label>
                    <textarea name="rejected_reason" class="form-control" rows="3" placeholder="Raison du refus..."><?php echo e(old('rejected_reason')); ?></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Refuser</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partner-accounts\_reject_modal.blade.php ENDPATH**/ ?>