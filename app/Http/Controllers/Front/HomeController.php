<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Public homepage (TravelerWP-like).
     */
    public function index(): View
    {
        $destinations = [
            ['title' => 'Dubai', 'image' => 'destinations/dubai.jpg', 'slug' => 'dubai'],
            ['title' => 'Paris', 'image' => 'destinations/paris.jpg', 'slug' => 'paris'],
            ['title' => 'Tokyo', 'image' => 'destinations/tokyo.jpg', 'slug' => 'tokyo'],
            ['title' => 'New York', 'image' => 'destinations/newyork.jpg', 'slug' => 'new-york'],
        ];

        return view('front.home', compact('destinations'));
    }
}
