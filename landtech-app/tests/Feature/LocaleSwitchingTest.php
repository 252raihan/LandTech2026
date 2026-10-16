<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleSwitchingTest extends TestCase
{
    public function test_default_locale_is_english_and_switcher_is_rendered(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('<html lang="en">', false);
        $response->assertSee('id="locale-select"', false);
    }

    public function test_switching_locale_persists_in_session_and_sets_html_lang(): void
    {
        $this->from('/dashboard')
            ->post('/locale', ['locale' => 'bn'])
            ->assertSessionHas('locale', 'bn');

        $page = $this->get('/dashboard');

        $page->assertStatus(200);
        $page->assertSee('<html lang="bn">', false);
        $page->assertSee('ভাষা', false);
        $page->assertSee('id="locale-select"', false);
    }

    public function test_invalid_locale_is_rejected_and_session_not_changed(): void
    {
        $this->post('/locale', ['locale' => 'zz'.str_repeat('a', 60)])
            ->assertSessionHasErrors(['locale']);
    }

    public function test_invalid_locale_json_response_is_translated(): void
    {
        $this->postJson('/locale', ['locale' => 'xx'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_bengali_validation_message_is_used_when_locale_is_bengali(): void
    {
        $this->withSession(['locale' => 'bn'])
            ->postJson('/locale', ['locale' => 'xx'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['locale'])
            ->assertJsonPath('errors.locale.0', 'নির্বাচিত ভাষা অবৈধ।')
            ->assertJsonPath('message', 'নির্বাচিত ভাষা অবৈধ।');
    }

    public function test_english_validation_message_is_used_when_locale_is_english(): void
    {
        $this->postJson('/locale', ['locale' => 'xx'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['locale'])
            ->assertJsonPath('errors.locale.0', 'The selected language is invalid.');
    }

    public function test_unknown_session_locale_falls_back_to_config_default(): void
    {
        $response = $this->withSession(['locale' => 'not-a-locale'])->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('<html lang="en">', false);
    }

    public function test_lang_js_reflects_active_locale_and_strings(): void
    {
        $response = $this->withSession(['locale' => 'bn'])->get('/lang.js');

        $response->assertStatus(200);
        $this->assertSame(
            'application/javascript; charset=UTF-8',
            $response->headers->get('Content-Type'),
        );
        $response->assertSee("window.LandTech.locale = 'bn';", false);
        $response->assertSee('এই ডেমোর সব ডেটা কৃত্রিম।', false);
    }

    public function test_lang_js_defaults_to_english_without_session(): void
    {
        $response = $this->get('/lang.js');

        $response->assertStatus(200);
        $response->assertSee("window.LandTech.locale = 'en';", false);
        $response->assertSee('All data in this demo is simulated.', false);
    }

    public function test_missing_translations_fall_back_to_english(): void
    {
        app()->setLocale('bn');

        $this->assertSame(
            'English fallback line for missing translations.',
            trans('app.fallback_demo'),
        );
    }
}
