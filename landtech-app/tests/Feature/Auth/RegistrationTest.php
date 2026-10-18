<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // Registration resolves the `citizen` role by name, as RoleSeeder creates
        // it. Phase 4 grants it server-side; the request cannot influence this.
        \App\Models\Role::query()->firstOrCreate(
            ['name' => \App\Models\Role::CITIZEN],
            ['label_en' => 'Citizen', 'label_bn' => 'নাগরিক']
        );

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        // A self-registered account is a citizen and lands on the citizen panel.
        $response->assertRedirect(route('citizen.dashboard', absolute: false));
    }

    public function test_registration_attempts_within_the_rate_limit_are_validated_normally(): void
    {
        // An invalid payload keeps the session a guest while still exercising
        // the route end-to-end, so these requests reach validation instead of
        // being short-circuited by the guest redirect.
        foreach (range(1, 4) as $attempt) {
            $this->post('/register', [
                'name' => 'Within Limit',
                'email' => 'not-an-email',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertStatus(302)->assertSessionHasErrors('email');
        }
    }

    public function test_fifth_registration_attempt_is_not_blocked_and_the_sixth_is_rate_limited(): void
    {
        $payload = [
            'name' => 'Beyond Limit',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        // The fifth attempt consumes the last slot: it must still be processed
        // by the normal validation rules, not rejected by the limiter.
        foreach (range(1, 5) as $attempt) {
            $this->post('/register', $payload)
                ->assertStatus(302)
                ->assertSessionHasErrors('email');
        }

        // The sixth attempt within the same minute is blocked by throttle:5,1.
        $this->post('/register', $payload)->assertStatus(429);
    }

    public function test_registration_without_the_citizen_role_seeded_does_not_escalate(): void
    {
        // Roles table is empty: the account must be created role-less and land
        // on the honest pending page, never on a privileged area.
        \App\Models\Role::query()->delete();

        $response = $this->post('/register', [
            'name' => 'Unseeded User',
            'email' => 'unseeded@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $user = \App\Models\User::where('email', 'unseeded@example.com')->firstOrFail();

        $this->assertSame(0, $user->roles()->count());

        // Registration redirects to the citizen panel; the guest-middleware
        // landing resolution is what sends a role-less account to pending.
        $response->assertRedirect(route('citizen.dashboard', absolute: false));
    }
}
