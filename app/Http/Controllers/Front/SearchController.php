<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Search results page (GET /search with location, check_in, check_out, guests).
     */
    public function index(Request $request): View
    {
        return view('front.search', [
            'location' => $request->get('location'),
            'check_in' => $request->get('check_in'),
            'check_out' => $request->get('check_out'),
            'guests' => $request->get('guests', '1 guest, 1 room'),
            'type' => $request->get('type', 'Hotel'),
        ]);
    }
}
