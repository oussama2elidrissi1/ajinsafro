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
        $quote->loadMissing(['customRequest.creator', 'customRequest.services', 'days.services', 'items.day']);

        $directory = 'custom-requests/'.$quote->custom_request_id.'/quotes';
        $fileName = 'devis-'.$quote->quote_number.'-v'.$quote->version.'.pdf';
        $path = $directory.'/'.$fileName;

        $pdf = Pdf::loadView('admin.custom-requests.pdf.quote', $this->getPdfViewData($quote))
            ->setPaper('a4');

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function generatePriceSheet(CustomRequestQuote $quote): string
    {
        $quote->loadMissing(['customRequest.creator', 'customRequest.services', 'days.services', 'items.day']);

        $directory = 'custom-requests/'.$quote->custom_request_id.'/quotes';
        $fileName = 'fiche-prix-'.$quote->quote_number.'-v'.$quote->version.'.pdf';
        $path = $directory.'/'.$fileName;

        $pdf = Pdf::loadView('admin.custom-requests.pdf.price-sheet', $this->getPriceSheetViewData($quote))
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
            'showAmounts' => false,
            'summaryMode' => (bool) $quote->summary_mode,
        ];
    }

    public function getPriceSheetViewData(CustomRequestQuote $quote): array
    {
        return [
            'quote' => $quote,
            'customRequest' => $quote->customRequest,
            'items' => $quote->items,
            'days' => $quote->days,
            'invoiceSettings' => $this->invoiceSettings(),
            'showAmounts' => true,
            'summaryMode' => false,
        ];
    }

    private function invoiceSettings(): array
    {
        $headerPath = Setting::getValue('invoice_header_image');
        $footerPath = Setting::getValue('invoice_footer_image');
        $brandLogoPath = Setting::resolvedBrandLogoPath();
        $headerSources = $this->publicDiskImageSources($headerPath);
        $footerSources = $this->publicDiskImageSources($footerPath);
        $logoSources = $this->publicDiskImageSources($brandLogoPath);

        return [
            'brand_name' => Setting::getValue('brand_name', 'Ajinsafro.ma'),
            'logo_url' => $logoSources['data'] ?: $logoSources['file'] ?: Setting::brandLogoUrl('dark'),
            'phone' => Setting::getValue('topbar_phone', ''),
            'email' => Setting::getValue('topbar_email', ''),
            'header_image_path' => $headerPath,
            'footer_image_path' => $footerPath,
            'header_image_url' => Setting::storageUrl(Setting::normalizePublicDiskPath($headerPath)),
            'footer_image_url' => Setting::storageUrl(Setting::normalizePublicDiskPath($footerPath)),
            'header_image_src' => $headerSources['data'] ?: $headerSources['file'],
            'footer_image_src' => $footerSources['data'] ?: $footerSources['file'],
            'header_image_file' => $headerSources['file'],
            'footer_image_file' => $footerSources['file'],
            'legal_information' => Setting::getValue('invoice_legal_information', Setting::getValue('company_legal_information', '')),
            'company_address' => Setting::getValue('company_address', ''),
            'company_ice' => Setting::getValue('company_ice', ''),
            'company_if' => Setting::getValue('company_if', ''),
            'company_rc' => Setting::getValue('company_rc', ''),
            'default_conditions' => Setting::getValue('invoice_conditions', Setting::getValue('quote_conditions', '')),
        ];
    }

    private function publicDiskImageSources(?string $path): array
    {
        $absolutePath = $this->resolveImageAbsolutePath($path);
        if (! $absolutePath || ! is_file($absolutePath)) {
            return ['data' => null, 'file' => null];
        }

        $data = $this->imageDataUri($absolutePath);
        $file = 'file:///'.str_replace('\\', '/', $absolutePath);

        return ['data' => $data, 'file' => $file];
    }

    private function publicDiskImageSource(?string $path): ?string
    {
        $absolutePath = $this->resolveImageAbsolutePath($path);
        if (! $absolutePath || ! is_file($absolutePath)) {
            return null;
        }

        return $this->imageDataUri($absolutePath);
    }

    private function imageDataUri(string $absolutePath): ?string
    {
        $contents = @file_get_contents($absolutePath);
        if ($contents === false) {
            return null;
        }

        $mimeType = @mime_content_type($absolutePath) ?: $this->mimeTypeFromExtension($absolutePath);

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function resolveImageAbsolutePath(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $value = trim($path);

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $urlPath = parse_url($value, PHP_URL_PATH);
            $value = is_string($urlPath) ? $urlPath : $value;
        }

        $value = str_replace('\\', '/', $value);
        $value = ltrim($value, '/');

        foreach ([
            'admin/storage/',
            'booking/storage/',
            'storage/app/public/',
            'public/storage/',
            'storage/',
            'public/',
        ] as $prefix) {
            if (str_starts_with($value, $prefix)) {
                $value = substr($value, strlen($prefix));
                break;
            }
        }

        $normalized = Setting::normalizePublicDiskPath($value);
        if ($normalized && Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->path($normalized);
        }

        $candidatePaths = [
            storage_path('app/public/'.$value),
            public_path('storage/'.$value),
            public_path($value),
            base_path($value),
        ];

        foreach ($candidatePaths as $candidatePath) {
            if (is_file($candidatePath)) {
                return $candidatePath;
            }
        }

        return null;
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
