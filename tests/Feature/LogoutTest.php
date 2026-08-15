<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Bug report: "logout button doesn't work" on the operator workspaces
 * (admin/vendor/employee/syndicate). Root cause was in
 * resources/js/workspace-shell.js's logout() function: it only POSTed to
 * the real logout endpoint (`/api/auth/logout`) when a bearer token was
 * present in localStorage. Those four workspaces authenticate via Sanctum's
 * session-cookie guard, not a bearer token, so `token` was null and the
 * endpoint was never actually called for them — the client redirected to
 * /login?logout=1 (a special case in routes/web.php that renders the login
 * page without bouncing back into the dashboard even if still
 * authenticated — itself a sign this had already been noticed and worked
 * around rather than fixed), while the server-side session stayed alive.
 * Fixed by always calling the endpoint regardless of whether a token
 * exists. This test proves the endpoint the fix now unconditionally calls
 * actually destroys a session-authenticated user's session — the contract
 * the frontend fix depends on.
 */
test('POST /api/auth/logout invalidates a session-authenticated user\'s web session', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user);
    expect(Auth::guard('web')->check())->toBeTrue();

    $this->postJson('/api/auth/logout')->assertOk();

    expect(Auth::guard('web')->check())->toBeFalse();
});

test('POST /api/auth/logout revokes the presented token and leaves api user unauthenticated', function () {
    $user = User::factory()->create();
    $plainTextToken = $user->createToken('logout-regression')->plainTextToken;

    $this->withToken($plainTextToken)->postJson('/api/auth/logout')->assertOk();

    expect(PersonalAccessToken::findToken($plainTextToken))->toBeNull();
    Auth::forgetGuards();
    $this->withToken($plainTextToken)->getJson('/api/user')->assertUnauthorized();
});
