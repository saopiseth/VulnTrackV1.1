<?php

namespace App\Http\Controllers;

use App\Models\AssessmentScope;
use App\Models\AssessmentScopeGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssessmentScopeController extends Controller
{
    // ─── Groups ──────────────────────────────────────────────────

    public function index()
    {
        $groups = AssessmentScopeGroup::withCount('items')
            ->with('creator')
            ->latest()
            ->get();

        return view('assessment_scope.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['created_by'] = Auth::id();
        AssessmentScopeGroup::create($data);

        return back()->with('success', 'Scope group created.');
    }

    public function show(AssessmentScopeGroup $assessmentScopeGroup)
    {
        $group = $assessmentScopeGroup->load('creator');

        $items = AssessmentScope::where('group_id', $group->id)
            ->orderBy('identified_scope')
            ->orderBy('ip_address')
            ->paginate(50);

        $stats = [
            'total'    => AssessmentScope::where('group_id', $group->id)->count(),
            'by_scope' => AssessmentScope::where('group_id', $group->id)
                            ->selectRaw('identified_scope, count(*) as total')
                            ->whereNotNull('identified_scope')
                            ->groupBy('identified_scope')
                            ->pluck('total', 'identified_scope'),
        ];

        return view('assessment_scope.show', [
            'group'   => $group,
            'items'   => $items,
            'stats'   => $stats,
            'levels'  => AssessmentScope::criticalityLevels(),
            'scopes'  => AssessmentScope::scopeOptions(),
            'envs'    => AssessmentScope::environmentOptions(),
            'locs'    => AssessmentScope::locationOptions(),
        ]);
    }

    public function update(Request $request, AssessmentScopeGroup $assessmentScopeGroup)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $assessmentScopeGroup->update($data);

        return back()->with('success', 'Scope group updated.');
    }

    public function destroy(AssessmentScopeGroup $assessmentScopeGroup)
    {
        $assessmentScopeGroup->delete();
        return redirect()->route('assessment-scope.index')->with('success', 'Scope group deleted.');
    }

    // ─── Items ───────────────────────────────────────────────────

    public function storeItem(Request $request, AssessmentScopeGroup $assessmentScopeGroup)
    {
        $data = $request->validate([
            'ip_address'         => ['nullable', 'ip'],
            'hostname'           => ['nullable', 'string', 'max:255'],
            'system_name'        => ['nullable', 'string', 'max:255'],
            'system_criticality' => ['nullable', 'integer', 'between:1,5'],
            'system_owner'       => ['nullable', 'string', 'max:100'],
            'identified_scope'   => ['nullable', 'in:PCI,DMZ,Internal,External,Swift,Non-Bank,Public,Critical,Less Critical'],
            'environment'        => ['nullable', 'in:PROD,UAT,STAGE,DR,DEV,Non-Prod,DEV-QA'],
            'location'           => ['nullable', 'in:DC,DR,Cloud'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'remediation_sla'    => ['nullable', 'in:Priority Level 1,Priority Level 2,Priority Level 3,Priority Level 4'],
        ]);

        $data['group_id']   = $assessmentScopeGroup->id;
        $data['created_by'] = Auth::id();
        AssessmentScope::create($data);

        return back()->with('success', 'Entry added.');
    }

    public function updateItem(Request $request, AssessmentScopeGroup $assessmentScopeGroup, AssessmentScope $item)
    {
        abort_if($item->group_id !== $assessmentScopeGroup->id, 403);

        $data = $request->validate([
            'ip_address'         => ['nullable', 'ip'],
            'hostname'           => ['nullable', 'string', 'max:255'],
            'system_name'        => ['nullable', 'string', 'max:255'],
            'system_criticality' => ['nullable', 'integer', 'between:1,5'],
            'system_owner'       => ['nullable', 'string', 'max:100'],
            'identified_scope'   => ['nullable', 'in:PCI,DMZ,Internal,External,Swift,Non-Bank,Public,Critical,Less Critical'],
            'environment'        => ['nullable', 'in:PROD,UAT,STAGE,DR,DEV,Non-Prod,DEV-QA'],
            'location'           => ['nullable', 'in:DC,DR,Cloud'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'remediation_sla'    => ['nullable', 'in:Priority Level 1,Priority Level 2,Priority Level 3,Priority Level 4'],
        ]);

        $item->update($data);

        return back()->with('success', 'Entry updated.');
    }

    public function destroyItem(AssessmentScopeGroup $assessmentScopeGroup, AssessmentScope $item)
    {
        abort_if($item->group_id !== $assessmentScopeGroup->id, 403);
        $item->delete();
        return back()->with('success', 'Entry deleted.');
    }

    // ─── Import ──────────────────────────────────────────────────

    public function importBatch(Request $request, AssessmentScopeGroup $assessmentScopeGroup)
    {
        $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:2000'],
        ]);

        // Enum lookup maps: case-insensitive key → canonical value
        $scopeLookup = collect(AssessmentScope::scopeOptions())
            ->mapWithKeys(fn ($v) => [strtolower($v) => $v])->all();
        $envLookup   = collect(AssessmentScope::environmentOptions())
            ->mapWithKeys(fn ($v) => [strtolower($v) => $v])->all();
        $slaLookup   = collect(AssessmentScope::remediationSlaOptions())
            ->mapWithKeys(fn ($v) => [strtolower($v) => $v])->all();

        // Alias map: long/alternate Excel labels → canonical lookup key (lowercase)
        $scopeAliases = [
            'pci scope'            => 'pci',
            'swift scope'          => 'swift',
            'swift asset scope'    => 'swift',
            'public scope'         => 'public',
            'non-bank scope'       => 'non-bank',
            'critical scope'       => 'critical',
            'critical asset scope' => 'critical',
            'less critical scope'  => 'less critical',
        ];

        $now    = now();
        $userId = Auth::id();

        // Normalize any scalar (string OR number) from Excel/CSV parsers
        $str = fn ($v) => is_scalar($v) && $v !== null && $v !== '' && trim((string) $v) !== ''
            ? mb_substr(trim((string) $v), 0, 255) : null;

        // Safe enum resolver: returns canonical value or null; logs unrecognised values
        $enum = function (string $field, $v, array $lookup, array $aliases = []) use ($assessmentScopeGroup): ?string {
            if ($v === null || $v === '') return null;
            $key = strtolower(trim((string) $v));
            if ($key === '') return null;
            if (isset($aliases[$key])) $key = $aliases[$key];
            if (isset($lookup[$key])) return $lookup[$key];
            Log::warning("Assessment scope import: unrecognised {$field} value", [
                'group_id' => $assessmentScopeGroup->id,
                'value'    => $v,
                'accepted' => implode(', ', array_values($lookup)),
            ]);
            return null; // reject invalid enum; caller may count as warning
        };

        // Deduplicate within file: last-wins keyed by ip_address, then hostname
        $byIp       = [];  // ip      => data
        $byHostname = [];  // hn      => data (no ip)
        $ambiguous  = [];  // neither ip nor hostname
        $skipped    = 0;
        $warnings   = [];

        foreach ($request->input('rows', []) as $rowNum => $row) {
            $hostname = $str($row['hostname']     ?? null);
            $ip       = $str($row['ip_address']   ?? null);
            $sysName  = $str($row['system_name']  ?? null);
            $owner    = $str($row['system_owner'] ?? null);

            // Enum fields — track when a value was provided but not recognised
            $rawScope = $row['identified_scope'] ?? null;
            $rawEnv   = $row['environment']      ?? null;
            $rawSla   = $row['remediation_sla']  ?? null;

            $scope = $enum('identified_scope', $rawScope, $scopeLookup, $scopeAliases);
            $env   = $enum('environment',      $rawEnv,   $envLookup);
            $sla   = $enum('remediation_sla',  $rawSla,   $slaLookup);

            // Collect user-facing warnings for values that were provided but rejected
            $lineNum = $rowNum + 2; // +2: 1-based + header row
            if ($rawScope !== null && $rawScope !== '' && $scope === null) {
                $warnings[] = "Row {$lineNum}: identified_scope \"{$rawScope}\" not recognised (accepted: PCI, DMZ, Internal, External, Swift, Non-Bank, Public, Critical, Less Critical).";
            }
            if ($rawEnv !== null && $rawEnv !== '' && $env === null) {
                $warnings[] = "Row {$lineNum}: environment \"{$rawEnv}\" not recognised (accepted: PROD, UAT, STAGE, DR, DEV, Non-Prod, DEV-QA).";
            }
            if ($rawSla !== null && $rawSla !== '' && $sla === null) {
                $warnings[] = "Row {$lineNum}: remediation_sla \"{$rawSla}\" not recognised (accepted: Priority Level 1/2/3/4).";
            }

            // Skip completely empty rows
            if (!$hostname && !$ip && !$scope && !$sysName && !$env && !$owner && !$sla) {
                $skipped++;
                continue;
            }

            $data = [
                'group_id'           => $assessmentScopeGroup->id,
                'hostname'           => $hostname,
                'ip_address'         => $ip,
                'identified_scope'   => $scope,
                'system_name'        => $sysName,
                'environment'        => $env,
                'system_owner'       => $owner,
                'remediation_sla'    => $sla,
                'system_criticality' => null,
                'location'           => null,
                'notes'              => null,
                'created_by'         => $userId,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];

            if ($ip)           $byIp[$ip]           = $data;
            elseif ($hostname) $byHostname[$hostname] = $data;
            else               $ambiguous[]           = $data;
        }

        if (empty($byIp) && empty($byHostname) && empty($ambiguous)) {
            return response()->json(['error' => 'No valid rows to import.'], 422);
        }

        // Look up existing records to route each row to insert or update
        $existingByIp = !empty($byIp)
            ? DB::table('assessment_scopes')
                ->where('group_id', $assessmentScopeGroup->id)
                ->whereIn('ip_address', array_keys($byIp))
                ->pluck('id', 'ip_address')
            : collect();

        $existingByHostname = !empty($byHostname)
            ? DB::table('assessment_scopes')
                ->where('group_id', $assessmentScopeGroup->id)
                ->whereNull('ip_address')
                ->whereIn('hostname', array_keys($byHostname))
                ->pluck('id', 'hostname')
            : collect();

        $toInsert = [];
        $toUpdate = [];  // id => data

        foreach ($byIp       as $ip => $data) { $id = $existingByIp->get($ip);       $id ? $toUpdate[$id] = $data : $toInsert[] = $data; }
        foreach ($byHostname as $hn => $data) { $id = $existingByHostname->get($hn); $id ? $toUpdate[$id] = $data : $toInsert[] = $data; }
        foreach ($ambiguous  as $data)         { $toInsert[] = $data; }

        // ── Inserts (bulk; fall back to per-row on failure) ──────────────
        $insertedCount = 0;
        $failedCount   = 0;

        foreach (array_chunk($toInsert, 500) as $chunk) {
            try {
                AssessmentScope::insert($chunk);
                $insertedCount += count($chunk);
            } catch (\Exception $e) {
                Log::error('Assessment scope import: bulk insert failed — retrying per-row', [
                    'group_id'   => $assessmentScopeGroup->id,
                    'chunk_size' => count($chunk),
                    'error'      => $e->getMessage(),
                ]);
                // Retry individually so one bad row doesn't drop the whole chunk
                foreach ($chunk as $row) {
                    try {
                        AssessmentScope::insert([$row]);
                        $insertedCount++;
                    } catch (\Exception $e2) {
                        Log::error('Assessment scope import: row insert failed', [
                            'group_id' => $assessmentScopeGroup->id,
                            'ip'       => $row['ip_address'] ?? null,
                            'hostname' => $row['hostname']   ?? null,
                            'error'    => $e2->getMessage(),
                        ]);
                        $failedCount++;
                    }
                }
            }
        }

        // ── Updates (per-row; only overwrite non-null fields) ────────────
        $updatedCount = 0;
        $updateFields = ['hostname', 'ip_address', 'identified_scope', 'system_name',
                         'environment', 'system_owner', 'remediation_sla'];

        foreach ($toUpdate as $id => $data) {
            try {
                $payload = [];
                foreach ($updateFields as $f) {
                    if (array_key_exists($f, $data) && $data[$f] !== null) {
                        $payload[$f] = $data[$f];
                    }
                }
                if (!empty($payload)) {
                    $payload['updated_at'] = $now;
                    DB::table('assessment_scopes')->where('id', $id)->update($payload);
                }
                $updatedCount++;
            } catch (\Exception $e) {
                Log::error('Assessment scope import: row update failed', [
                    'group_id'  => $assessmentScopeGroup->id,
                    'record_id' => $id,
                    'error'     => $e->getMessage(),
                ]);
                $failedCount++;
            }
        }

        return response()->json([
            'total'    => $insertedCount + $updatedCount + $failedCount,
            'inserted' => $insertedCount,
            'updated'  => $updatedCount,
            'failed'   => $failedCount,
            'skipped'  => $skipped,
            'warnings' => array_slice($warnings, 0, 20), // cap to avoid huge payloads
        ]);
    }

    // ─── Items JSON (for create-assessment preview) ──────────────

    public function itemsJson(AssessmentScopeGroup $assessmentScopeGroup)
    {
        $levels = AssessmentScope::criticalityLevels();

        $items = AssessmentScope::where('group_id', $assessmentScopeGroup->id)
            ->orderBy('identified_scope')
            ->orderBy('ip_address')
            ->get(['ip_address', 'hostname', 'system_name', 'system_criticality',
                   'system_owner', 'identified_scope', 'environment', 'location'])
            ->map(function ($item) use ($levels) {
                $crit = $levels[$item->system_criticality] ?? null;
                return [
                    'ip_address'        => $item->ip_address,
                    'hostname'          => $item->hostname,
                    'system_name'       => $item->system_name,
                    'system_criticality'=> $item->system_criticality,
                    'criticality_label' => $crit['label'] ?? null,
                    'criticality_bg'    => $crit['bg']    ?? null,
                    'criticality_color' => $crit['color'] ?? null,
                    'system_owner'      => $item->system_owner,
                    'identified_scope'  => $item->identified_scope,
                    'environment'       => $item->environment,
                    'location'          => $item->location,
                ];
            });

        return response()->json($items);
    }

    // ─── Export ──────────────────────────────────────────────────

    public function export(AssessmentScopeGroup $assessmentScopeGroup)
    {
        $rows = AssessmentScope::where('group_id', $assessmentScopeGroup->id)
            ->orderBy('identified_scope')
            ->orderBy('ip_address')
            ->get(['hostname','ip_address','identified_scope','system_name',
                   'environment','system_owner','remediation_sla']);

        return response()->json($rows);
    }
}
