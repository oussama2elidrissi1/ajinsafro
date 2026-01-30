<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CircuitsController extends Controller
{
    public function index()
    {
        return view('admin.circuits.index');
    }

    public function page(Request $request)
    {
        $submenu = $request->route()->parameter('submenu');
        return view('admin.circuits.' . $submenu . '.index');
    }
}
