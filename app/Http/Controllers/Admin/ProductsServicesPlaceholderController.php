<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProductsServicesPlaceholderController extends Controller
{
    public function voiture(): View
    {
        return view('admin.products-services.wip', [
            'title' => 'Voiture',
            'intro' => 'Cette section est en cours de construction.',
        ]);
    }

    public function billetterie(): View
    {
        return view('admin.products-services.wip', [
            'title' => 'Billetterie',
            'intro' => 'Espace billetterie en cours de construction.',
        ]);
    }
}
