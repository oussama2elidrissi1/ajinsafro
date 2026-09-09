<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChargeType;
use App\Models\Departure;
use App\Models\DepartureCharge;
use App\Models\FinanceSupplier;
use App\Models\FinancialDocument;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\StructuralExpense;
use App\Models\User;
use App\Models\Voyage;
use App\Services\BranchScopeService;
use App\Services\Finance\FinanceControlService;
use App\Services\Finance\StructuralExpenseRecurrenceService;
use App\Support\AdminMenuPermissionRegistry;
use App\Support\FinanceControlPermissions;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Module « Finance & Controle ».
 *
 * Couvre les regles de calcul et, surtout, le cloisonnement d'acces : le module doit rester
 * invisible et inaccessible a tout role autre que l'administration globale.
 */
class FinanceControlModuleTest extends TestCase
{
    use RefreshDatabase;

    private Departure $departure;

    private Voyage $voyage;

    private Branch $branch;

    protected function setUp(): void
    {
        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite est requis pour ce test isole.');
        }

        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    /**
     * Jeu de donnees commun :
     *   CA vendu       = 24 000 + 10 000 = 34 000
     *   Encaisse       =  5 000 + 10 000 + 4 000 = 19 000
     *   Reste clients  = 15 000
     *   Charges reelles (hors annulee) = 8 000 + 3 000 = 11 000
     *   Charges payees  = 8 000 + 1 000 = 9 000
     *   Reste fournisseurs = 2 000
     *   Marge reelle   = 34 000 - 11 000 = 23 000
     */
    private function seedProject(): void
    {
        $this->branch = Branch::query()->create(['name' => 'Agence Tanger', 'code' => 'TNG']);
        $this->voyage = Voyage::query()->create(['name' => 'Circuit Sud', 'slug' => 'circuit-sud']);
        $this->departure = Departure::query()->create([
            'voyage_id' => $this->voyage->id,
            'start_date' => '2026-10-05',
            'status' => Departure::STATUS_OPEN,
        ]);

        $first = $this->makeReservation(24000, 3);
        $second = $this->makeReservation(10000, 2);

        // Une reservation annulee ne doit peser ni dans le CA ni dans les encaissements.
        $cancelled = Reservation::query()->create([
            'voyage_id' => $this->voyage->id,
            'tour_id' => $this->voyage->id,
            'departure_id' => $this->departure->id,
            'branch_id' => $this->branch->id,
            'status' => Reservation::STATUS_CANCELLED,
            'dossier_status' => Reservation::DOSSIER_CANCELLED,
            'total_amount' => 50000,
            'passengers_count' => 4,
        ]);
        $this->makePayment($cancelled, 50000, '2026-09-01');

        $this->makePayment($first, 5000, '2026-09-01', 'espece');
        $this->makePayment($first, 10000, '2026-09-10', 'virement');
        $this->makePayment($second, 4000, '2026-09-12', 'virement');

        $type = ChargeType::query()->create(['name' => 'Hotel', 'slug' => 'hotel', 'is_active' => true]);
        $supplier = FinanceSupplier::query()->create(['name' => 'Hotel Atlas', 'type' => 'hotel', 'is_active' => true]);

        DepartureCharge::query()->create([
            'departure_id' => $this->departure->id,
            'voyage_id' => $this->voyage->id,
            'branch_id' => $this->branch->id,
            'charge_type_id' => $type->id,
            'supplier_id' => $supplier->id,
            'title' => 'Hebergement',
            'amount' => 8000,
            'planned_amount' => 7500,
            'paid_amount' => 8000,
            'status' => DepartureCharge::STATUS_PAID,
            'payment_status' => 'paye',
            'payment_method' => 'ordre_virement',
            'charge_date' => '2026-09-15',
        ]);

        DepartureCharge::query()->create([
            'departure_id' => $this->departure->id,
            'voyage_id' => $this->voyage->id,
            'branch_id' => $this->branch->id,
            'charge_type_id' => $type->id,
            'supplier_id' => $supplier->id,
            'title' => 'Transport',
            'amount' => 3000,
            'planned_amount' => 3000,
            'paid_amount' => 1000,
            'status' => DepartureCharge::STATUS_PARTIALLY_PAID,
            'payment_status' => 'partiel',
            'payment_method' => 'espece',
            'charge_date' => '2026-09-16',
        ]);

        // Charge annulee : conservee pour l'historique, exclue de tous les totaux.
        DepartureCharge::query()->create([
            'departure_id' => $this->departure->id,
            'voyage_id' => $this->voyage->id,
            'charge_type_id' => $type->id,
            'title' => 'Prestation annulee',
            'amount' => 9999,
            'planned_amount' => 9999,
            'paid_amount' => 0,
            'status' => DepartureCharge::STATUS_CANCELLED,
            'payment_status' => 'non_paye',
            'payment_method' => 'autre',
            'charge_date' => '2026-09-17',
        ]);
    }

    private function makeReservation(float $total, int $passengers): Reservation
    {
        return Reservation::query()->create([
            'voyage_id' => $this->voyage->id,
            'tour_id' => $this->voyage->id,
            'departure_id' => $this->departure->id,
            'branch_id' => $this->branch->id,
            'status' => Reservation::STATUS_CONFIRMED,
            'dossier_status' => Reservation::DOSSIER_CONFIRMED,
            'total_amount' => $total,
            'passengers_count' => $passengers,
        ]);
    }

    private function makePayment(Reservation $reservation, float $amount, string $date, string $method = 'espece'): ReservationPayment
    {
        return ReservationPayment::query()->create([
            'reservation_id' => $reservation->id,
            'payment_date' => $date,
            'payment_method' => $method,
            'amount' => $amount,
        ]);
    }

    private function adminUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate(BranchScopeService::ROLE_SUPER_ADMIN, 'web');
        $role->givePermissionTo(Permission::findOrCreate(AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION, 'web'));

        foreach (FinanceControlPermissions::moduleOwned() as $name) {
            $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    /**
     * Role NON super_admin a qui on attribue volontairement les permissions du module
     * ET la permission d'acces admin : il ne doit malgre tout pas pouvoir entrer.
     *
     * C'est le scenario le plus defavorable : il prouve que le verrou est bien le role,
     * et non une permission qui pourrait etre cochee par erreur dans Roles & Permissions.
     */
    private function nonAdminUser(string $roleName = BranchScopeService::ROLE_CHEF_COMMERCIAL): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate($roleName, 'web');
        $role->givePermissionTo(Permission::findOrCreate(AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION, 'web'));

        foreach (FinanceControlPermissions::moduleOwned() as $name) {
            $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    /** 1. L'administrateur accede a toutes les pages du module. */
    public function test_admin_can_access_every_finance_control_page(): void
    {
        $this->seedProject();
        $admin = $this->adminUser();

        $urls = [
            route('admin.finance.control.dashboard'),
            route('admin.finance.control.travel-projects.index'),
            route('admin.finance.control.travel-projects.show', $this->departure),
            route('admin.finance.control.client-collections.index'),
            route('admin.finance.control.travel-expenses.index'),
            route('admin.finance.control.structural-expenses.index'),
            route('admin.finance.control.structural-expenses.recurrences'),
            route('admin.finance.control.suppliers.index'),
            route('admin.finance.control.treasury.index'),
            route('admin.finance.control.documents.index'),
            route('admin.finance.control.results.index'),
            route('admin.finance.control.exports.index'),
            route('admin.finance.control.travel-expenses.create'),
            route('admin.finance.control.structural-expenses.create'),
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        // Formulaires d'edition : rendus avec un enregistrement existant.
        $charge = DepartureCharge::query()->where('title', 'Transport')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.finance.control.travel-expenses.edit', $charge))->assertOk();

        $expense = StructuralExpense::query()->create([
            'branch_id' => $this->branch->id,
            'category' => 'loyer',
            'label' => 'Loyer test',
            'amount' => 1000,
            'status' => StructuralExpense::STATUS_PLANNED,
            'expense_date' => '2026-09-01',
        ]);
        $this->actingAs($admin)->get(route('admin.finance.control.structural-expenses.edit', $expense))->assertOk();

        // Export comptable : flux CSV telechargeable.
        $this->actingAs($admin)
            ->get(route('admin.finance.control.exports.download', ['type' => 'projets']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /** 2. Tout role non administrateur recoit 403, meme avec les permissions attribuees par erreur. */
    public function test_non_admin_roles_are_forbidden_even_with_permissions_granted(): void
    {
        $this->seedProject();

        $roles = [
            BranchScopeService::ROLE_SIEGE_ADMIN,
            BranchScopeService::ROLE_BRANCH_ADMIN,
            BranchScopeService::ROLE_CHEF_COMMERCIAL,
            BranchScopeService::ROLE_MANAGER,
            BranchScopeService::ROLE_COMMERCIAL,
            BranchScopeService::ROLE_AGENT,
            BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY,
            'partner_admin',
            'partner_agent',
            // Roles legacy : ils ne doivent pas davantage ouvrir le module.
            'Admin',
            'Super Admin',
            'Admin Siege',
            'Comptable',
        ];

        foreach ($roles as $roleName) {
            $user = $this->nonAdminUser($roleName);

            $this->actingAs($user)->get(route('admin.finance.control.dashboard'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.finance.control.travel-projects.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.finance.control.structural-expenses.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.finance.control.treasury.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.finance.control.documents.index'))->assertForbidden();
        }
    }

    /**
     * 2 bis. Aucun contournement legacy : ni `is_admin`, ni compte dev, ni `access_mode = custom`
     * ne doit ouvrir le module a un utilisateur qui n'a pas le role super_admin.
     */
    public function test_legacy_is_admin_flag_does_not_bypass_the_role_restriction(): void
    {
        $this->seedProject();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION];
        foreach (FinanceControlPermissions::moduleOwned() as $name) {
            $permissions[] = $name;
        }
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Compte historique : flag is_admin, permissions directes, aucun role super_admin.
        $legacyAdmin = User::factory()->create(['is_admin' => true]);
        $legacyAdmin->givePermissionTo($permissions);

        // Compte dev identifie par email (App\Models\User::DEV_ADMIN_EMAILS).
        $devAccount = User::factory()->create(['email' => 'dev@ajinsafro.ma', 'is_admin' => true]);
        $devAccount->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([$legacyAdmin->fresh(), $devAccount->fresh()] as $user) {
            $this->assertFalse(FinanceControlPermissions::userIsFinanceAdmin($user));
            $this->actingAs($user)->get(route('admin.finance.control.dashboard'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.finance.control.travel-projects.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.finance.control.structural-expenses.index'))->assertForbidden();
        }
    }

    /** Le role super_admin, et lui seul, ouvre le module. */
    public function test_only_super_admin_role_grants_access(): void
    {
        $this->seedProject();

        $superAdmin = $this->adminUser();
        $this->assertTrue($superAdmin->hasRole(BranchScopeService::ROLE_SUPER_ADMIN));
        $this->assertTrue(FinanceControlPermissions::userIsFinanceAdmin($superAdmin));
        $this->actingAs($superAdmin)->get(route('admin.finance.control.dashboard'))->assertOk();

        $siegeAdmin = $this->nonAdminUser(BranchScopeService::ROLE_SIEGE_ADMIN);
        $this->assertFalse(FinanceControlPermissions::userIsFinanceAdmin($siegeAdmin));
        $this->actingAs($siegeAdmin)->get(route('admin.finance.control.dashboard'))->assertForbidden();

        // La liste des roles autorises reste volontairement reduite a un seul element.
        $this->assertSame([BranchScopeService::ROLE_SUPER_ADMIN], FinanceControlPermissions::adminRoleNames());
    }

    /** Le seeder de roles ne redistribue jamais les permissions du module. */
    public function test_role_seeder_grants_finance_permissions_to_super_admin_only(): void
    {
        $this->seed(\Database\Seeders\AjinsafroRolesSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (FinanceControlPermissions::moduleOwned() as $permission) {
            $holders = Role::query()
                ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
                ->pluck('name')
                ->all();

            $this->assertSame(
                [BranchScopeService::ROLE_SUPER_ADMIN],
                $holders,
                "La permission {$permission} ne doit etre portee que par super_admin."
            );
        }
    }

    /** 2 ter. Les ecritures sont refusees aux non-admins. */
    public function test_non_admin_cannot_write_finance_data(): void
    {
        $this->seedProject();
        $user = $this->nonAdminUser();

        $this->actingAs($user)
            ->post(route('admin.finance.control.structural-expenses.store'), [
                'category' => 'loyer',
                'label' => 'Tentative',
                'amount' => 100,
                'status' => StructuralExpense::STATUS_PLANNED,
                'expense_date' => '2026-09-01',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('structural_expenses', ['label' => 'Tentative']);
    }

    /** Niveau 1 : le menu n'expose le module qu'au role super_admin. */
    public function test_menu_exposes_module_only_to_super_admin(): void
    {
        $menuService = app(\App\Services\Admin\AdminMenuService::class);

        $keysFor = function (User $user) use ($menuService): array {
            return collect($menuService->buildForUser($user))->pluck('key')->all();
        };

        $this->assertContains('finance-control', $keysFor($this->adminUser()));

        foreach ([
            BranchScopeService::ROLE_SIEGE_ADMIN,
            BranchScopeService::ROLE_BRANCH_ADMIN,
            BranchScopeService::ROLE_CHEF_COMMERCIAL,
            BranchScopeService::ROLE_MANAGER,
            BranchScopeService::ROLE_COMMERCIAL,
            BranchScopeService::ROLE_AGENT,
            'partner_admin',
            'Admin',
            'Super Admin',
        ] as $roleName) {
            $this->assertNotContains(
                'finance-control',
                $keysFor($this->nonAdminUser($roleName)),
                "Le menu ne doit pas apparaitre pour le role {$roleName}."
            );
        }
    }

    /** 3, 4, 7, 8, 9. Les paiements remontent et tous les totaux du projet sont exacts. */
    public function test_project_totals_are_computed_from_existing_reservations_and_payments(): void
    {
        $this->seedProject();

        $summary = app(FinanceControlService::class)->projectSummary($this->departure);

        $this->assertSame(2, $summary['reservations_count']);
        $this->assertSame(5, $summary['travelers_count']);

        // 3 + 4 : CA vendu et encaissements issus des donnees existantes, hors dossier annule.
        $this->assertSame(34000.0, $summary['sold_amount']);
        $this->assertSame(19000.0, $summary['collected_amount']);

        // 8 : reste client
        $this->assertSame(15000.0, $summary['client_remaining']);

        // 5 : charges agregees, charge annulee exclue
        $this->assertSame(10500.0, $summary['planned_charges']);
        $this->assertSame(11000.0, $summary['real_charges']);
        $this->assertSame(9000.0, $summary['paid_charges']);

        // 9 : reste fournisseur
        $this->assertSame(2000.0, $summary['supplier_remaining']);

        // 7 : marges et taux
        $this->assertSame(23500.0, $summary['planned_margin']);
        $this->assertSame(23000.0, $summary['real_margin']);
        $this->assertSame(67.65, $summary['margin_rate']);
        $this->assertTrue($summary['is_profitable']);
    }

    /** 6. Les charges de structure ne sont jamais melangees aux charges de voyage. */
    public function test_structural_expenses_never_affect_project_margin(): void
    {
        $this->seedProject();

        StructuralExpense::query()->create([
            'branch_id' => $this->branch->id,
            'category' => 'loyer',
            'label' => 'Loyer Tanger',
            'amount' => 12000,
            'paid_amount' => 12000,
            'status' => StructuralExpense::STATUS_PAID,
            'expense_date' => '2026-09-01',
        ]);

        $service = app(FinanceControlService::class);
        $summary = $service->projectSummary($this->departure);

        // La marge du projet est inchangee par la charge de structure.
        $this->assertSame(11000.0, $summary['real_charges']);
        $this->assertSame(23000.0, $summary['real_margin']);

        // Elle est bien comptabilisee, mais dans son propre agregat.
        $structural = $service->structuralTotals(null, null, null);
        $this->assertSame(12000.0, $structural['amount']);
        $this->assertSame(0.0, $structural['remaining']);

        // Et aucune charge de structure n'a atterri dans departure_charges.
        $this->assertDatabaseMissing('departure_charges', ['title' => 'Loyer Tanger']);
    }

    /** Le cash encaisse n'est pas assimile a un benefice. */
    public function test_collected_cash_is_not_treated_as_profit(): void
    {
        $this->seedProject();

        $summary = app(FinanceControlService::class)->projectSummary($this->departure);

        $this->assertNotEquals($summary['collected_amount'], $summary['real_margin']);
        $this->assertSame(
            round($summary['sold_amount'] - $summary['real_charges'], 2),
            $summary['real_margin']
        );
    }

    /** 10. Un justificatif est rattache au bon mouvement, et seulement a lui. */
    public function test_documents_are_attached_to_the_correct_movement(): void
    {
        $this->seedProject();
        $admin = $this->adminUser();

        $payment = ReservationPayment::query()->where('amount', 5000)->firstOrFail();
        $charge = DepartureCharge::query()->where('title', 'Transport')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.finance.control.documents.store'), [
                'document_type' => 'recu_client',
                'status' => FinancialDocument::STATUS_TO_CHECK,
                'movement_type' => 'payment',
                'movement_id' => $payment->id,
                'amount' => 5000,
            ])
            ->assertRedirect();

        $document = FinancialDocument::query()->firstOrFail();

        $this->assertSame($payment->getMorphClass(), $document->documentable_type);
        $this->assertSame($payment->id, $document->documentable_id);
        // Le contexte est herite du mouvement : agence et depart de la reservation.
        $this->assertSame($this->departure->id, $document->departure_id);
        $this->assertSame($this->branch->id, $document->branch_id);
        $this->assertSame($admin->id, $document->created_by);

        $this->assertTrue($payment->financialDocuments()->exists());
        $this->assertFalse($charge->documents()->exists());
    }

    /** Le filtre « operations sans justificatif » respecte le seuil de montant. */
    public function test_missing_documents_filter_honours_amount_threshold(): void
    {
        $this->seedProject();
        $service = app(FinanceControlService::class);

        // 4 paiements existent (dont celui du dossier annule) : aucun n'a de piece.
        $this->assertSame(4, $service->paymentsMissingDocumentsQuery()->count());
        $this->assertSame(2, $service->paymentsMissingDocumentsQuery(10000)->count());

        // Charges non annulees sans piece : Hebergement et Transport.
        $this->assertSame(2, $service->chargesMissingDocumentsQuery()->count());
    }

    /** Une charge annulee sort des totaux mais reste en base (pas de suppression physique). */
    public function test_cancelling_a_charge_preserves_history(): void
    {
        $this->seedProject();
        $admin = $this->adminUser();
        $charge = DepartureCharge::query()->where('title', 'Transport')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.finance.control.travel-expenses.cancel', $charge))
            ->assertRedirect();

        $this->assertDatabaseHas('departure_charges', [
            'id' => $charge->id,
            'status' => DepartureCharge::STATUS_CANCELLED,
            'deleted_at' => null,
        ]);

        $summary = app(FinanceControlService::class)->projectSummary($this->departure);
        $this->assertSame(8000.0, $summary['real_charges']);
    }

    /** La generation des charges recurrentes est idempotente : jamais de doublon. */
    public function test_recurring_structural_expenses_generate_without_duplicates(): void
    {
        $branch = Branch::query()->create(['name' => 'Agence Casa', 'code' => 'CASA']);
        $admin = $this->adminUser();

        StructuralExpense::query()->create([
            'branch_id' => $branch->id,
            'category' => 'loyer',
            'label' => 'Loyer Casa',
            'amount' => 12000,
            'status' => StructuralExpense::STATUS_PAID,
            'expense_date' => '2026-01-01',
            'is_recurring' => true,
            'recurrence' => 'mensuelle',
        ]);

        $service = app(StructuralExpenseRecurrenceService::class);
        $until = CarbonImmutable::parse('2026-06-30');

        $created = $service->generate($until, $admin->id);
        $this->assertSame(5, $created, 'Fevrier a juin, soit 5 occurrences.');

        // Deuxieme passage sur la meme periode : rien de nouveau.
        $this->assertSame(0, $service->generate($until, $admin->id));
        $this->assertSame(5, StructuralExpense::query()->whereNotNull('recurrence_parent_id')->count());

        // Les occurrences ne sont pas elles-memes recurrentes : pas de cascade.
        $this->assertSame(0, StructuralExpense::query()->whereNotNull('recurrence_parent_id')->where('is_recurring', true)->count());
    }

    /** Le module ne modifie jamais une reservation ni un paiement existant. */
    public function test_module_never_mutates_reservations_or_payments(): void
    {
        $this->seedProject();
        $admin = $this->adminUser();

        $before = Reservation::query()->orderBy('id')->get(['id', 'total_amount', 'status', 'paid_amount'])->toArray();
        $paymentsBefore = ReservationPayment::query()->orderBy('id')->get(['id', 'amount', 'payment_method'])->toArray();

        $this->actingAs($admin)->get(route('admin.finance.control.travel-projects.show', $this->departure))->assertOk();
        $this->actingAs($admin)->get(route('admin.finance.control.client-collections.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.finance.control.treasury.index'))->assertOk();

        $this->assertSame($before, Reservation::query()->orderBy('id')->get(['id', 'total_amount', 'status', 'paid_amount'])->toArray());
        $this->assertSame($paymentsBefore, ReservationPayment::query()->orderBy('id')->get(['id', 'amount', 'payment_method'])->toArray());
    }

    /** Les montants negatifs sont refuses cote serveur. */
    public function test_negative_amounts_are_rejected(): void
    {
        $this->seedProject();
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.finance.control.travel-expenses.store'), [
                'departure_id' => $this->departure->id,
                'title' => 'Charge negative',
                'amount' => -50,
                'status' => DepartureCharge::STATUS_PLANNED,
                'payment_method' => 'espece',
            ])
            ->assertSessionHasErrors('amount');

        // Un montant paye superieur au montant reel est egalement refuse.
        $this->actingAs($admin)
            ->post(route('admin.finance.control.travel-expenses.store'), [
                'departure_id' => $this->departure->id,
                'title' => 'Trop paye',
                'amount' => 100,
                'paid_amount' => 500,
                'status' => DepartureCharge::STATUS_PLANNED,
                'payment_method' => 'espece',
            ])
            ->assertSessionHasErrors('paid_amount');

        $this->assertDatabaseMissing('departure_charges', ['title' => 'Charge negative']);
    }

    /** Un depart sans vente ne produit ni division par zero ni taux aberrant. */
    public function test_project_without_sales_has_zero_margin_rate(): void
    {
        $voyage = Voyage::query()->create(['name' => 'Circuit vide', 'slug' => 'circuit-vide']);
        $departure = Departure::query()->create([
            'voyage_id' => $voyage->id,
            'start_date' => '2026-11-01',
            'status' => Departure::STATUS_OPEN,
        ]);

        $summary = app(FinanceControlService::class)->projectSummary($departure);

        $this->assertSame(0.0, $summary['sold_amount']);
        $this->assertSame(0.0, $summary['margin_rate']);
        $this->assertFalse($summary['is_profitable']);
    }
}
