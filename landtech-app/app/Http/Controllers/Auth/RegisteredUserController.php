<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * SECURITY (Phase 4 / H3)
     * -----------------------
     * Public registration grants EXACTLY ONE role, `citizen`, and only on the
     * server side. The request is never allowed to influence that:
     *   - the validated field list is explicit (`name`, `email`, `password`), so
     *     `role`, `role_id`, `is_admin`, `roles[]` and any similar field posted
     *     by the client are simply discarded by `validate()`;
     *   - `User::$fillable` excludes every privilege column;
     *   - the role is resolved by name from the roles table here, never from
     *     request input.
     *
     * There is deliberately no public path to a privileged role — privileged
     * grants are an administrative action only.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $this->assignDefaultRole($user);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('citizen.dashboard', absolute: false));
    }

    /**
     * Grant the single default self-service role.
     *
     * If the roles table has not been seeded the grant is skipped rather than
     * failing the registration: the account is then role-less and lands on the
     * honest `account.pending` page instead of a broken dashboard.
     */
    protected function assignDefaultRole(User $user): void
    {
        $roleId = Role::query()->where('name', Role::CITIZEN)->value('id');

        if ($roleId !== null) {
            $user->roles()->attach($roleId);
        }
    }
}
