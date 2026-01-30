<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccommodationsController extends Controller
{
    public function index()
    {
        return view('admin.accommodations.index');
    }

    public function page(Request $request)
    {
        $submenu = $request->route()->parameter('submenu');
        return view('admin.accommodations.' . $submenu . '.index');
    }
}
