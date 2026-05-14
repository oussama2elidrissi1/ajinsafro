<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TailorMadeRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.tailor-made-requests.index');
    }
}
