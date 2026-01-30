<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DemoController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the demo dashboard (index).
     */
    public function index()
    {
        return view('index');
    }

    /**
     * Show a demo page by view name.
     */
    public function page(Request $request, string $any)
    {
        if (view()->exists($any)) {
            return view($any);
        }
        return abort(404);
    }
}
