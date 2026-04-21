<?php

namespace App\Http\Controllers;

use App\Models\AssessmentScope;
use App\Models\AssessmentScopeGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'identified_scope'   => ['nullable', 'in:PCI,DMZ,Internal'],
            'environment'        => ['nullable', 'in:PROD,UAT,STAGE'],
            'location'           => ['nullable', 'in:DC,DR,Cloud'],
            'notes'              => ['nullable', 'string', 'max:1000'],
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
            'identified_scope'   => ['nullable', 'in:PCI,DMZ,Internal'],
            'environment'        => ['nullable', 'in:PROD,UAT,STAGE'],
            'location'           => ['nullable', 'in:DC,DR,Cloud'],
            'notes'              => ['nullable', 'string', 'max:1000'],
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
            'rows'                      => ['required', 'array', 'min:1', 'max:2000'],
            'rows.*.ip_address'         => ['nullable', 'string', 'max:45'],
            'rows.*.hostname'           => ['nullable', 'string', 'max:255'],
            'rows.*.system_name'        => ['nullable', 'string', 'max:255'],
            'rows.*.system_criticality' => ['nullable', 'integer', 'between:1,5'],
            'rows.*.system_owner'       => ['nullable', 'string', 'max:100'],
            'rows.*.identified_scope'   => ['nullable', 'in:PCI,DMZ,Internal'],
            'rows.*.environment'        => ['nullable', 'in:PROD,UAT,STAGE'],
            'rows.*.location'           => ['nullable', 'in:DC,DR,Cloud'],
            'rows.*.notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $now    = now();
        $userId = Auth::id();

        $rows = array_map(fn ($row) => [
            'group_id'           => $assessmentScopeGroup->id,
            'ip_address'         => $row['ip_address']         ?? null,
            'hostname'           => $row['hostname']           ?? null,
            'system_name'        => $row['system_name']        ?? null,
            'system_criticality' => isset($row['system_criticality']) ? (int) $row['system_criticality'] : null,
            'system_owner'       => $row['system_owner']       ?? null,
            'identified_scope'   => $row['identified_scope']   ?? null,
            'environment'        => $row['environment']        ?? null,
            'location'           => $row['location']           ?? null,
            'notes'              => $row['notes']              ?? null,
            'created_by'         => $userId,
            'created_at'         => $now,
            'updated_at'         => $now,
        ], $request->rows);

        foreach (array_chunk($rows, 200) as $chunk) {
            AssessmentScope::insert($chunk);
        }

        return response()->json(['imported' => count($rows)]);
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
            ->get(['ip_address','hostname','system_name','system_criticality',
                   'system_owner','identified_scope','environment','location','notes']);

        return response()->json($rows);
    }
}
