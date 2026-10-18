<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller.
 *
 * `AuthorizesRequests` is required for `$this->authorize(...)` inside
 * controllers. It is applied here rather than per-controller so every Phase 5
 * controller enforces its policy checks through the same mechanism.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
