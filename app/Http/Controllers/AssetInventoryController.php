<?php

namespace App\Http\Controllers;

use App\Models\AssetInventory;
use App\Models\AssessmentScope;
use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use App\Models\VulnScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetInventoryController extends Controller
{
    public function index(Request $request): View
    {
        AssetInventory::syncFromScopes();

        $latestScan = VulnScan::where('upload_status', 'completed')
            ->latest('updated_at')
            ->first();

        $assets = $this->filteredAssetQuery($request)
            ->orderByRaw('CASE WHEN last_scanned_at IS NULL THEN 1 ELSE 0 END, last_scanned_at DESC, ip_address ASC')
            ->paginate(25)
            ->withQueryString();

        return view('asset_inventory.index', [
            'assets'        => $assets,
            'latestScan'    => $latestScan,
            'scopeOptions'  => AssessmentScope::scopeOptions(),
            'envOptions'    => AssessmentScope::environmentOptions(),
            'slaOptions'    => AssessmentScope::remediationSlaOptions(),
            'statusOptions' => [
                AssetInventory::STATUS_ACTIVE,
                AssetInventory::STATUS_NOT_FOUND,
                AssetInventory::STATUS_DECOMMISSIONED,
            ],
        ]);
    }

    public function update(Request $request, AssetInventory $assetInventory)
    {
        $data = $request->validate([
            'system_name'      => ['nullable', 'string', 'max:255'],
            'identified_scope' => ['nullable', 'in:' . implode(',', AssessmentScope::scopeOptions())],
            'environment'      => ['nullable', 'in:' . implode(',', AssessmentScope::environmentOptions())],
            'system_owner'     => ['nullable', 'string', 'max:100'],
            'remediation_sla'  => ['nullable', 'in:' . implode(',', AssessmentScope::remediationSlaOptions())],
        ]);

        $assetInventory->update($data);

        return response()->json(['success' => true, 'asset' => $assetInventory->fresh()]);
    }

    public function syncFromScope(Request $request)
    {
        $groupId = $request->input('group_id');
        abort_if(!$groupId, 422);

        $businessFields = ['system_name', 'identified_scope', 'environment', 'system_owner', 'remediation_sla'];

        $scopes = DB::table('assessment_scopes')
            ->where('group_id', $groupId)
            ->where(fn ($q) => $q->whereNotNull('ip_address')->orWhereNotNull('hostname'))
            ->get(array_merge(['ip_address', 'hostname'], $businessFields));

        $synced = 0;
        $now    = now();

        foreach ($scopes as $scope) {
            $payload = [];
            foreach ($businessFields as $f) {
                if ($scope->$f !== null && $scope->$f !== '') {
                    $payload[$f] = $scope->$f;
                }
            }
            if (empty($payload)) continue;

            $payload['updated_at'] = $now;

            // Match by IP first, fall back to hostname
            if ($scope->ip_address) {
                $rows = DB::table('asset_inventories')->where('ip_address', $scope->ip_address)->update($payload);
            } else {
                $rows = DB::table('asset_inventories')->where('hostname', $scope->hostname)->update($payload);
            }

            $synced += $rows;
        }

        return response()->json(['synced' => $synced]);
    }

    public function exportExcel(Request $request)
    {
        abort_unless(class_exists(\ZipArchive::class), 500, 'Excel export requires the PHP zip extension.');

        $assets = $this->filteredAssetQuery($request)
            ->orderByDesc('last_scanned_at')
            ->get();

        $path = storage_path('app/asset-inventory-report-' . uniqid() . '.xlsx');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $this->buildAssetInventoryXlsx($path, $assets, $request->only(['search', 'scope', 'env', 'status', 'os_family']));

        $filename = 'asset_inventory_report_' . now()->format('Ymd_His') . '.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ])->deleteFileAfterSend(true);
    }

    public function show(AssetInventory $assetInventory): View
    {
        $findings = VulnFinding::where('ip_address', $assetInventory->ip_address)
            ->with('assessment:id,name')
            ->orderByRaw("FIELD(severity,'Critical','High','Medium','Low','Info')")
            ->orderByDesc('cvss_score')
            ->limit(100)
            ->get();

        $assessments = $findings
            ->pluck('assessment')
            ->filter()
            ->unique('id')
            ->values();

        $osHistory = VulnHostOs::where('ip_address', $assetInventory->ip_address)
            ->with('assessment:id,name')
            ->orderByDesc('updated_at')
            ->get();

        return view('asset_inventory.show', compact(
            'assetInventory', 'findings', 'assessments', 'osHistory'
        ));
    }

    private function filteredAssetQuery(Request $request)
    {
        $query = AssetInventory::query();

        if ($s = $request->search) {
            $query->where(fn($q) => $q
                ->where('ip_address', 'like', "%{$s}%")
                ->orWhere('hostname',  'like', "%{$s}%")
                ->orWhere('os',        'like', "%{$s}%")
                ->orWhere('os_kernel', 'like', "%{$s}%")
            );
        }

        if ($v = $request->scope) {
            $query->where('identified_scope', $v);
        }
        if ($v = $request->env) {
            $query->where('environment', $v);
        }
        if ($v = $request->status) {
            $query->where('status', $v);
        }
        if ($v = $request->os_family) {
            $query->where('os_family', 'like', "%{$v}%");
        }

        return $query;
    }

    private function buildAssetInventoryXlsx(string $path, $assets, array $filters): void
    {
        $headers = [
            'No.', 'IP Address', 'Hostname', 'System Name', 'Operating System', 'OS Family',
            'Kernel / Build', 'Scope', 'Environment', 'Classification Level', 'Critical Level',
            'Critical Vulns', 'High Vulns', 'Medium Vulns', 'Low Vulns', 'Open Ports',
            'Status', 'Last Scanned', 'Notes',
        ];

        $rows = [
            ['Asset Inventory Report'],
            ['Generated', now()->format('Y-m-d H:i:s')],
            ['Filters', $this->assetExportFilterSummary($filters)],
            [],
            $headers,
        ];

        foreach ($assets as $i => $asset) {
            $rows[] = [
                $i + 1,
                $asset->ip_address,
                $asset->hostname,
                $asset->system_name,
                $asset->os,
                $asset->os_family,
                $asset->os_kernel,
                $asset->identified_scope,
                $asset->environment,
                $asset->classification_level,
                $asset->critical_level,
                (int) $asset->vuln_critical,
                (int) $asset->vuln_high,
                (int) $asset->vuln_medium,
                (int) $asset->vuln_low,
                $asset->open_ports,
                $asset->status,
                $asset->last_scanned_at?->format('Y-m-d H:i'),
                $asset->notes,
            ];
        }

        if ($assets->isEmpty()) {
            $rows[] = ['No assets found for the selected filters.'];
        }

        $zip = new \ZipArchive();
        abort_unless($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true, 500, 'Unable to create Excel export.');

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('docProps/app.xml', $this->xlsxAppXml());
        $zip->addFromString('docProps/core.xml', $this->xlsxCoreXml('Asset Inventory Report'));
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSheetXml($rows));

        abort_unless($zip->close(), 500, 'Unable to finalize Excel export.');
    }

    private function assetExportFilterSummary(array $filters): string
    {
        $active = array_filter($filters, fn($value) => filled($value));
        if (!$active) {
            return 'All assets';
        }

        return collect($active)
            ->map(fn($value, $key) => ucfirst(str_replace('_', ' ', $key)) . '=' . $value)
            ->implode('; ');
    }

    private function xlsxSheetXml(array $rows): string
    {
        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $cellXml = '';
            foreach (array_values($row) as $colIndex => $value) {
                $cellXml .= $this->xlsxCell($colIndex + 1, $rowIndex + 1, $value, $rowIndex);
            }
            $sheetRows .= '<row r="' . ($rowIndex + 1) . '">' . $cellXml . '</row>';
        }

        $cols = '';
        $widths = [8, 18, 22, 24, 36, 16, 24, 16, 16, 20, 18, 15, 14, 16, 13, 30, 16, 20, 42];
        foreach ($widths as $i => $width) {
            $col = $i + 1;
            $cols .= '<col min="' . $col . '" max="' . $col . '" width="' . $width . '" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols><sheetData>' . $sheetRows . '</sheetData>'
            . '<autoFilter ref="A5:S' . max(5, count($rows)) . '"/>'
            . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            . '</worksheet>';
    }

    private function xlsxCell(int $col, int $row, mixed $value, int $rowIndex): string
    {
        $ref = $this->xlsxColumnName($col) . $row;
        $style = match (true) {
            $rowIndex === 0 => 1,
            $rowIndex === 4 => 2,
            $col >= 12 && $col <= 15 && $rowIndex > 4 => 3,
            default => 0,
        };

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
        }

        $value = (string) ($value ?? '');
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            $value = "'" . $value;
        }

        return '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . $this->xlsxXml($value) . '</t></is></c>';
    }

    private function xlsxColumnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function xlsxXml(string $value): string
    {
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function xlsxWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Asset Inventory" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function xlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="3"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="16"/><color rgb="FF0F172A"/><name val="Arial"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Arial"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFCBD5E1"/></left><right style="thin"><color rgb="FFCBD5E1"/></right><top style="thin"><color rgb="FFCBD5E1"/></top><bottom style="thin"><color rgb="FFCBD5E1"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment horizontal="right"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function xlsxAppXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>VulnTrack</Application></Properties>';
    }

    private function xlsxCoreXml(string $title): string
    {
        $now = now()->toAtomString();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>' . $this->xlsxXml($title) . '</dc:title><dc:creator>VulnTrack</dc:creator><cp:lastModifiedBy>VulnTrack</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>';
    }
}
