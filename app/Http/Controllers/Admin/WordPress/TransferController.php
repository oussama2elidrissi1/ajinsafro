<?php

namespace App\Http\Controllers\Admin\WordPress;

use App\Http\Controllers\Controller;
use App\Http\Requests\WordPressTransferStoreRequest;
use App\Http\Requests\WordPressTransferUpdateRequest;
use App\Models\CatalogTransfer;
use App\Models\Wp\StCar;
use App\Models\Wp\WpPost;
use App\Services\WordPressCatalogSyncService;
use App\Services\WordPressMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(
        protected WordPressMediaService $media,
        protected WordPressCatalogSyncService $sync
    ) {}

    public function index(Request $request): View
    {
        $postsTable = (new WpPost())->getTable();
        $carsTable = (new StCar())->getTable();

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $city = trim((string) $request->query('city', ''));

        $transfers = WpPost::query()
            ->leftJoin($carsTable, $postsTable.'.ID', '=', $carsTable.'.post_id')
            ->select($postsTable.'.*')
            ->with('stCar')
            ->where($postsTable.'.post_type', 'st_cars')
            ->whereIn($postsTable.'.post_status', ['publish', 'draft'])
            ->when($search !== '', function ($query) use ($search, $postsTable, $carsTable) {
                $query->where(function ($inner) use ($search, $postsTable, $carsTable) {
                    $inner->where($postsTable.'.post_title', 'like', '%'.$search.'%')
                        ->orWhere($postsTable.'.post_name', 'like', '%'.$search.'%')
                        ->orWhere($carsTable.'.cars_address', 'like', '%'.$search.'%')
                        ->orWhereExists(function ($sub) use ($search, $postsTable) {
                            $sub->selectRaw('1')
                                ->from('postmeta as pm')
                                ->whereColumn('pm.post_id', $postsTable.'.ID')
                                ->whereIn('pm.meta_key', ['aj_transfer_from', 'aj_transfer_to', 'aj_transfer_type', 'aj_transfer_vehicle_type'])
                                ->where('pm.meta_value', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when(in_array($status, ['publish', 'draft'], true), fn ($query) => $query->where($postsTable.'.post_status', $status))
            ->when($city !== '', fn ($query) => $query->where($carsTable.'.cars_address', $city))
            ->orderByDesc($postsTable.'.post_modified')
            ->paginate(15)
            ->withQueryString();

        $cityOptions = StCar::query()
            ->whereNotNull('cars_address')
            ->where('cars_address', '!=', '')
            ->distinct()
            ->orderBy('cars_address')
            ->pluck('cars_address');

        return view('admin.wordpress.transfers.index', [
            'transfers' => $transfers,
            'cityOptions' => $cityOptions,
            'media' => $this->media,
            'filters' => compact('search', 'status', 'city'),
        ]);
    }

    public function create(): View
    {
        return view('admin.wordpress.transfers.create');
    }

    public function store(WordPressTransferStoreRequest $request): RedirectResponse
    {
        $this->sync->saveTransferFromRequest($request->validated(), $request);

        return redirect()
            ->route('admin.wordpress.transfers.index')
            ->with('success', 'Transfert crÃ©Ã© avec succÃ¨s.');
    }

    public function edit(int $transfer): View
    {
        $record = $this->sync->syncTransferRecordFromWpPostId($transfer);
        $wpPost = $this->sync->getWpPost($transfer, 'st_cars');

        return view('admin.wordpress.transfers.edit', [
            'transfer' => $wpPost,
            'stCar' => (object) [
                'post_id' => $wpPost->ID,
                'cars_address' => $record->cars_address,
                'cars_price' => $record->cars_price,
                'min_price' => $record->min_price,
                'max_price' => $record->max_price,
                'number_car' => $record->number_car,
                'is_featured' => $record->is_featured ? 'on' : 'off',
            ],
            'featuredUrl' => $this->media->getFeaturedImageUrlVerified($wpPost->ID),
            'meta' => [
                'aj_transfer_from' => $record->transfer_from,
                'aj_transfer_to' => $record->transfer_to,
                'aj_transfer_type' => $record->transfer_type,
                'aj_transfer_capacity' => $record->transfer_capacity,
                'aj_transfer_vehicle_type' => $record->transfer_vehicle_type,
            ],
        ]);
    }

    public function update(WordPressTransferUpdateRequest $request, int $transfer): RedirectResponse
    {
        $record = CatalogTransfer::query()->where('wp_post_id', $transfer)->first()
            ?? $this->sync->syncTransferRecordFromWpPostId($transfer);

        $this->sync->saveTransferFromRequest($request->validated(), $request, $record);

        return redirect()
            ->route('admin.wordpress.transfers.index')
            ->with('success', 'Transfert mis Ã  jour.');
    }

    public function destroy(int $transfer): RedirectResponse
    {
        $this->sync->trashTransferByWpPostId($transfer);

        return redirect()
            ->route('admin.wordpress.transfers.index')
            ->with('success', 'Transfert dÃ©placÃ© dans la corbeille.');
    }
}
