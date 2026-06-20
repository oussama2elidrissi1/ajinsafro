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
        $headerPath = Setting::normalizePublicDiskPath(Setting::getValue('invoice_header_image'));
        $footerPath = Setting::normalizePublicDiskPath(Setting::getValue('invoice_footer_image'));
        $brandLogoPath = Setting::resolvedBrandLogoPath();

        return [
            'brand_name' => Setting::getValue('brand_name', 'Ajinsafro.ma'),
            'logo_url' => $this->publicDiskImageSource($brandLogoPath) ?: Setting::brandLogoUrl('dark'),
            'phone' => Setting::getValue('topbar_phone', ''),
            'email' => Setting::getValue('topbar_email', ''),
            'header_image_url' => Setting::storageUrl($headerPath),
            'footer_image_url' => Setting::storageUrl($footerPath),
            'header_image_src' => $this->publicDiskImageSource($headerPath),
            'footer_image_src' => $this->publicDiskImageSource($footerPath),
            'legal_information' => Setting::getValue('invoice_legal_information', Setting::getValue('company_legal_information', '')),
            'company_address' => Setting::getValue('company_address', ''),
            'company_ice' => Setting::getValue('company_ice', ''),
            'company_if' => Setting::getValue('company_if', ''),
            'company_rc' => Setting::getValue('company_rc', ''),
            'default_conditions' => Setting::getValue('invoice_conditions', Setting::getValue('quote_conditions', '')),
        ];
    }

    private function publicDiskImageSource(?string $path): ?string
    {
        $normalized = Setting::normalizePublicDiskPath($path);
        if (! $normalized || ! Storage::disk('public')->exists($normalized)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($normalized);
        $contents = @file_get_contents($absolutePath);
        if ($contents === false) {
            return null;
        }

        $mimeType = @mime_content_type($absolutePath) ?: $this->mimeTypeFromExtension($absolutePath);

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function mimeTypeFromExtension(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
