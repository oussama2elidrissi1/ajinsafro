<?php

namespace App\Providers;

use App\Models\User;
use App\Support\FinanceControlPermissions;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        /**
         * Acces au module « Finance & Controle ».
         *
         * Defini comme Gate et non comme permission Spatie : il ne peut donc etre accorde
         * depuis l'ecran Roles & Permissions, et le menu applique exactement la meme regle
         * que le middleware `finance.control`.
         */
        Gate::define(FinanceControlPermissions::ACCESS_GATE, function (User $user): bool {
            return FinanceControlPermissions::userIsFinanceAdmin($user);
        });
    }
}
