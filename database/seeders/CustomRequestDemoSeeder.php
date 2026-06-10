<?php

namespace Database\Seeders;

use App\Models\CustomRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CustomRequestDemoSeeder extends Seeder
{
    public const OFFLINE_EMAIL = 'offline.demo@ajinsafro.ma';
    public const OFFLINE_PASSWORD = 'AgentOffline@2026';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $offlinePermissions = [
            'custom_requests.view',
            'custom_requests.quote',
            'custom_requests.documents',
        ];

        $commercialPermissions = [
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
            'custom_requests.edit',
            'custom_requests.confirm',
            'custom_requests.cancel',
            'custom_requests.documents',
        ];

        foreach (array_unique(array_merge($offlinePermissions, $commercialPermissions)) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $offlineRole = Role::findOrCreate('Agent Offline', 'web');
        $offlineRole->syncPermissions($offlinePermissions);

        $commercialRole = Role::findOrCreate('Agent Commercial Demo', 'web');
        $commercialRole->syncPermissions($commercialPermissions);

        $offline = User::query()->updateOrCreate(
            ['email' => self::OFFLINE_EMAIL],
            [
                'name' => 'Agent Offline Demo',
                'password' => Hash::make(self::OFFLINE_PASSWORD),
                'is_admin' => false,
                'is_active' => true,
                'access_mode' => 'role',
                'base_role' => 'Agent Offline',
                'job_title' => 'Agent offline / cotation',
            ]
        );
        $offline->syncRoles([$offlineRole]);

        $commercial = User::query()->updateOrCreate(
            ['email' => 'commercial.demo@ajinsafro.ma'],
            [
                'name' => 'Agent Commercial Demo',
                'password' => Hash::make('CommercialDemo@2026'),
                'is_admin' => false,
                'is_active' => true,
                'access_mode' => 'role',
                'base_role' => 'Agent Commercial Demo',
                'job_title' => 'Agent commercial',
            ]
        );
        $commercial->syncRoles([$commercialRole]);

        $newRequest = CustomRequest::query()->firstOrCreate(
            ['request_number' => 'DAC-2026-DEMO1'],
            [
                'created_by' => $commercial->id,
                'customer_full_name' => 'Client Demo Nouvelle Demande',
                'customer_phone' => '+212600000111',
                'customer_email' => 'client.demo@example.test',
                'customer_city' => 'Fès',
                'customer_country' => 'Maroc',
                'customer_type' => 'new_customer',
                'desired_destination' => 'Istanbul',
                'departure_city' => 'Casablanca',
                'desired_departure_date' => now()->addDays(30)->toDateString(),
                'desired_return_date' => now()->addDays(37)->toDateString(),
                'desired_duration' => '7 nuits',
                'travel_type' => 'organized_trip',
                'travelers_count' => 2,
                'adults_count' => 2,
                'children_count' => 0,
                'babies_count' => 0,
                'currency' => 'MAD',
                'payment_status' => 'unpaid',
                'status' => CustomRequest::STATUS_NEW,
                'priority' => 'urgent',
                'requested_services_details' => 'Hôtel 4*, vols et transferts.',
            ]
        );

        if ($newRequest->statusLogs()->count() === 0) {
            $newRequest->statusLogs()->create([
                'user_id' => $commercial->id,
                'old_status' => null,
                'new_status' => CustomRequest::STATUS_NEW,
                'note' => 'Demande démo créée pour agent offline.',
            ]);
        }

        $assignedRequest = CustomRequest::query()->firstOrCreate(
            ['request_number' => 'DAC-2026-DEMO2'],
            [
                'created_by' => $commercial->id,
                'assigned_to' => $offline->id,
                'customer_full_name' => 'Client Demo Assigné',
                'customer_phone' => '+212600000222',
                'customer_email' => 'client.assigne@example.test',
                'customer_city' => 'Rabat',
                'customer_country' => 'Maroc',
                'customer_type' => 'existing_customer',
                'desired_destination' => 'Dubaï',
                'departure_city' => 'Casablanca',
                'desired_departure_date' => now()->addDays(45)->toDateString(),
                'desired_return_date' => now()->addDays(52)->toDateString(),
                'desired_duration' => '7 jours',
                'travel_type' => 'hotel_stay',
                'travelers_count' => 3,
                'adults_count' => 2,
                'children_count' => 1,
                'babies_count' => 0,
                'currency' => 'MAD',
                'payment_status' => 'unpaid',
                'status' => CustomRequest::STATUS_PROCESSING,
                'priority' => 'normal',
                'requested_services_details' => 'Séjour hôtel + transferts privés.',
            ]
        );

        if ($assignedRequest->statusLogs()->count() === 0) {
            $assignedRequest->statusLogs()->create([
                'user_id' => $offline->id,
                'old_status' => CustomRequest::STATUS_ASSIGNED,
                'new_status' => CustomRequest::STATUS_PROCESSING,
                'note' => 'Demande démo prise en charge.',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
