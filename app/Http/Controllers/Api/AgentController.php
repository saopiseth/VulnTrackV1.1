<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\AssetsRequest;
use App\Http\Requests\Agent\HeartbeatRequest;
use App\Http\Requests\Agent\RegisterAgentRequest;
use App\Http\Requests\Agent\SoftwareRequest;
use App\Models\Agent;
use App\Models\AgentHardwareSnapshot;
use App\Models\AgentLog;
use App\Models\InstalledSoftware;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    // ── 1. Register ───────────────────────────────────────────────

    /**
     * POST /api/agent/register
     *
     * Registers a new agent or re-registers an existing one.
     * A fresh Bearer token is returned and MUST be stored securely by the agent.
     * Only the SHA-256 hash of the token is stored server-side.
     *
     * Re-registration invalidates the previous token — useful for key rotation.
     */
    public function register(RegisterAgentRequest $request): JsonResponse
    {
        $rawToken = Str::random(40);

        $agent = Agent::updateOrCreate(
            ['uuid' => $request->uuid],
            [
                'hostname'   => $request->hostname,
                'ip_address' => $request->ip_address,
                'os'         => $request->os,
                'status'     => 'online',
                'last_seen'  => now(),
                'api_token'  => hash('sha256', $rawToken),
            ]
        );

        AgentLog::record(
            $agent,
            AgentLog::EVENT_REGISTER,
            "Registered from {$request->ip_address} (hostname: {$request->hostname})"
        );

        $statusCode = $agent->wasRecentlyCreated ? 201 : 200;
        $message    = $agent->wasRecentlyCreated ? 'Agent registered successfully.' : 'Agent re-registered. Previous token has been revoked.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => [
                'agent_id' => $agent->id,
                'uuid'     => $agent->uuid,
                'token'    => $rawToken,  // Returned once — store it securely on the agent
            ],
        ], $statusCode);
    }

    // ── 2. Heartbeat ──────────────────────────────────────────────

    /**
     * POST /api/agent/heartbeat
     *
     * Updates the agent's last_seen timestamp and marks it online.
     * Optionally updates the IP address (agents on DHCP may change IPs).
     *
     * Agents should call this every 1–5 minutes.
     * A scheduled job can mark agents offline if last_seen > threshold.
     */
    public function heartbeat(HeartbeatRequest $request): JsonResponse
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');

        $updates = [
            'status'    => 'online',
            'last_seen' => now(),
        ];

        if ($request->filled('ip_address')) {
            $updates['ip_address'] = $request->ip_address;
        }

        $agent->update($updates);

        // Heartbeat logs are high-volume — only record them in debug/verbose mode
        // in production. For now we skip creating a log row to keep the table lean.
        // Uncomment to enable heartbeat audit trail:
        // AgentLog::record($agent, AgentLog::EVENT_HEARTBEAT);

        return response()->json([
            'success'   => true,
            'message'   => 'Heartbeat received.',
            'timestamp' => now()->toISOString(),
        ]);
    }

    // ── 3. Hardware Assets ────────────────────────────────────────

    /**
     * POST /api/agent/assets
     *
     * Records a hardware snapshot for the agent.
     * Every call creates a NEW row — history is preserved intentionally.
     * The latest snapshot can be retrieved via Agent::latestSnapshot().
     *
     * Agents should call this on boot and periodically (e.g. every hour).
     */
    public function assets(AssetsRequest $request): JsonResponse
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');
        $now   = now();

        $snapshot = AgentHardwareSnapshot::create([
            'agent_id'     => $agent->id,
            'cpu'          => $request->cpu,
            'ram'          => $request->ram,
            'disk'         => $request->disk,
            'os_version'   => $request->os_version,
            'collected_at' => $now,
        ]);

        $agent->update(['status' => 'online', 'last_seen' => $now]);

        AgentLog::record(
            $agent,
            AgentLog::EVENT_UPDATE,
            sprintf(
                'Hardware snapshot — CPU: %s | RAM: %sMB | Disk: %sGB | OS: %s',
                $request->cpu    ?? 'unknown',
                $request->ram    ?? '?',
                $request->disk   ?? '?',
                $request->os_version ?? 'unknown'
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'Hardware snapshot recorded.',
            'data'    => [
                'snapshot_id'  => $snapshot->id,
                'collected_at' => $snapshot->collected_at->toISOString(),
            ],
        ], 201);
    }

    // ── 4. Software Inventory ─────────────────────────────────────

    /**
     * POST /api/agent/software
     *
     * Synchronises the agent's complete software list using a three-phase approach:
     *
     *   Phase 1 — Remove entries no longer present (software was uninstalled).
     *   Phase 2 — Insert new entries not yet tracked.
     *   Phase 3 — Bump collected_at on unchanged entries to confirm they're still present.
     *
     * The unique constraint on (agent_id, name, version) acts as a safety net
     * against duplicates that might be introduced by concurrent requests.
     *
     * Agents should call this after every software discovery scan.
     */
    public function software(SoftwareRequest $request): JsonResponse
    {
        /** @var Agent $agent */
        $agent        = $request->attributes->get('agent');
        $softwareList = $request->input('software', []);
        $now          = now();

        [$inserted, $removed, $unchanged] = DB::transaction(function () use ($agent, $softwareList, $now) {

            // ── Normalise & deduplicate the incoming list ────────────
            $incoming = collect($softwareList)
                ->map(fn ($s) => [
                    'name'         => mb_substr(trim($s['name']), 0, 500),
                    'version'      => mb_substr(trim($s['version']), 0, 100),
                    'installed_at' => !empty($s['installed_at'])
                                        ? Carbon::parse($s['installed_at'])
                                        : null,
                ])
                ->unique(fn ($s) => mb_strtolower($s['name']) . '||' . $s['version'])
                ->values();

            // Key: lower(name)||version → value: software row
            $existing = InstalledSoftware::where('agent_id', $agent->id)
                ->select('id', 'name', 'version')
                ->get()
                ->keyBy(fn ($sw) => mb_strtolower($sw->name) . '||' . $sw->version);

            // Build a lookup of incoming keys for O(1) membership checks
            $incomingKeyFlip = $incoming
                ->map(fn ($s) => mb_strtolower($s['name']) . '||' . $s['version'])
                ->flip();

            // ── Phase 1: Delete stale entries ────────────────────────
            $idsToDelete = $existing
                ->filter(fn ($sw) => ! $incomingKeyFlip->has(
                    mb_strtolower($sw->name) . '||' . $sw->version
                ))
                ->pluck('id');

            $removed = $idsToDelete->count();
            if ($removed > 0) {
                InstalledSoftware::whereIn('id', $idsToDelete)->delete();
            }

            // ── Phase 2: Insert new entries (not yet in DB) ──────────
            $toInsert = $incoming->filter(fn ($s) =>
                ! $existing->has(mb_strtolower($s['name']) . '||' . $s['version'])
            );

            $inserted = $toInsert->count();
            if ($inserted > 0) {
                // Chunk to stay within MySQL's max_allowed_packet
                foreach ($toInsert->chunk(200) as $chunk) {
                    InstalledSoftware::insert(
                        $chunk->map(fn ($s) => [
                            'agent_id'     => $agent->id,
                            'name'         => $s['name'],
                            'version'      => $s['version'],
                            'installed_at' => $s['installed_at']
                                                ? $s['installed_at']->format('Y-m-d H:i:s')
                                                : null,
                            'collected_at' => $now->format('Y-m-d H:i:s'),
                            'created_at'   => $now->format('Y-m-d H:i:s'),
                            'updated_at'   => $now->format('Y-m-d H:i:s'),
                        ])->values()->all()
                    );
                }
            }

            // ── Phase 3: Refresh collected_at on unchanged entries ───
            $idsToTouch = $existing
                ->filter(fn ($sw) => $incomingKeyFlip->has(
                    mb_strtolower($sw->name) . '||' . $sw->version
                ))
                ->pluck('id');

            $unchanged = $idsToTouch->count();
            if ($unchanged > 0) {
                InstalledSoftware::whereIn('id', $idsToTouch)
                    ->update([
                        'collected_at' => $now,
                        'updated_at'   => $now,
                    ]);
            }

            return [$inserted, $removed, $unchanged];
        });

        $agent->update(['status' => 'online', 'last_seen' => $now]);

        AgentLog::record(
            $agent,
            AgentLog::EVENT_UPDATE,
            "Software sync — total: {$incoming->count()}, inserted: {$inserted}, removed: {$removed}, unchanged: {$unchanged}"
        );

        $total = InstalledSoftware::where('agent_id', $agent->id)->count();

        return response()->json([
            'success' => true,
            'message' => 'Software inventory synced successfully.',
            'data'    => [
                'total_on_agent' => count($softwareList),
                'inserted'       => $inserted,
                'removed'        => $removed,
                'unchanged'      => $unchanged,
                'total_in_db'    => $total,
            ],
        ]);
    }
}
