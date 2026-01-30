<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    public function index()
    {
        return view('admin.reporting.index');
    }

    public function page(Request $request)
    {
        $submenu = $request->route()->parameter('submenu');
        return view('admin.reporting.' . $submenu . '.index');
    }
}
