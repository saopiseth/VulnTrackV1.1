<?php

namespace App\Http\Controllers;

use App\Models\SlaPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SlaPolicyController extends Controller
{
    private function adminOnly(): void
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);
    }

    public function index(Request $request)
    {
        $this->adminOnly();

        $policies = SlaPolicy::withCount('assessments')
            ->with('creator')
            ->when($request->filled('search'), fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('sla_policies.index', compact('policies'));
    }

    public function create()
    {
        $this->adminOnly();
        return view('sla_policies.create');
    }

    public function store(Request $request)
    {
        $this->adminOnly();

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120', 'unique:sla_policies,name'],
            'description'   => ['nullable', 'string', 'max:500'],
            'critical_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'high_days'     => ['required', 'integer', 'min:1', 'max:3650'],
            'medium_days'   => ['required', 'integer', 'min:1', 'max:3650'],
            'low_days'      => ['required', 'integer', 'min:1', 'max:3650'],
            'is_default'    => ['boolean'],
        ]);

        $data['created_by'] = Auth::id();
        $data['is_default'] = $request->boolean('is_default');

        DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                SlaPolicy::where('is_default', true)->update(['is_default' => false]);
            }
            SlaPolicy::create($data);
        });

        return redirect()->route('sla-policies.index')
            ->with('success', 'SLA policy "' . $data['name'] . '" created.');
    }

    public function edit(SlaPolicy $slaPolicy)
    {
        $this->adminOnly();
        return view('sla_policies.edit', ['policy' => $slaPolicy]);
    }

    public function update(Request $request, SlaPolicy $slaPolicy)
    {
        $this->adminOnly();

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120', 'unique:sla_policies,name,' . $slaPolicy->id],
            'description'   => ['nullable', 'string', 'max:500'],
            'critical_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'high_days'     => ['required', 'integer', 'min:1', 'max:3650'],
            'medium_days'   => ['required', 'integer', 'min:1', 'max:3650'],
            'low_days'      => ['required', 'integer', 'min:1', 'max:3650'],
            'is_default'    => ['boolean'],
        ]);

        $data['is_default'] = $request->boolean('is_default');

        DB::transaction(function () use ($slaPolicy, $data) {
            if ($data['is_default']) {
                SlaPolicy::where('is_default', true)
                    ->where('id', '!=', $slaPolicy->id)
                    ->update(['is_default' => false]);
            }
            $slaPolicy->update($data);
        });

        return redirect()->route('sla-policies.index')
            ->with('success', 'SLA policy "' . $slaPolicy->name . '" updated.');
    }

    public function destroy(SlaPolicy $slaPolicy)
    {
        $this->adminOnly();
        $name = $slaPolicy->name;
        $slaPolicy->delete();
        return redirect()->route('sla-policies.index')
            ->with('success', 'SLA policy "' . $name . '" deleted.');
    }
}
