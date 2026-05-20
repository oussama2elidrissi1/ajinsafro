<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TailorMadeRequest;
use Illuminate\View\View;

class TailorMadeRequestController extends Controller
{
    public function index(): View
    {
        $requests = TailorMadeRequest::query()
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.tailor-made-requests.index', [
            'requests' => $requests,
        ]);
    }
}
