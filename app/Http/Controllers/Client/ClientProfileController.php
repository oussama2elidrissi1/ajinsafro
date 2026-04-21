<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

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

