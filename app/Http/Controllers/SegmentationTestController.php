<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSegmentationScan;
use App\Models\SegmentationTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SegmentationTestController extends Controller
{
    public function index()
    {
        $tests = SegmentationTest::with('creator')
            ->withCount('results')
            ->latest()
            ->paginate(15);

        return view('segmentation.index', compact('tests'));
    }

    public function create()
    {
        return view('segmentation.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'scanner_ip' => ['required', 'ip'],
            'notes'      => ['nullable', 'string', 'max:1000'],
            'scan_file'  => ['required', 'file', 'max:204800', 'mimes:xml,txt,nmap'],
        ]);

        $file   = $request->file('scan_file');
        $ext    = strtolower($file->getClientOriginalExtension());
        $stored = $file->store('segmentation/scans', 'local');

        $test = SegmentationTest::create([
            'name'          => $validated['name'],
            'scanner_ip'    => $validated['scanner_ip'],
            'notes'         => $validated['notes'] ?? null,
            'file_path'     => $stored,
            'file_name'     => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'upload_status' => 'pending',
            'created_by'    => auth()->id(),
        ]);

        ProcessSegmentationScan::dispatch($test->id, $stored, $ext);

        return redirect()
            ->route('segmentation.show', $test)
            ->with('success', 'Segmentation test created. Nmap file is being analysed.');
    }

    public function show(SegmentationTest $segmentationTest)
    {
        $test = $segmentationTest->load('creator');

        $results = $segmentationTest->results()
            ->orderByRaw("FIELD(status,'not_accessible','accessible')")
            ->orderBy('target_subnet')
            ->get();

        $details = $segmentationTest->details()
            ->orderBy('target_subnet')
            ->orderBy('host_ip')
            ->orderBy('port')
            ->get();

        return view('segmentation.show', compact('test', 'results', 'details'));
    }

    public function status(SegmentationTest $segmentationTest)
    {
        return response()->json([
            'status' => $segmentationTest->upload_status,
            'error'  => $segmentationTest->upload_error,
        ]);
    }

    public function destroy(SegmentationTest $segmentationTest)
    {
        if ($segmentationTest->file_path) {
            Storage::disk('local')->delete($segmentationTest->file_path);
        }

        $segmentationTest->delete();

        return redirect()->route('segmentation.index')
            ->with('success', 'Segmentation test deleted.');
    }

    public function exportCsv(SegmentationTest $segmentationTest)
    {
        abort_unless($segmentationTest->upload_status === 'completed', 404);

        $test    = $segmentationTest;
        $details = $segmentationTest->details()
            ->orderBy('target_subnet')->orderBy('host_ip')->orderBy('port')
            ->get();

        $filename = 'segmentation-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($test, $details) {
            $fh = fopen('php://output', 'w');

            fputcsv($fh, [
                'Scanner IP', 'Scanner Subnet', 'Target Subnet', 'Host IP', 'Port', 'Protocol', 'Service',
            ]);

            foreach ($details as $d) {
                fputcsv($fh, [
                    $test->scanner_ip,
                    $test->scanner_subnet,
                    $d->target_subnet,
                    $d->host_ip,
                    $d->port ?? '',
                    $d->protocol ?? '',
                    $d->service ?? '',
                ]);
            }

            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }
}
