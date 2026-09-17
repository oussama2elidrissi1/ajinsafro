{{--
    Barre d'actions repetee au pied de chaque etape : elle reste visible pendant
    le defilement pour que l'enregistrement soit toujours a portee.
--}}
<div class="aje-actions">
    <button type="submit" class="aje-btn -primary">Enregistrer</button>
    <button type="submit" class="aje-btn -ghost" data-save-continue>Enregistrer et continuer</button>
    <a href="{{ route('admin.wordpress.hotels.index') }}" class="aje-btn -quiet">Annuler</a>
</div>
