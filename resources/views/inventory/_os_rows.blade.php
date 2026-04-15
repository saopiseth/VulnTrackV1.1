@forelse($hosts as $host)
@php
    $effectiveOs  = $host->os_override ?? $host->os_name;
    $effectiveFam = $host->os_override_family ?? $host->os_family;
    $meta         = $familyMeta[$effectiveFam] ?? $familyMeta['Other'];
    $critLevels   = \App\Models\VulnHostOs::criticalityLevels();
    $crit         = $host->asset_criticality;
    $critMeta     = $crit ? ($critLevels[$crit] ?? null) : null;

    // Kernel display
    switch ($effectiveFam) {
        case 'Linux': case 'Unix':
            $kMeta  = ['label'=>'kernel','icon'=>'bi-terminal','bg'=>'#d1fae5','color'=>'#065f46'];
            $kValue = $host->os_kernel;
            break;
        case 'Windows':
            $kMeta  = ['label'=>'build','icon'=>'bi-windows','bg'=>'#dbeafe','color'=>'#1e40af'];
            $kValue = preg_replace('/^build\s+/i', '', $host->os_kernel);
            break;
        default:
            $kMeta  = null;
            $kValue = $host->os_kernel;
    }

    // Encode host data for shared modals
    $hostData = json_encode([
        'id'              => $host->id,
        'ip'              => $host->ip_address,
        'hostname'        => $host->hostname ?? '',
        'os_name'         => $host->os_name ?? '',
        'os_override'     => $host->os_override ?? '',
        'os_override_fam' => $host->os_override_family ?? '',
        'os_override_note'=> $host->os_override_note ?? '',
        'os_confidence'   => $host->os_confidence,
        'os_family'       => $effectiveFam,
        'has_override'    => $host->hasOverride(),
        'criticality'     => $crit,
        'system_name'     => $host->system_name ?? '',
        'system_owner'    => $host->system_owner ?? '',
        'crit_at'         => $host->criticality_set_at ? $host->criticality_set_at->format('d M Y') : '',
        'os_history'      => $host->os_history ?? [],
    ]);
@endphp
<tr data-host='{{ $hostData }}'>
    <td style="padding:.65rem 1rem;font-family:monospace;font-weight:700;color:#0f172a;vertical-align:middle">
        {{ $host->ip_address }}
    </td>
    <td style="padding:.65rem .75rem;color:#64748b;vertical-align:middle">
        {{ $host->hostname ?? '—' }}
    </td>
    <td style="padding:.65rem .75rem;vertical-align:middle">
        @if($effectiveOs)
            <span>{{ $effectiveOs }}</span>
            @if($host->hasOverride())
                <span class="override-badge ms-1"><i class="bi bi-pencil-fill"></i> Override</span>
            @endif
            @if($host->os_history && count($host->os_history) > 0)
                <span style="font-size:.68rem;color:#94a3b8;margin-left:.3rem">
                    <i class="bi bi-clock-history"></i> {{ count($host->os_history) }}
                </span>
            @endif
        @else
            <span style="color:#94a3b8">Not detected</span>
        @endif
    </td>
    <td style="padding:.65rem .75rem;vertical-align:middle">
        <span class="os-badge" style="background:{{ $meta['bg'] }};color:{{ $meta['color'] }}">
            <i class="bi {{ $meta['icon'] }}"></i>{{ $effectiveFam }}
        </span>
    </td>
    <td style="padding:.65rem .75rem;vertical-align:middle">
        @if($host->os_kernel)
        @if($kMeta)
        <span style="font-size:.62rem;font-weight:700;background:{{ $kMeta['bg'] }};color:{{ $kMeta['color'] }};
              padding:.05rem .35rem;border-radius:4px;margin-right:.25rem;
              text-transform:uppercase;letter-spacing:.4px;white-space:nowrap">
            <i class="bi {{ $kMeta['icon'] }}" style="font-size:.6rem"></i> {{ $kMeta['label'] }}
        </span>
        @endif
        <span style="font-family:monospace;font-size:.75rem;color:#374151"
              title="{{ $host->os_kernel }}">{{ $kValue }}</span>
        @else
        <span style="color:#cbd5e1;font-size:.75rem">—</span>
        @endif
    </td>
    <td style="padding:.65rem .75rem;vertical-align:middle">
        <button type="button" class="btn btn-sm os-override-btn"
            style="font-size:.72rem;padding:.2rem .55rem;border-radius:7px;background:#f8fafc;border:1px solid #e2e8f0;color:#374151"
            title="Set manual OS override"><i class="bi bi-pencil"></i>
        </button>
        <button type="button" class="btn btn-sm ms-1 os-crit-btn"
            style="font-size:.72rem;padding:.2rem .55rem;border-radius:7px;
                   background:{{ $critMeta ? $critMeta['bg'] : '#f0fdf4' }};
                   border:1px solid {{ $critMeta ? $critMeta['bg'] : '#bbf7d0' }};
                   color:{{ $critMeta ? $critMeta['color'] : '#15803d' }}"
            title="Set Asset Criticality"><i class="bi bi-shield-check"></i>
        </button>
        <a href="{{ route('inventory.os-assets.apps', $host) }}"
            class="btn btn-sm ms-1"
            style="font-size:.72rem;padding:.2rem .55rem;border-radius:7px;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d"
            title="View installed applications"><i class="bi bi-grid-3x3-gap"></i>
        </a>
        @if($host->os_history && count($host->os_history))
        <button type="button" class="btn btn-sm ms-1 os-history-btn"
            style="font-size:.72rem;padding:.2rem .55rem;border-radius:7px;background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b"
            title="View OS history"><i class="bi bi-clock-history"></i>
        </button>
        @endif
    </td>
</tr>
@empty
<tr>
    <td colspan="6" style="padding:3rem;text-align:center;color:#94a3b8">
        <i class="bi bi-cpu" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
        No OS asset data found.
    </td>
</tr>
@endforelse
