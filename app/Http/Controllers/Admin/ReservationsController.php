<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReservationsController extends Controller
{
    public function index()
    {
        return view('admin.reservations.index');
    }

    public function page(Request $request)
    {
        $submenu = $request->route()->parameter('submenu');
        return view('admin.reservations.' . $submenu . '.index');
    }
}
