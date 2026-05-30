<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index');
    }

    public function page(Request $request)
    {
        $submenu = $request->route()->parameter('submenu');
        $view = 'admin.settings.' . $submenu . '.index';

        if ($submenu === 'parametres-generaux') {
            $storedLogoPath = Setting::getValue('brand_logo');
            $normalizedLogoPath = Setting::resolvedBrandLogoPath();
            if ($storedLogoPath !== $normalizedLogoPath && $normalizedLogoPath !== null) {
                Setting::setValue('brand_logo', $normalizedLogoPath);
            }

            $settings = [
                'brand_name' => Setting::getValue('brand_name'),
                'brand_logo' => $normalizedLogoPath,
                'brand_logo_url' => Setting::brandLogoUrl(),
                'topbar_phone' => Setting::getValue('topbar_phone'),
                'topbar_email' => Setting::getValue('topbar_email'),
                'social_facebook' => Setting::getValue('social_facebook'),
                'social_twitter' => Setting::getValue('social_twitter'),
                'social_instagram' => Setting::getValue('social_instagram'),
                'social_youtube' => Setting::getValue('social_youtube'),
                'hero_type' => Setting::getValue('hero_type'),
                'hero_image' => Setting::getValue('hero_image'),
                'hero_video' => Setting::getValue('hero_video'),
                'hero_overlay_opacity' => Setting::getValue('hero_overlay_opacity'),
                'hero_title' => Setting::getValue('hero_title'),
                'hero_subtitle' => Setting::getValue('hero_subtitle'),
                'invoice_header_image' => Setting::getValue('invoice_header_image'),
                'invoice_header_image_url' => Setting::storageUrl(Setting::getValue('invoice_header_image')),
                'invoice_footer_image' => Setting::getValue('invoice_footer_image'),
                'invoice_footer_image_url' => Setting::storageUrl(Setting::getValue('invoice_footer_image')),
                'default_hotel_image' => Setting::getValue('default_hotel_image'),
                'default_hotel_image_url' => Setting::storageUrl(Setting::getValue('default_hotel_image')),
                'default_transfer_image' => Setting::getValue('default_transfer_image'),
                'default_transfer_image_url' => Setting::storageUrl(Setting::getValue('default_transfer_image')),
                'default_activity_image' => Setting::getValue('default_activity_image'),
                'default_activity_image_url' => Setting::storageUrl(Setting::getValue('default_activity_image')),
                'ws_modal_show_commission' => Setting::getValue('ws_modal_show_commission', '1'),
                'ws_modal_show_commission_type' => Setting::getValue('ws_modal_show_commission_type', '1'),
                'ws_modal_show_commission_amount' => Setting::getValue('ws_modal_show_commission_amount', '1'),
                'ws_modal_show_commission_percentage' => Setting::getValue('ws_modal_show_commission_percentage', '1'),
                'ws_modal_show_commission_fixed' => Setting::getValue('ws_modal_show_commission_fixed', '1'),
                'ws_modal_show_commission_agent' => Setting::getValue('ws_modal_show_commission_agent', '1'),
                'ws_modal_show_commission_branch' => Setting::getValue('ws_modal_show_commission_branch', '1'),
                'ws_modal_show_commission_help' => Setting::getValue('ws_modal_show_commission_help', '1'),
                'ws_modal_show_departure_report' => Setting::getValue('ws_modal_show_departure_report', '1'),
            ];
            return view($view, compact('settings'));
        }

        return view($view);
    }

    /**
     * Update general parameters (front homepage settings).
     */
    public function updateParametresGeneraux(Request $request)
    {
        $currentHeroImage = Setting::getValue('hero_image');
        $currentHeroVideo = Setting::getValue('hero_video');

        // Validation de base (tout est nullable pour les fichiers)
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:255'],
            'brand_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'topbar_phone' => ['nullable', 'string', 'max:100'],
            'topbar_email' => ['nullable', 'email', 'max:255'],
            'social_facebook' => ['nullable', 'url', 'max:500'],
            'social_twitter' => ['nullable', 'url', 'max:500'],
            'social_instagram' => ['nullable', 'url', 'max:500'],
            'social_youtube' => ['nullable', 'url', 'max:500'],
            'hero_type' => ['required', Rule::in(['image', 'video'])],
            'hero_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'hero_video' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:51200'],
            'hero_overlay_opacity' => ['required', 'numeric', 'min:0', 'max:1'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:500'],
            'invoice_header_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:5120'],
            'invoice_footer_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:5120'],
            'default_hotel_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'default_transfer_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'default_activity_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_default_hotel_image' => ['nullable', 'in:0,1'],
            'remove_default_transfer_image' => ['nullable', 'in:0,1'],
            'remove_default_activity_image' => ['nullable', 'in:0,1'],
            'ws_modal_show_commission' => ['nullable', 'in:0,1'],
            'ws_modal_show_commission_type' => ['nullable', 'in:0,1'],
            'ws_modal_show_commission_amount' => ['nullable', 'in:0,1'],
            'ws_modal_show_commission_percentage' => ['nullable', 'in:0,1'],
            'ws_modal_show_commission_fixed' => ['nullable', 'in:0,1'],
            'ws_modal_show_commission_agent' => ['nullable', 'in:0,1'],
            'ws_modal_show_commission_branch' => ['nullable', 'in:0,1'],
            'ws_modal_show_commission_help' => ['nullable', 'in:0,1'],
            'ws_modal_show_departure_report' => ['nullable', 'in:0,1'],
        ]);

        // Vérification manuelle : si type=image et pas d'image actuelle ni uploadée
        if ($validated['hero_type'] === 'image' && empty($currentHeroImage) && !$request->hasFile('hero_image')) {
            return back()->withErrors(['hero_image' => 'The hero image field is required when type is image.'])->withInput();
        }

        // Vérification manuelle : si type=video et pas de vidéo actuelle ni uploadée
        if ($validated['hero_type'] === 'video' && empty($currentHeroVideo) && !$request->hasFile('hero_video')) {
            return back()->withErrors(['hero_video' => 'The hero video field is required when type is video.'])->withInput();
        }

        Setting::setValue('brand_name', $validated['brand_name']);
        Setting::setValue('topbar_phone', $validated['topbar_phone'] ?? '');
        Setting::setValue('topbar_email', $validated['topbar_email'] ?? '');
        Setting::setValue('social_facebook', $validated['social_facebook'] ?? '');
        Setting::setValue('social_twitter', $validated['social_twitter'] ?? '');
        Setting::setValue('social_instagram', $validated['social_instagram'] ?? '');
        Setting::setValue('social_youtube', $validated['social_youtube'] ?? '');
        Setting::setValue('hero_type', $validated['hero_type']);
        Setting::setValue('hero_overlay_opacity', (string) $validated['hero_overlay_opacity']);
        Setting::setValue('hero_title', $validated['hero_title']);
        Setting::setValue('hero_subtitle', $validated['hero_subtitle'] ?? '');

        Setting::setValue('ws_modal_show_commission', $request->input('ws_modal_show_commission', '0'));
        Setting::setValue('ws_modal_show_commission_type', $request->input('ws_modal_show_commission_type', '0'));
        Setting::setValue('ws_modal_show_commission_amount', $request->input('ws_modal_show_commission_amount', '0'));
        Setting::setValue('ws_modal_show_commission_percentage', $request->input('ws_modal_show_commission_percentage', '0'));
        Setting::setValue('ws_modal_show_commission_fixed', $request->input('ws_modal_show_commission_fixed', '0'));
        Setting::setValue('ws_modal_show_commission_agent', $request->input('ws_modal_show_commission_agent', '0'));
        Setting::setValue('ws_modal_show_commission_branch', $request->input('ws_modal_show_commission_branch', '0'));
        Setting::setValue('ws_modal_show_commission_help', $request->input('ws_modal_show_commission_help', '0'));
        Setting::setValue('ws_modal_show_departure_report', $request->input('ws_modal_show_departure_report', '0'));

        if ($request->hasFile('brand_logo')) {
            $oldPath = Setting::normalizePublicDiskPath(Setting::getValue('brand_logo'));
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('brand_logo')->store('front/brand', 'public');
            Setting::setValue('brand_logo', Setting::normalizePublicDiskPath($path));
        }

        if ($request->hasFile('hero_image')) {
            $oldPath = Setting::getValue('hero_image');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('hero_image')->store('front/hero', 'public');
            Setting::setValue('hero_image', $path);
        }

        if ($request->hasFile('hero_video')) {
            $oldPath = Setting::getValue('hero_video');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('hero_video')->store('front/hero', 'public');
            Setting::setValue('hero_video', $path);
        }

        if ($request->hasFile('invoice_header_image')) {
            $oldPath = Setting::normalizePublicDiskPath(Setting::getValue('invoice_header_image'));
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('invoice_header_image')->store('settings/invoices', 'public');
            Setting::setValue('invoice_header_image', Setting::normalizePublicDiskPath($path));
        }

        if ($request->hasFile('invoice_footer_image')) {
            $oldPath = Setting::normalizePublicDiskPath(Setting::getValue('invoice_footer_image'));
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('invoice_footer_image')->store('settings/invoices', 'public');
            Setting::setValue('invoice_footer_image', Setting::normalizePublicDiskPath($path));
        }

        // Default images (hotels/transfers/activities) used as fallback when no per-item image is set.
        foreach ([
            'default_hotel_image' => 'settings/default-images/hotel',
            'default_transfer_image' => 'settings/default-images/transfer',
            'default_activity_image' => 'settings/default-images/activity',
        ] as $key => $dir) {
            $removeKey = 'remove_' . $key;
            if ($request->input($removeKey) === '1') {
                $oldPath = Setting::normalizePublicDiskPath(Setting::getValue($key));
                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
                Setting::setValue($key, '');
            }

            if ($request->hasFile($key)) {
                $oldPath = Setting::normalizePublicDiskPath(Setting::getValue($key));
                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
                $path = $request->file($key)->store($dir, 'public');
                Setting::setValue($key, Setting::normalizePublicDiskPath($path));
            }
        }

        // Make defaults available to the WP connection too (options table) for theme consumption if needed.
        try {
            $payload = [
                'default_hotel_image_url' => Setting::storageUrl(Setting::getValue('default_hotel_image')),
                'default_transfer_image_url' => Setting::storageUrl(Setting::getValue('default_transfer_image')),
                'default_activity_image_url' => Setting::storageUrl(Setting::getValue('default_activity_image')),
                'updated_at' => now()->toIso8601String(),
            ];

            DB::connection('wp')->table('options')->updateOrInsert(
                ['option_name' => 'aj_default_images'],
                [
                    'option_value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'autoload' => 'no',
                ]
            );
        } catch (\Throwable $e) {
            // Non-blocking: defaults are still stored in Laravel settings.
        }

        return redirect()
            ->route('admin.settings.parametres-generaux')
            ->with('success', __('Parameters saved successfully.'));
    }
}
