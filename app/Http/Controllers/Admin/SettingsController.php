<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
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
            $settings = [
                'brand_name' => Setting::getValue('brand_name'),
                'brand_logo' => Setting::getValue('brand_logo'),
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

        if ($request->hasFile('brand_logo')) {
            $oldPath = Setting::getValue('brand_logo');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('brand_logo')->store('front/brand', 'public');
            Setting::setValue('brand_logo', $path);
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

        return redirect()
            ->route('admin.settings.parametres-generaux')
            ->with('success', __('Parameters saved successfully.'));
    }
}
