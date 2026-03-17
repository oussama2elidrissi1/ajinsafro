<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentsController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;
        $documents = $partner->documents()->orderBy('type')->get();
        $typeLabels = PartnerDocument::typeLabels();
        return view('partner.v2.documents.index', compact('documents', 'typeLabels'));
    }
}
