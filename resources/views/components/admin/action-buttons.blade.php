@props([
    'editUrl' => null,
    'deleteUrl' => null,
    'viewUrl' => null,
    'deleteConfirm' => 'Supprimer cet élément ?',
])

<div class="aj-actions" style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
    @if($viewUrl)
        <a href="{{ $viewUrl }}" target="_blank" class="aj-icon-btn" title="Voir sur le site" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;transition:.16s ease;">
            <i class="bx bx-link-external"></i>
        </a>
    @endif

    @if($editUrl)
        <a href="{{ $editUrl }}" class="aj-icon-btn" title="Modifier" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;transition:.16s ease;">
            <i class="bx bx-pencil"></i>
        </a>
    @endif

    @if($deleteUrl)
        <form action="{{ $deleteUrl }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $deleteConfirm }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="aj-icon-btn -danger" title="Supprimer" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;transition:.16s ease;">
                <i class="bx bx-trash"></i>
            </button>
        </form>
    @endif
</div>
