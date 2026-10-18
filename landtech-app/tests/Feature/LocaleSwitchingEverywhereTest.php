<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the locale switcher works on every page (not just auth), that the
 * HTML lang attribute follows the locale, that the choice persists across
 * navigation, and that the JS dictionary is emitted per-locale.
 */
class LocaleSwitchingEverywhereTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function demoPages(): array
    {
        return [
            'dashboard', 'saga', 'sync-webhook', 'crypto-verify', 'plot-lock',
            'ekyc-biometric', 'bank-lien', 'ai-ocr-conflict', 'faraz-calculator',
            'gis-map', 'risk-scoring', 'audit-ledger',
        ];
    }

    public function test_every_demo_page_renders_in_english_by_default(): void
    {
        foreach ($this->demoPages() as $slug) {
            $response = $this->get("/{$slug}");

            $response->assertOk();
            $response->assertSee('lang="en"', false);
        }
    }

    public function test_every_demo_page_renders_in_bengali_after_switching(): void
    {
        $this->post('/locale', ['locale' => 'bn'])->assertRedirect();

        foreach ($this->demoPages() as $slug) {
            $response = $this->get("/{$slug}");

            $response->assertOk();
            $response->assertSee('lang="bn"', false);

            // The sidebar must be in Bengali on every page.
            $response->assertSee('ড্যাশবোর্ড', false);
        }
    }

    public function test_page_title_translates_on_every_demo_page(): void
    {
        $this->post('/locale', ['locale' => 'bn']);

        $this->get('/faraz-calculator')->assertSee('এম৭ · ফরায়েজ অংশ ক্যালকুলেটর', false);
        $this->get('/gis-map')->assertSee('এম৮ · জিআইএস স্থানিক ম্যাপিং স্তর', false);
        $this->get('/risk-scoring')->assertSee('এম৯ · সর্বজনীন ঝুঁকি নির্ণয় ইঞ্জিন', false);
        $this->get('/audit-ledger')->assertSee('এম১০ · অপরিবর্তনীয় অডিট লেজার', false);
        $this->get('/bank-lien')->assertSee('এম৫ · ব্যাংক দায় ও বোঝা নিবন্ধন', false);
    }

    public function test_auth_pages_remain_bilingual(): void
    {
        $this->get('/login')->assertOk()->assertSee('Log in');

        $this->post('/locale', ['locale' => 'bn']);

        $this->get('/login')->assertOk()->assertSee('লগ ইন', false);
        $this->get('/register')->assertOk()->assertSee('নিবন্ধন', false);
        $this->get('/forgot-password')->assertOk()->assertSee('lang="bn"', false);
    }

    public function test_locale_persists_across_navigation(): void
    {
        $this->post('/locale', ['locale' => 'bn']);

        $this->get('/dashboard')->assertSee('lang="bn"', false);
        $this->get('/saga')->assertSee('lang="bn"', false);
        $this->get('/crypto-verify')->assertSee('lang="bn"', false);
    }

    public function test_switching_back_to_english_works(): void
    {
        $this->post('/locale', ['locale' => 'bn']);
        $this->get('/dashboard')->assertSee('lang="bn"', false);

        $this->post('/locale', ['locale' => 'en']);
        $this->get('/dashboard')->assertSee('lang="en"', false);
        $this->get('/dashboard')->assertSee('Command Dashboard');
    }

    public function test_js_dictionary_is_emitted_in_the_active_locale(): void
    {
        $en = $this->get('/lang.js');
        $en->assertOk();
        $this->assertStringContainsString('window.__t', $en->getContent());
        $this->assertStringContainsString('HIGH RISK', $en->getContent());
        // app()->getLocale() is emitted through var_export(), i.e. single-quoted.
        $this->assertMatchesRegularExpression(
            "/window\\.LandTech\\.locale\\s*=\\s*'en'/u",
            $en->getContent()
        );

        $this->post('/locale', ['locale' => 'bn']);

        $bn = $this->get('/lang.js');
        $bn->assertOk();
        $this->assertStringContainsString('উচ্চ ঝুঁকি', $bn->getContent());
        $this->assertMatchesRegularExpression(
            "/window\\.LandTech\\.locale\\s*=\\s*'bn'/u",
            $bn->getContent()
        );
    }

    public function test_js_helper_provides_escaping_and_fallback(): void
    {
        $js = $this->get('/lang.js')->getContent();

        // The escaping helper and the key-echo fallback must both be present.
        $this->assertStringContainsString('window.__esc', $js);
        $this->assertStringContainsString('window.__tHtml', $js);
        $this->assertStringContainsString('return key;', $js);
    }

    public function test_locale_script_is_served_as_javascript(): void
    {
        $response = $this->get('/lang.js');

        $response->assertOk();
        $this->assertStringContainsString('javascript', $response->headers->get('Content-Type'));
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->post('/locale', ['locale' => 'fr'])->assertSessionHasErrors('locale');
    }
}
