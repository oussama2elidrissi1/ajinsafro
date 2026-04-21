<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ClientReservationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $client = Client::query()->where('user_id', $user->id)->first();
        abort_unless($client, 403);

        $reservations = Reservation::query()
            ->where('client_external_id', $client->id)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('client.reservations.index', [
            'client' => $client,
            'reservations' => $reservations,
        ]);
    }

    public function show(Request $request, Reservation $reservation)
    {
        $user = $request->user();
        $client = Client::query()->where('user_id', $user->id)->first();
        abort_unless($client, 403);

        abort_unless((int) $reservation->client_external_id === (int) $client->id, 404);

        $reservation->load(['passengers', 'extras', 'reservationRooms']);

        return view('client.reservations.show', [
            'client' => $client,
            'reservation' => $reservation,
        ]);
    }
}

