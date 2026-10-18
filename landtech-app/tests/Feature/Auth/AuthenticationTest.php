<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        // Phase 4: a role-less account is not silently sent to the public demo
        // dashboard; it lands on the honest pending page instead.
        $response->assertRedirect(route('account.pending', absolute: false));
    }

    public function test_a_citizen_authenticates_and_lands_on_the_citizen_dashboard(): void
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::query()->firstOrCreate(
            ['name' => \App\Models\Role::CITIZEN],
            ['label_en' => 'Citizen', 'label_bn' => 'নাগরিক']
        );
        $user->roles()->syncWithoutDetaching([$role->id]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('citizen.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
