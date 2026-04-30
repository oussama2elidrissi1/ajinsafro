<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientNotification;
use App\Models\GroupDealParticipant;
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

        $recentGroupDeals = GroupDealParticipant::query()
            ->with('groupDeal:id,title,slug,current_price,status')
            ->where(function ($query) use ($client, $user) {
                $query->where('client_id', $client->id)
                    ->orWhere('user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $notifications = ClientNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return view('client.dashboard', [
            'client' => $client,
            'recentReservations' => $recent,
            'recentGroupDeals' => $recentGroupDeals,
            'notifications' => $notifications,
        ]);
    }
}
