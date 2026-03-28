<div class="modal fade" id="reject-modal-{{ $partner->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.partner-accounts.reject', $partner) }}" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Refuser le partenaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Refuser la demande de <strong>{{ $partner->display_name }}</strong> ({{ $partner->email }}) ?</p>
                    <label class="form-label">Motif (optionnel)</label>
                    <textarea name="rejected_reason" class="form-control" rows="3" placeholder="Raison du refus...">{{ old('rejected_reason') }}</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Refuser</button>
                </div>
            </form>
        </div>
    </div>
</div>
