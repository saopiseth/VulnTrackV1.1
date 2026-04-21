<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgentToken
{
    /**
     * Authenticate inbound agent API requests.
     *
     * Protocol:
     *   - Agent sends `Authorization: Bearer <raw_token>` header.
     *   - Middleware hashes the token with SHA-256 and looks up the agent.
     *   - The raw token is NEVER stored; only the hash lives in `agents.api_token`.
     *   - On success the resolved Agent model is attached to the request attributes
     *     so controllers can retrieve it via `$request->attributes->get('agent')`.
     *
     * Optional UUID cross-check:
     *   - If the request body contains a `uuid` field it MUST match the token owner.
     *   - This prevents one compromised agent from submitting data for another.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawToken = $request->bearerToken();

        if (empty($rawToken)) {
            return $this->unauthorized('Authentication required. Provide an Authorization: Bearer <token> header.');
        }

        $agent = Agent::where('api_token', hash('sha256', $rawToken))->first();

        if (!$agent) {
            return $this->unauthorized('Invalid or expired agent token.');
        }

        // Cross-check UUID when present in the payload
        $payloadUuid = $request->input('uuid');
        if ($payloadUuid !== null && $payloadUuid !== $agent->uuid) {
            return response()->json([
                'success' => false,
                'message' => 'UUID mismatch: payload UUID does not match the authenticated agent.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Make the agent available throughout the request lifecycle
        $request->attributes->set('agent', $agent);

        return $next($request);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
