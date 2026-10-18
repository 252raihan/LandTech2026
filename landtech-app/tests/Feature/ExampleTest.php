<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * The root URL now serves the public Welcome / Login landing page.
     *
     * Phase 4 changed this from a redirect to /dashboard: guests must land on
     * the welcome page, and the public demo dashboard must stay reachable at its
     * own unchanged URL.
     */
    public function test_the_root_serves_the_welcome_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('welcome');

        $this->get('/dashboard')->assertStatus(200);
    }
}
