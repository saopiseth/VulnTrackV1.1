<?php

namespace App\Http\Controllers;

use App\Models\ThreatIntelItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ThreatIntelController extends Controller
{
    public function index(Request $request)
    {
        // Subquery: count matched tracked findings by CVE ID
        $matchSub = DB::table('vuln_tracked')
            ->selectRaw('COUNT(*)')
            ->whereNotNull('cve')
            ->whereColumn('cve', 'threat_intel_items.cve_id');

        $query = ThreatIntelItem::selectRaw('threat_intel_items.*')
            ->selectSub($matchSub, 'matched_count')
            ->with('creator');

        if ($request->filled('type'))     $query->where('type', $request->type);
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('severity')) $query->where('severity', $request->severity);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title',            'like', "%$s%")
                  ->orWhere('cve_id',          'like', "%$s%")
                  ->orWhere('description',      'like', "%$s%")
                  ->orWhere('affected_products','like', "%$s%")
                  ->orWhere('source',           'like', "%$s%");
            });
        }

        $items = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Summary stats — cached 5 min, bust on any write
        $stats = Cache::store('file')->remember('threat_intel.stats', 300, function () {
            return [
                'total'         => ThreatIntelItem::count(),
                'active'        => ThreatIntelItem::where('status', 'Active')->count(),
                'critical_high' => ThreatIntelItem::whereIn('severity', ['Critical', 'High'])->count(),
                'matched'       => ThreatIntelItem::whereNotNull('cve_id')
                                        ->whereExists(fn ($q) => $q->from('vuln_tracked')
                                            ->whereNotNull('cve')
                                            ->whereColumn('cve', 'threat_intel_items.cve_id'))
                                        ->count(),
                'mitigated'     => ThreatIntelItem::whereIn('status', ['Mitigated', 'Archived'])->count(),
            ];
        });

        // Type distribution — cached 5 min
        $typeCounts = Cache::store('file')->remember('threat_intel.typeCounts', 300, function () {
            return ThreatIntelItem::selectRaw('type, COUNT(*) as cnt')
                ->groupBy('type')
                ->pluck('cnt', 'type');
        });

        // AJAX: return only the table rows + pagination HTML
        if ($request->ajax()) {
            return response()->json([
                'html'  => view('threat_intel._items', [
                    'items'      => $items,
                    'allStatuses'=> ThreatIntelItem::statuses(),
                ])->render(),
                'links' => $items->links()->toHtml(),
                'total' => $items->total(),
            ]);
        }

        return view('threat_intel.index', compact('items', 'stats', 'typeCounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'type'              => ['required', 'in:CVE,Advisory,IOC,Exploit,Campaign'],
            'cve_id'            => ['nullable', 'string', 'max:30'],
            'cvss_score'        => ['nullable', 'numeric', 'min:0', 'max:10'],
            'severity'          => ['required', 'in:Critical,High,Medium,Low,Info'],
            'description'       => ['nullable', 'string'],
            'affected_products' => ['nullable', 'string', 'max:1000'],
            'source'            => ['nullable', 'string', 'max:100'],
            'source_url'        => ['nullable', 'url', 'max:500'],
            'published_at'      => ['nullable', 'date'],
            'status'            => ['required', 'in:Active,Monitoring,Mitigated,Archived'],
            'tags'              => ['nullable', 'string', 'max:300'],
            'ioc_type'          => ['nullable', 'in:IP,Domain,Hash,URL'],
            'ioc_value'         => ['nullable', 'string', 'max:512'],
        ]);

        if (!empty($data['cve_id'])) {
            $data['cve_id'] = strtoupper(trim($data['cve_id']));
        }

        $data['tags'] = !empty($data['tags'])
            ? array_values(array_filter(array_map('trim', explode(',', $data['tags']))))
            : null;

        $data['created_by'] = Auth::id();

        ThreatIntelItem::create($data);
        $this->bustCache();

        return back()->with('success', 'Threat intel item added successfully.');
    }

    public function updateStatus(Request $request, ThreatIntelItem $item)
    {
        $data = $request->validate([
            'status' => ['required', 'in:Active,Monitoring,Mitigated,Archived'],
        ]);

        $item->update($data);
        $this->bustCache();

        return back()->with('success', "Status updated to {$data['status']}.");
    }

    public function destroy(ThreatIntelItem $item)
    {
        $this->authorize('delete', $item);

        $item->delete();
        $this->bustCache();

        return back()->with('success', 'Intel item deleted.');
    }

    public function import(Request $request)
    {
        $this->authorize('import', ThreatIntelItem::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:json,csv,txt', 'max:4096'],
        ]);

        $file    = $request->file('file');
        $ext     = strtolower($file->getClientOriginalExtension());
        $content = file_get_contents($file->getRealPath());

        $rows = match ($ext) {
            'json'  => $this->parseJson($content),
            'csv'   => $this->parseCsv($content),
            default => [],
        };

        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            if (empty($row['title'])) { $skipped++; continue; }

            $severity = in_array($row['severity'] ?? '', ThreatIntelItem::severities())
                ? $row['severity'] : 'Medium';
            $type     = in_array($row['type'] ?? '', ThreatIntelItem::types())
                ? $row['type'] : 'CVE';
            $status   = in_array($row['status'] ?? '', ThreatIntelItem::statuses())
                ? $row['status'] : 'Active';
            $iocType  = in_array($row['ioc_type'] ?? '', ThreatIntelItem::iocTypes())
                ? $row['ioc_type'] : null;

            $tags = null;
            if (!empty($row['tags'])) {
                $tags = is_array($row['tags'])
                    ? $row['tags']
                    : array_values(array_filter(array_map('trim', explode(',', $row['tags']))));
            }

            $cvss = null;
            if (isset($row['cvss_score']) && is_numeric($row['cvss_score'])) {
                $cvss = min(10.0, max(0.0, (float) $row['cvss_score']));
            }

            ThreatIntelItem::create([
                'title'             => substr(trim($row['title']), 0, 255),
                'type'              => $type,
                'cve_id'            => !empty($row['cve_id']) ? strtoupper(trim($row['cve_id'])) : null,
                'cvss_score'        => $cvss,
                'severity'          => $severity,
                'description'       => $row['description'] ?? null,
                'affected_products' => $row['affected_products'] ?? null,
                'source'            => $row['source'] ?? null,
                'source_url'        => $row['source_url'] ?? null,
                'published_at'      => !empty($row['published_at']) ? $row['published_at'] : null,
                'status'            => $status,
                'tags'              => $tags,
                'ioc_type'          => $iocType,
                'ioc_value'         => $row['ioc_value'] ?? null,
                'created_by'        => Auth::id(),
            ]);

            $imported++;
        }

        $msg = "Imported {$imported} item(s)";
        if ($skipped) $msg .= ", skipped {$skipped} invalid row(s)";

        $this->bustCache();

        return back()->with('success', $msg . '.');
    }

    // ── Private helpers ────────────────────────────────────────

    private function bustCache(): void
    {
        Cache::store('file')->forget('threat_intel.stats');
        Cache::store('file')->forget('threat_intel.typeCounts');
    }

    private function parseJson(string $content): array
    {
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) return [];
        if (isset($decoded[0]))       return $decoded;
        if (isset($decoded['items'])) return $decoded['items'];
        return [];
    }

    private function parseCsv(string $content): array
    {
        $lines = explode("\n", trim($content));
        if (count($lines) < 2) return [];

        $headers = array_map('trim', str_getcsv(array_shift($lines)));
        $rows    = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $values = str_getcsv($line);
            if (count($values) !== count($headers)) continue;
            $rows[] = array_combine($headers, $values);
        }

        return $rows;
    }
}
