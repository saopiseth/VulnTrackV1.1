<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessHardeningVerification;
use App\Models\HardeningAssessment;
use App\Models\HardeningVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HardeningVerificationController extends Controller
{
    public function index()
    {
        $verifications = HardeningVerification::with(['assessment', 'creator'])
            ->latest()
            ->paginate(15);

        return view('hardening.verifications.index', compact('verifications'));
    }

    public function create(Request $request)
    {
        // Pre-select an assessment if passed via query string
        $selectedAssessmentUuid = $request->query('assessment');
        $selectedAssessment     = $selectedAssessmentUuid
            ? HardeningAssessment::where('uuid', $selectedAssessmentUuid)->first()
            : null;

        $assessments = HardeningAssessment::where('upload_status', 'completed')
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'system_name', 'ip_address']);

        return view('hardening.verifications.create', compact('assessments', 'selectedAssessment'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hardening_assessment_uuid' => ['required', 'string', 'exists:hardening_assessments,uuid'],
            'verification_date'         => ['required', 'date'],
            'verified_by'               => ['nullable', 'string', 'max:255'],
            'remarks'                   => ['nullable', 'string'],
            'nessus_file'               => ['required', 'file', 'max:204800', 'mimes:xml,csv,nessus'],
        ]);

        $assessment = HardeningAssessment::where('uuid', $validated['hardening_assessment_uuid'])->firstOrFail();

        $file   = $request->file('nessus_file');
        $ext    = strtolower($file->getClientOriginalExtension());
        $stored = $file->store('hardening/verifications', 'local');

        $verification = HardeningVerification::create([
            'hardening_assessment_id' => $assessment->id,
            'verification_date'       => $validated['verification_date'],
            'verified_by'             => $validated['verified_by'] ?? null,
            'remarks'                 => $validated['remarks'] ?? null,
            'nessus_file_path'        => $stored,
            'nessus_file_name'        => $file->getClientOriginalName(),
            'nessus_file_size'        => $file->getSize(),
            'upload_status'           => 'pending',
            'created_by'              => auth()->id(),
        ]);

        ProcessHardeningVerification::dispatch($verification->id, $stored, $ext);

        return redirect()
            ->route('hardening.verifications.show', $verification)
            ->with('success', 'Verification submitted. Results are being processed.');
    }

    public function show(HardeningVerification $hardeningVerification)
    {
        $verification = $hardeningVerification->load(['assessment', 'creator']);

        $results = $hardeningVerification->results()
            ->with('originalFinding')
            ->orderByRaw("FIELD(verification_status,'Still Open','Not Found in Verification','New Finding','Resolved','Accepted Risk')")
            ->paginate(50);

        return view('hardening.verifications.show', compact('verification', 'results'));
    }

    public function uploadStatus(HardeningVerification $hardeningVerification)
    {
        return response()->json([
            'status'    => $hardeningVerification->upload_status,
            'error'     => $hardeningVerification->upload_error,
            'resolved'  => $hardeningVerification->resolved_count,
            'stillOpen' => $hardeningVerification->still_open_count,
            'newFindings' => $hardeningVerification->new_finding_count,
            'notFound'  => $hardeningVerification->not_found_count,
        ]);
    }

    public function destroy(HardeningVerification $hardeningVerification)
    {
        if ($hardeningVerification->nessus_file_path) {
            Storage::disk('local')->delete($hardeningVerification->nessus_file_path);
        }

        $hardeningVerification->delete();

        return redirect()->route('hardening.verifications.index')
            ->with('success', 'Verification deleted.');
    }
}
