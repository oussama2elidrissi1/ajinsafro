<?php

namespace App\Services;

use App\Models\CustomRequestQuote;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CustomRequestQuotePdfService
{
    public function generate(CustomRequestQuote $quote): string
    {
        $quote->loadMissing(['customRequest.creator', 'customRequest.services', 'days.services', 'items']);

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
            'days' => $quote->days,
            'invoiceSettings' => $this->invoiceSettings(),
            'showAmounts' => ! (bool) $quote->summary_mode,
            'summaryMode' => (bool) $quote->summary_mode,
        ];
    }

    private function invoiceSettings(): array
    {
        return [
            'brand_name' => Setting::getValue('brand_name', 'Ajinsafro.ma'),
            'logo_url' => Setting::brandLogoUrl('dark'),
            'phone' => Setting::getValue('topbar_phone', ''),
            'email' => Setting::getValue('topbar_email', ''),
            'header_image_url' => Setting::storageUrl(Setting::getValue('invoice_header_image')),
            'footer_image_url' => Setting::storageUrl(Setting::getValue('invoice_footer_image')),
            'legal_information' => Setting::getValue('invoice_legal_information', Setting::getValue('company_legal_information', '')),
            'company_address' => Setting::getValue('company_address', ''),
            'company_ice' => Setting::getValue('company_ice', ''),
            'company_if' => Setting::getValue('company_if', ''),
            'company_rc' => Setting::getValue('company_rc', ''),
            'default_conditions' => Setting::getValue('invoice_conditions', Setting::getValue('quote_conditions', '')),
        ];
    }
}
