<?php

namespace App\Http\Controllers;

use App\Models\HardeningAssessment;
use App\Models\HardeningVerification;
use Illuminate\Support\Facades\DB;

class HardeningDashboardController extends Controller
{
    public function index()
    {
        $totalAssessments  = HardeningAssessment::where('upload_status', 'completed')->count();
        $totalVerifications= HardeningVerification::where('upload_status', 'completed')->count();

        $totals = HardeningAssessment::where('upload_status', 'completed')
            ->selectRaw('SUM(compliant_count) as compliant, SUM(non_compliant_count) as non_compliant,
                         SUM(partially_compliant_count) as partial, SUM(not_applicable_count) as na')
            ->first();

        $recentAssessments = HardeningAssessment::latest()
            ->take(6)
            ->get();

        return view('hardening.dashboard', [
            'totalAssessments'   => $totalAssessments,
            'totalVerifications' => $totalVerifications,
            'totalCompliant'     => (int) ($totals->compliant    ?? 0),
            'totalNonCompliant'  => (int) ($totals->non_compliant ?? 0),
            'totalPartial'       => (int) ($totals->partial       ?? 0),
            'totalNotApplicable' => (int) ($totals->na            ?? 0),
            'recentAssessments'  => $recentAssessments,
        ]);
    }

    public function standards()
    {
        return view('hardening.standards.index');
    }

    public function reports()
    {
        $assessments = HardeningAssessment::where('upload_status', 'completed')
            ->withCount('verifications')
            ->latest()
            ->paginate(15);

        return view('hardening.reports.index', compact('assessments'));
    }
}
