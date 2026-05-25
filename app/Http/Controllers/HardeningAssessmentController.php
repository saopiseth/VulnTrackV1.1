<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessHardeningUpload;
use App\Models\HardeningAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HardeningAssessmentController extends Controller
{
    public function index()
    {
        $assessments = HardeningAssessment::with('creator')
            ->withCount('verifications')
            ->latest()
            ->paginate(15);

        return view('hardening.assessments.index', compact('assessments'));
    }

    public function create()
    {
        return view('hardening.assessments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'system_name'      => ['required', 'string', 'max:255'],
            'hostname'         => ['nullable', 'string', 'max:255'],
            'ip_address'       => ['required', 'string', 'max:100'],
            'operating_system' => ['nullable', 'string', 'max:255'],
            'environment'      => ['required', 'in:Production,UAT,Development,Internal,DMZ,Cloud'],
            'scope_type'       => ['nullable', 'string', 'max:100'],
            'asset_owner'      => ['nullable', 'string', 'max:255'],
            'system_owner'     => ['nullable', 'string', 'max:255'],
            'criticality_level'=> ['required', 'in:Critical,High,Medium,Low'],
            'assessment_date'  => ['required', 'date'],
            'remarks'          => ['nullable', 'string'],
            'nessus_file'      => ['required', 'file', 'max:204800', 'mimes:xml,csv,nessus'],
        ]);

        $file      = $request->file('nessus_file');
        $ext       = strtolower($file->getClientOriginalExtension());
        $stored    = $file->store('hardening/assessments', 'local');

        $assessment = HardeningAssessment::create([
            'name'              => $validated['name'],
            'system_name'       => $validated['system_name'],
            'hostname'          => $validated['hostname'] ?? null,
            'ip_address'        => $validated['ip_address'],
            'operating_system'  => $validated['operating_system'] ?? null,
            'environment'       => $validated['environment'],
            'scope_type'        => $validated['scope_type'] ?? null,
            'asset_owner'       => $validated['asset_owner'] ?? null,
            'system_owner'      => $validated['system_owner'] ?? null,
            'criticality_level' => $validated['criticality_level'],
            'assessment_date'   => $validated['assessment_date'],
            'remarks'           => $validated['remarks'] ?? null,
            'nessus_file_path'  => $stored,
            'nessus_file_name'  => $file->getClientOriginalName(),
            'nessus_file_size'  => $file->getSize(),
            'upload_status'     => 'pending',
            'created_by'        => auth()->id(),
        ]);

        ProcessHardeningUpload::dispatch($assessment->id, $stored, $ext);

        return redirect()
            ->route('hardening.assessments.show', $assessment)
            ->with('success', 'Assessment created. Nessus file is being processed.');
    }

    public function show(HardeningAssessment $hardeningAssessment)
    {
        $assessment = $hardeningAssessment->load(['creator', 'verifications']);

        $findings = $hardeningAssessment->findings()
            ->orderByRaw("FIELD(compliance_status,'Non-Compliant','Partially Compliant','Compliant','Not Applicable')")
            ->paginate(50);

        return view('hardening.assessments.show', compact('assessment', 'findings'));
    }

    public function uploadStatus(HardeningAssessment $hardeningAssessment)
    {
        return response()->json([
            'status' => $hardeningAssessment->upload_status,
            'error'  => $hardeningAssessment->upload_error,
            'total'  => $hardeningAssessment->total_findings,
        ]);
    }

    public function destroy(HardeningAssessment $hardeningAssessment)
    {
        if ($hardeningAssessment->nessus_file_path) {
            Storage::disk('local')->delete($hardeningAssessment->nessus_file_path);
        }

        $hardeningAssessment->delete();

        return redirect()->route('hardening.assessments.index')
            ->with('success', 'Assessment deleted.');
    }
}
