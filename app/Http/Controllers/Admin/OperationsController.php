<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function index()
    {
        return view('admin.operations.index');
    }

    public function page(Request $request)
    {
        $submenu = $request->route()->parameter('submenu');
        return view('admin.operations.' . $submenu . '.index');
    }
}
