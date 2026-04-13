<?php

namespace App\Http\Controllers;

use App\Models\ProjectAssessment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjectAssessmentController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectAssessment::with('creator')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('assessment_type', 'like', "%$s%")
                  ->orWhere('project_coordinator', 'like', "%$s%")
                  ->orWhere('assessor', 'like', "%$s%")
                  ->orWhere('bcd_id', 'like', "%$s%");
            });
        }

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('priority')) $query->where('priority', $request->priority);

        $assessments = $query->paginate(15)->withQueryString();

        $stats = ProjectAssessment::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed
        ")->first();

        return view('assessments.index', compact('assessments', 'stats'));
    }

    public function create()
    {
        $criteria = ProjectAssessment::criteria();
        $statuses = ProjectAssessment::criteriaStatuses();
        return view('assessments.create', compact('criteria', 'statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(array_merge([
            'assessment_type'     => ['required', 'string', 'max:255'],
            'project_kickoff'     => ['nullable', 'date'],
            'due_date'            => ['nullable', 'date'],
            'complete_date'       => ['nullable', 'date'],
            'project_coordinator' => ['nullable', 'string', 'max:255'],
            'priority'            => ['required', 'in:Critical,High,Medium,Low'],
            'bcd_id'              => ['nullable', 'string', 'max:100'],
            'status'              => ['required', 'in:Open,In Progress,Closed'],
            'bcd_url'             => ['nullable', 'url', 'max:500'],
            'comments'            => ['nullable', 'string'],
        ], $this->evidenceValidationRules()));

        $this->processCriteriaData($request, $data);

        $data['created_by'] = Auth::id();
        $data['assessor']   = Auth::user()->name;

        ProjectAssessment::create($data);

        return redirect()->route('assessments.index')
            ->with('success', 'Project assessment created successfully.');
    }

    public function show(ProjectAssessment $assessment)
    {
        $criteria = ProjectAssessment::criteria();
        $statuses = ProjectAssessment::criteriaStatuses();
        return view('assessments.show', compact('assessment', 'criteria', 'statuses'));
    }

    public function edit(ProjectAssessment $assessment)
    {
        $this->authorize('update', $assessment);

        $criteria = ProjectAssessment::criteria();
        $statuses = ProjectAssessment::criteriaStatuses();
        return view('assessments.edit', compact('assessment', 'criteria', 'statuses'));
    }

    public function update(Request $request, ProjectAssessment $assessment)
    {
        $this->authorize('update', $assessment);

        $data = $request->validate(array_merge([
            'assessment_type'     => ['required', 'string', 'max:255'],
            'project_kickoff'     => ['nullable', 'date'],
            'due_date'            => ['nullable', 'date'],
            'complete_date'       => ['nullable', 'date'],
            'project_coordinator' => ['nullable', 'string', 'max:255'],
            'priority'            => ['required', 'in:Critical,High,Medium,Low'],
            'bcd_id'              => ['nullable', 'string', 'max:100'],
            'status'              => ['required', 'in:Open,In Progress,Closed'],
            'bcd_url'             => ['nullable', 'url', 'max:500'],
            'comments'            => ['nullable', 'string'],
        ], $this->evidenceValidationRules()));

        $this->processCriteriaData($request, $data, $assessment);

        $assessment->update($data);

        return redirect()->route('assessments.index')
            ->with('success', 'Project assessment updated successfully.');
    }

    public function report(ProjectAssessment $assessment)
    {
        $pdf = Pdf::loadView('assessments.report', compact('assessment'))
            ->setPaper('A4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'Security-Assessment-Report_'
            . preg_replace('/[^A-Za-z0-9\-]/', '-', $assessment->assessment_type)
            . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function destroy(ProjectAssessment $assessment)
    {
        $this->authorize('delete', $assessment);

        foreach (ProjectAssessment::criteriaFields() as $field) {
            if ($assessment->{"{$field}_evidence"}) {
                Storage::disk('public')->delete($assessment->{"{$field}_evidence"});
            }
        }

        $assessment->delete();

        return redirect()->route('assessments.index')
            ->with('success', 'Project assessment deleted.');
    }

    private function evidenceValidationRules(): array
    {
        $rules = [];
        $statuses = implode(',', ProjectAssessment::criteriaStatuses());
        foreach (ProjectAssessment::criteriaFields() as $field) {
            $rules["{$field}_evidence"] = ['nullable', 'file', 'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip'];
            $rules["{$field}_status"] = ['nullable', "in:{$statuses}"];
        }
        return $rules;
    }

    private function processCriteriaData(Request $request, array &$data, ?ProjectAssessment $existing = null): void
    {
        foreach (ProjectAssessment::criteriaFields() as $field) {
            $data[$field] = $request->input($field, '0') === '1';
            $data["{$field}_status"] = $request->input("{$field}_status", 'Not Started');

            if ($request->hasFile("{$field}_evidence")) {
                if ($existing && $existing->{"{$field}_evidence"}) {
                    Storage::disk('public')->delete($existing->{"{$field}_evidence"});
                }
                $data["{$field}_evidence"] = $request->file("{$field}_evidence")
                    ->store('evidence', 'public');
            } else {
                unset($data["{$field}_evidence"]);
            }
        }
    }
}
