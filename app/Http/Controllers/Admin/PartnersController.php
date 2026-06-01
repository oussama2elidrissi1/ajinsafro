<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnersController extends Controller
{
    public function index()
    {
        return view('admin.partners.index');
    }

    public function page(Request $request): View
    {
        $submenu = $request->route()->parameter('submenu');

        if ($submenu === 'partenaires') {
            $query = Partner::query()->with(['user:id,name,email', 'validatedByUser:id,name']);

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            $search = trim((string) $request->query('search', ''));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('raison_sociale', 'like', '%' . $search . '%')
                        ->orWhere('nom_commercial', 'like', '%' . $search . '%')
                        ->orWhere('nom_responsable', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('telephone', 'like', '%' . $search . '%');
                });
            }

            $partners = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

            // Reuse the existing partner accounts view to keep a single UX/source of truth.
            return view('admin.partner-accounts.index', compact('partners'));
        }

        return view('admin.partners.' . $submenu . '.index');
    }
}
