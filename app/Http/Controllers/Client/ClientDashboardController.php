<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $client = Client::query()->where('user_id', $user->id)->first();
        abort_unless($client, 403);

        $recent = Reservation::query()
            ->where('client_external_id', $client->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('client.dashboard', [
            'client' => $client,
            'recentReservations' => $recent,
        ]);
    }
}

