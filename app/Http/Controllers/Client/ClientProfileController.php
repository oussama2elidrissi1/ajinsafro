<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $client = Client::query()->where('user_id', $user->id)->first();
        abort_unless($client, 403);

        return view('client.profile.edit', [
            'client' => $client,
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $client = Client::query()->where('user_id', $user->id)->first();
        abort_unless($client, 403);

        // Password change (separate form submission)
        if ($request->boolean('change_password')) {
            $data = $request->validate([
                'current_password' => ['nullable', 'string', 'max:200'],
                'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
            ]);

            // If user already has a password set, require current password.
            $hasPassword = ! empty((string) ($user->getAuthPassword() ?? ''));
            if ($hasPassword) {
                $current = (string) ($data['current_password'] ?? '');
                if ($current === '' || ! Hash::check($current, $user->getAuthPassword())) {
                    return back()
                        ->withErrors(['current_password' => 'Mot de passe actuel incorrect.'])
                        ->withInput();
                }
            }

            $user->password = (string) $data['password']; // hashed via cast in User model
            $user->save();

            // Optional hygiene: clear temp password after user changed it.
            if (! empty($client->portal_temp_password)) {
                $client->portal_temp_password = null;
                $client->portal_temp_password_created_at = null;
                $client->save();
            }

            return redirect()
                ->route('client.profile.edit')
                ->with('success', 'Mot de passe mis à jour.');
        }

        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:120'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
        ]);

        $client->fill($data);
        $client->save();

        // Keep user name in sync (optional).
        $name = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
        if ($name !== '' && (string) ($user->name ?? '') !== $name) {
            $user->name = $name;
            $user->save();
        }

        return redirect()
            ->route('client.profile.edit')
            ->with('success', 'Profil mis à jour.');
    }
}

