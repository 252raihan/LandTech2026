<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\Demo\DemoMode;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public Welcome / Login landing page (project root `/`).
 *
 * This page is PUBLIC and must remain safe to render for anyone, including an
 * anonymous visitor. It therefore exposes:
 *   - no records of any kind,
 *   - no credentials, tokens or secrets,
 *   - no role information about any account.
 *
 * The only dynamic data it computes is which panels exist and whether a
 * development demo account happens to be seeded for them. That is a
 * configuration fact, not user data.
 */
class WelcomeController extends Controller
{
    /**
     * Render the landing page.
     */
    public function __invoke(Request $request): View
    {
        $demoMode = DemoMode::enabled();

        // Human-readable role labels for any missing-account notice. Uses the
        // bilingual label columns so nothing raw is shown to the user.
        $labelColumn = app()->getLocale() === 'bn' ? 'label_bn' : 'label_en';
        $labels = Role::query()->pluck($labelColumn, 'name');

        $missing = [];

        if ($demoMode) {
            foreach (array_keys(DemoMode::implementedPanels()) as $roleName) {
                if (DemoMode::accountFor($roleName) === null) {
                    $missing[] = $labels[$roleName] ?? $roleName;
                }
            }
        }

        return view('welcome', [
            'demoMode' => $demoMode,
            'panels' => DemoMode::panels(),
            'missingDemoAccounts' => $missing,
        ]);
    }
}
