<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VisaController extends Controller
{
    public function index()
    {
        return view('admin.visa.index');
    }

    public function page(Request $request)
    {
        $submenu = $request->route()->parameter('submenu');
        return view('admin.visa.' . $submenu . '.index');
    }
}
