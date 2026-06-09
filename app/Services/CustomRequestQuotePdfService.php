<?php

namespace App\Services;

use App\Models\CustomRequestQuote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CustomRequestQuotePdfService
{
    public function generate(CustomRequestQuote $quote): string
    {
        $quote->loadMissing(['customRequest.creator', 'customRequest.services', 'items']);

        $directory = 'custom-requests/'.$quote->custom_request_id.'/quotes';
        $fileName = 'devis-'.$quote->quote_number.'-v'.$quote->version.'.pdf';
        $path = $directory.'/'.$fileName;

        $pdf = Pdf::loadView('admin.custom-requests.pdf.quote', $this->getPdfViewData($quote))
            ->setPaper('a4');

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function getPdfViewData(CustomRequestQuote $quote): array
    {
        return [
            'quote' => $quote,
            'customRequest' => $quote->customRequest,
            'items' => $quote->items,
            'showAmounts' => ! (bool) $quote->summary_mode,
            'summaryMode' => (bool) $quote->summary_mode,
        ];
    }
}
