<?php

use App\Http\Controllers\Api\AgentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent API Routes  —  /api/*
|--------------------------------------------------------------------------
|
| All routes here are automatically prefixed with /api by bootstrap/app.php.
| The `api` middleware group applies:
|   - SubstituteBindings
|   - throttle:api  (60 req/min per Bearer token, see AppServiceProvider)
|
| Authentication model:
|   - POST /api/agent/register  → public (no token yet); returns a Bearer token.
|   - All other routes          → require Authorization: Bearer <token>.
|   - `throttle:agent.register` limits registration attempts per IP.
|
*/

// ── Public: Registration ──────────────────────────────────────────────────
// No auth token required — this is how agents obtain their token.
// Rate-limited separately to prevent registration flooding.
Route::post('/agent/register', [AgentController::class, 'register'])
     ->middleware('throttle:agent.register')
     ->name('api.agent.register');

// ── Authenticated: Require valid Agent Bearer token ───────────────────────
Route::middleware('agent.token')->group(function () {

    // Heartbeat — call every 1–5 min to maintain "online" status
    Route::post('/agent/heartbeat', [AgentController::class, 'heartbeat'])
         ->name('api.agent.heartbeat');

    // Hardware snapshot — call on boot and periodically
    Route::post('/agent/assets', [AgentController::class, 'assets'])
         ->name('api.agent.assets');

    // Software sync — call after each discovery scan
    Route::post('/agent/software', [AgentController::class, 'software'])
         ->name('api.agent.software');
});

// ── Fallback: clean 404 for unknown API paths ────────────────────────────
Route::fallback(fn () => response()->json([
    'success' => false,
    'message' => 'API endpoint not found.',
], 404));
