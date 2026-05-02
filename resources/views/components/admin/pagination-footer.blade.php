@props(['paginator', 'linksView' => null])

<div class="aj-footer" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:16px;padding-top:18px;border-top:1px solid #eef3f8;color:#7a879a;font-size:13px;font-weight:600;">
    <div>
        Affichage de {{ $paginator->firstItem() ?? 0 }} à {{ $paginator->lastItem() ?? 0 }} sur {{ $paginator->total() }} résultats
    </div>
    <div class="aj-pagination-wrap">
        @if($linksView)
            {{ $paginator->onEachSide(1)->links($linksView) }}
        @else
            {{ $paginator->links() }}
        @endif
    </div>
</div>
