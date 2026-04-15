@php use App\Models\ThreatIntelItem; @endphp
@forelse($items as $item)
@php
    $sStyle  = ThreatIntelItem::severityStyle($item->severity);
    $tStyle  = ThreatIntelItem::typeStyle($item->type);
    $stStyle = ThreatIntelItem::statusStyle($item->status);
    $cvssC   = ThreatIntelItem::cvssColor($item->cvss_score);
    $matched = (int) $item->matched_count;
@endphp
<tr
    data-id="{{ $item->id }}"
    data-title="{{ $item->title }}"
    data-type="{{ $item->type }}"
    data-severity="{{ $item->severity }}"
    data-status="{{ $item->status }}"
    data-cve="{{ $item->cve_id ?? '' }}"
    data-cvss="{{ $item->cvss_score ?? '' }}"
    data-cvss-label="{{ ThreatIntelItem::cvssLabel($item->cvss_score) }}"
    data-source="{{ $item->source ?? '' }}"
    data-source-url="{{ $item->source_url ?? '' }}"
    data-published="{{ $item->published_at ? $item->published_at->format('d M Y') : '' }}"
    data-description="{{ $item->description ?? '' }}"
    data-affected="{{ $item->affected_products ?? '' }}"
    data-ioc-type="{{ $item->ioc_type ?? '' }}"
    data-ioc-value="{{ $item->ioc_value ?? '' }}"
    data-tags="{{ $item->tags ? implode(', ', $item->tags) : '' }}"
    data-matched="{{ $matched }}"
    data-creator="{{ $item->creator->name ?? '' }}"
    data-created="{{ $item->created_at->format('d M Y') }}">

    {{-- Severity --}}
    <td style="padding:.6rem 1rem;vertical-align:middle">
        <span class="intel-badge" style="background:{{ $sStyle['bg'] }};color:{{ $sStyle['color'] }}">
            <i class="bi {{ $sStyle['icon'] }}"></i>{{ $item->severity }}
        </span>
    </td>

    {{-- Type --}}
    <td style="padding:.6rem .75rem;vertical-align:middle">
        <span class="intel-badge" style="background:{{ $tStyle['bg'] }};color:{{ $tStyle['color'] }}">
            <i class="bi {{ $tStyle['icon'] }}"></i>{{ $item->type }}
        </span>
    </td>

    {{-- Title / CVE --}}
    <td style="padding:.6rem .75rem;vertical-align:middle;max-width:320px">
        <div style="font-weight:600;color:#0f172a;font-size:.83rem;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
             title="{{ $item->title }}">{{ $item->title }}</div>
        @if($item->cve_id)
        <div style="font-family:monospace;font-size:.72rem;color:#64748b;margin-top:.1rem">
            <i class="bi bi-tag me-1" style="font-size:.65rem"></i>{{ $item->cve_id }}
        </div>
        @endif
        @if($item->tags && count($item->tags))
        <div style="margin-top:.2rem">
            @foreach(array_slice($item->tags, 0, 3) as $tag)
            <span style="background:#f1f5f9;color:#64748b;font-size:.65rem;padding:.05rem .35rem;
                         border-radius:5px;margin-right:.2rem">{{ $tag }}</span>
            @endforeach
            @if(count($item->tags) > 3)
            <span style="font-size:.65rem;color:#94a3b8">+{{ count($item->tags) - 3 }}</span>
            @endif
        </div>
        @endif
    </td>

    {{-- CVSS --}}
    <td style="padding:.6rem .75rem;vertical-align:middle;text-align:center">
        @if(!is_null($item->cvss_score))
        <span class="cvss-pill" style="background:{{ $sStyle['bg'] }};color:{{ $cvssC }}">
            {{ number_format($item->cvss_score, 1) }}
        </span>
        @else
        <span style="color:#cbd5e1;font-size:.78rem">—</span>
        @endif
    </td>

    {{-- Source / Published --}}
    <td style="padding:.6rem .75rem;vertical-align:middle">
        @if($item->source)
        <div style="font-weight:500;color:#374151;font-size:.8rem">{{ $item->source }}</div>
        @endif
        @if($item->published_at)
        <div style="font-size:.72rem;color:#94a3b8;margin-top:.1rem">
            <i class="bi bi-calendar3 me-1" style="font-size:.65rem"></i>{{ $item->published_at->format('d M Y') }}
        </div>
        @endif
        @if(!$item->source && !$item->published_at)
        <span style="color:#cbd5e1;font-size:.78rem">—</span>
        @endif
    </td>

    {{-- Status --}}
    <td style="padding:.6rem .75rem;vertical-align:middle">
        <button type="button" class="intel-badge btn p-0 ti-status-btn"
            style="background:{{ $stStyle['bg'] }};color:{{ $stStyle['color'] }};border:none;
                   cursor:pointer;padding:.18rem .55rem !important"
            title="Update status">
            <i class="bi {{ $stStyle['icon'] }}"></i>{{ $item->status }}
        </button>
    </td>

    {{-- In System --}}
    <td style="padding:.6rem .75rem;vertical-align:middle;text-align:center">
        @if(!$item->cve_id)
            <span style="color:#cbd5e1;font-size:.72rem" title="No CVE ID — N/A">N/A</span>
        @elseif($matched > 0)
            <span style="background:#dbeafe;color:#1e40af;padding:.15rem .5rem;border-radius:20px;
                         font-size:.72rem;font-weight:700" title="{{ $matched }} matched finding(s)">
                <i class="bi bi-crosshair me-1" style="font-size:.65rem"></i>{{ $matched }}
            </span>
        @else
            <span style="color:#e2e8f0;font-size:.78rem">—</span>
        @endif
    </td>

    {{-- Actions --}}
    <td style="padding:.6rem .75rem;vertical-align:middle">
        <button type="button" class="btn btn-sm ti-detail-btn"
            style="font-size:.72rem;padding:.2rem .55rem;border-radius:7px;background:#f8fafc;border:1px solid #e2e8f0;color:#374151"
            title="View details"><i class="bi bi-eye"></i>
        </button>
        <button type="button" class="btn btn-sm ms-1 ti-delete-btn"
            style="font-size:.72rem;padding:.2rem .55rem;border-radius:7px;background:#fff5f5;border:1px solid #fecaca;color:#dc2626"
            title="Delete"><i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
@empty
<tr id="emptyRow">
    <td colspan="8" style="padding:3rem;text-align:center;color:#94a3b8">
        <i class="bi bi-newspaper" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
        No items match the current filters.
    </td>
</tr>
@endforelse
