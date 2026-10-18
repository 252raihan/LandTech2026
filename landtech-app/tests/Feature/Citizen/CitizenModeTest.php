<?php

namespace Tests\Feature\Citizen;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 — Citizen mode selection.
 *
 * THE CENTRAL CLAIM UNDER TEST: switching modes is a navigation preference and
 * nothing else. It must never touch `role_user`, never grant a permission, and
 * never give a citizen access to a registry, bank or admin function.
 */
class CitizenModeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function citizen(): User
    {
        $user = User::factory()->create();

        $role = Role::query()->firstOrCreate(
            ['name' => Role::CITIZEN],
            ['label_en' => 'Citizen', 'label_bn' => 'নাগরিক']
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh();
    }

    protected function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();

        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['label_en' => ucwords(str_replace('_', ' ', $roleName)), 'label_bn' => 'ভূমিকা']
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh();
    }

    // ------------------------------------------------------------ access

    public function test_guests_cannot_reach_the_mode_selection_screen(): void
    {
        $this->get(route('citizen.modes'))->assertRedirect(route('login'));
        $this->post(route('citizen.mode.switch'), ['mode' => 'buyer'])->assertRedirect(route('login'));
    }

    public function test_citizen_can_view_the_mode_selection_screen(): void
    {
        $this->actingAs($this->citizen())
            ->get(route('citizen.modes'))
            ->assertOk()
            ->assertViewIs('citizen.mode-select');
    }

    public static function nonCitizenRoles(): array
    {
        return [
            'admin' => [Role::ADMIN],
            'registry officer' => [Role::REGISTRY_OFFICER],
            'bank officer' => [Role::BANK_OFFICER],
        ];
    }

    /**
     * @dataProvider nonCitizenRoles
     */
    public function test_non_citizen_roles_are_refused_the_mode_screen(string $roleName): void
    {
        $this->actingAs($this->userWithRole($roleName))
            ->get(route('citizen.modes'))
            ->assertForbidden();
    }

    // ------------------------------------------------------- mode switching

    public function test_a_citizen_can_switch_to_buyer_and_back_to_seller(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)->post(route('citizen.mode.switch'), ['mode' => 'buyer'])
            ->assertRedirect();

        $this->assertSame('buyer', session('citizen.mode'));

        $this->actingAs($citizen)->post(route('citizen.mode.switch'), ['mode' => 'seller'])
            ->assertRedirect();

        $this->assertSame('seller', session('citizen.mode'));
    }

    public function test_switching_modes_does_not_change_the_users_roles(): void
    {
        $citizen = $this->citizen();

        $before = $citizen->roles()->pluck('name')->sort()->values()->all();
        $countBefore = \Illuminate\Support\Facades\DB::table('role_user')->count();

        $this->actingAs($citizen)->post(route('citizen.mode.switch'), ['mode' => 'buyer']);
        $this->actingAs($citizen)->post(route('citizen.mode.switch'), ['mode' => 'seller']);

        $after = $citizen->fresh()->roles()->pluck('name')->sort()->values()->all();

        $this->assertSame($before, $after, 'mode switching must not alter role assignments');
        $this->assertSame(
            $countBefore,
            \Illuminate\Support\Facades\DB::table('role_user')->count(),
            'mode switching must not write to role_user'
        );
        $this->assertTrue($citizen->fresh()->hasRole(Role::CITIZEN));
        $this->assertFalse($citizen->fresh()->hasRole(Role::ADMIN));
    }

    public function test_an_invalid_mode_is_rejected(): void
    {
        $this->actingAs($this->citizen())
            ->post(route('citizen.mode.switch'), ['mode' => 'admin'])
            ->assertSessionHasErrors('mode');

        $this->actingAs($this->citizen())
            ->post(route('citizen.mode.switch'), ['mode' => 'registry_officer'])
            ->assertSessionHasErrors('mode');
    }

    /**
     * The mode value is a session hint, so a tampered value must not unlock
     * anything. Setting it directly to an arbitrary string changes no access.
     */
    public function test_a_tampered_mode_value_grants_no_additional_access(): void
    {
        $citizen = $this->citizen();

        $this->withSession(['citizen.mode' => 'admin'])
            ->actingAs($citizen)
            ->get(route('citizen.modes'))
            ->assertOk();

        // Still refused the privileged dashboards.
        $this->actingAs($citizen)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($citizen)->get(route('registry.dashboard'))->assertForbidden();
        $this->actingAs($citizen)->get(route('bank.dashboard'))->assertForbidden();
    }

    public function test_both_modes_routes_are_reachable_by_the_same_account(): void
    {
        $citizen = $this->citizen();

        // Seller-mode routes.
        $this->actingAs($citizen)->get(route('citizen.land.index'))->assertOk();
        $this->actingAs($citizen)->get(route('citizen.listings.index'))->assertOk();

        // Buyer-mode routes.
        $this->actingAs($citizen)->get(route('citizen.find'))->assertOk();
        $this->actingAs($citizen)->get(route('citizen.verification.index'))->assertOk();
    }

    // ------------------------------------------------------- localisation

    public function test_mode_screen_is_translated_in_both_locales(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)
            ->get(route('citizen.modes'))
            ->assertOk()
            ->assertSee('Landowner / Seller')
            ->assertSee('Land Buyer');

        $this->post(route('locale.switch'), ['locale' => 'bn']);

        $this->actingAs($citizen)
            ->get(route('citizen.modes'))
            ->assertOk()
            ->assertSee('জমির মালিক / বিক্রেতা', false)
            ->assertSee('জমি ক্রেতা', false);
    }
}
