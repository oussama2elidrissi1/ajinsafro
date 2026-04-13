<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupDealsController extends Controller
{
    private const VISIBLE_STATUSES = ['actif', 'published', 'active'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $destination = trim((string) $request->input('destination', ''));
        $groupSize = max(2, (int) $request->input('group_size', 6));

        $dealsQuery = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->where('is_group_deal', true);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $dealsQuery->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('destination', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if ($destination !== '') {
            $dealsQuery->where('destination', $destination);
        }

        $deals = $dealsQuery
            ->orderByDesc('updated_at')
            ->select([
                'id',
                'name',
                'slug',
                'destination',
                'duration_text',
                'price_from',
                'currency',
            ])
            ->paginate(9)
            ->withQueryString();

        $destinations = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->where('is_group_deal', true)
            ->whereNotNull('destination')
            ->where('destination', '!=', '')
            ->distinct()
            ->orderBy('destination')
            ->pluck('destination')
            ->values();

        return view('group-deals.index', [
            'deals' => $deals,
            'destinations' => $destinations,
            'filters' => [
                'q' => $search,
                'destination' => $destination,
                'group_size' => $groupSize,
            ],
        ]);
    }
}
