@extends('layouts.app')
@section('title', 'Settings')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#0f172a,#1e293b);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0">
            <i class="bi bi-gear-fill"></i>
        </div>
        <div>
            <h4 class="mb-0">Settings</h4>
            <p class="mb-0">Configure your account preferences and security</p>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:none;background:#f0fdf4;color:#166534;">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
</div>
@endif

@php
    $logoPath           = \App\Models\SiteSetting::get('logo_path');
    $companyName        = \App\Models\SiteSetting::get('company_name', 'VulnTrack');
    $themeColor         = \App\Models\SiteSetting::get('theme_primary', '#98c20a');
    $rptCompany         = \App\Models\SiteSetting::get('report_company', '');
    $rptConfidentiality = \App\Models\SiteSetting::get('report_confidentiality', '');
    $rptPreparedBy      = \App\Models\SiteSetting::get('report_prepared_by', '');
    $rptTool            = \App\Models\SiteSetting::get('report_tool', '');
    $rptFooter          = \App\Models\SiteSetting::get('report_footer_text', '');
    $rptDisclaimer      = \App\Models\SiteSetting::get('report_disclaimer', '');
    $rptAccent          = \App\Models\SiteSetting::get('report_accent_color', '#84cc16');

    $ldapEnabled     = \App\Models\SiteSetting::get('ldap_enabled', '0') === '1';
    $ldapHost        = \App\Models\SiteSetting::get('ldap_host', '');
    $ldapPort        = \App\Models\SiteSetting::get('ldap_port', '389');
    $ldapEncryption  = \App\Models\SiteSetting::get('ldap_encryption', 'none');
    $ldapBaseDn      = \App\Models\SiteSetting::get('ldap_base_dn', '');
    $ldapBindDn      = \App\Models\SiteSetting::get('ldap_bind_dn', '');
    $ldapBindPwdSet  = (bool) \App\Models\SiteSetting::get('ldap_bind_password', '');
    $ldapUserFilter  = \App\Models\SiteSetting::get('ldap_user_filter', '');
    $ldapUidAttr     = \App\Models\SiteSetting::get('ldap_uid_attribute', 'sAMAccountName');

    $azureEnabled    = \App\Models\SiteSetting::get('azure_enabled', '0') === '1';
    $azureTenant     = \App\Models\SiteSetting::get('azure_tenant_id', '');
    $azureClientId   = \App\Models\SiteSetting::get('azure_client_id', '');
    $azureSecretSet  = (bool) \App\Models\SiteSetting::get('azure_client_secret', '');
    $azureRedirect   = \App\Models\SiteSetting::get('azure_redirect_uri', url('/auth/azure/callback'));
@endphp

<div class="row g-4">

    {{-- ── Company Name (admin only) ── --}}
    @if(Auth::user()->isAdministrator())
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#0f172a;font-weight:700">
                    <i class="bi bi-building me-2" style="color:var(--primary)"></i>Company Name
                </h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">
                    Displayed in the sidebar next to the logo.
                </p>
                <form method="POST" action="{{ route('account.company-name.update') }}" class="d-flex gap-2 align-items-start flex-wrap">
                    @csrf @method('PATCH')
                    <div style="flex:1;min-width:200px;max-width:360px">
                        <input type="text" name="company_name"
                            class="form-control @error('company_name') is-invalid @enderror"
                            value="{{ old('company_name', $companyName) }}"
                            placeholder="e.g. Acme Corp"
                            maxlength="80"
                            style="border-radius:9px;font-size:.875rem">
                        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-sm"
                        style="background:var(--primary);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.1rem;font-size:.85rem;white-space:nowrap">
                        <i class="bi bi-check-lg me-1"></i>Save
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Theme Color (admin only) ── --}}
    @if(Auth::user()->isAdministrator())
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#0f172a;font-weight:700">
                    <i class="bi bi-palette-fill me-2" style="color:var(--primary)"></i>Theme Color
                </h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">
                    Choose a primary color applied across all pages.
                </p>
                <form method="POST" action="{{ route('account.theme-color.update') }}">
                    @csrf @method('PATCH')
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div style="position:relative">
                            <input type="color" id="colorPicker" name="theme_primary"
                                value="{{ $themeColor }}"
                                style="width:52px;height:52px;padding:3px;border-radius:10px;border:1.5px solid #e2e8f0;cursor:pointer;background:#fff">
                        </div>
                        <div style="flex:1;min-width:140px;max-width:200px">
                            <label style="font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:.3rem">Hex Code</label>
                            <input type="text" id="hexInput"
                                value="{{ $themeColor }}"
                                maxlength="7"
                                placeholder="#98c20a"
                                style="border-radius:9px;font-size:.875rem;border:1.5px solid #e2e8f0;padding:.45rem .75rem;width:100%;font-family:monospace">
                        </div>
                        <div id="colorPreview"
                            style="width:52px;height:52px;border-radius:10px;border:1.5px solid #e2e8f0;background:{{ $themeColor }};flex-shrink:0;transition:background .2s"></div>
                        <button type="submit" class="btn btn-sm"
                            style="background:var(--primary);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.1rem;font-size:.85rem;white-space:nowrap">
                            <i class="bi bi-check-lg me-1"></i>Apply
                        </button>
                    </div>
                    @error('theme_primary')
                    <div style="font-size:.78rem;color:#dc2626;margin-top:.5rem">{{ $message }}</div>
                    @enderror
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Report Settings (admin only) ── --}}
    @if(Auth::user()->isAdministrator())
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#0f172a;font-weight:700">
                    <i class="bi bi-file-earmark-text-fill me-2" style="color:var(--primary)"></i>Report Settings
                </h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">
                    Customize the cover page, header, footer, and accent color used in PDF and Word reports.
                </p>
                <form method="POST" action="{{ route('account.report-settings.update') }}">
                    @csrf @method('PATCH')
                    <div class="row g-3">

                        {{-- Organization Name --}}
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Organization Name</label>
                            <input type="text" name="report_company" class="form-control @error('report_company') is-invalid @enderror"
                                value="{{ old('report_company', $rptCompany) }}"
                                placeholder="e.g. Acme Security"
                                maxlength="120"
                                style="border-radius:9px;font-size:.875rem">
                            @error('report_company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Confidentiality Label --}}
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Confidentiality Label</label>
                            <input type="text" name="report_confidentiality" class="form-control @error('report_confidentiality') is-invalid @enderror"
                                value="{{ old('report_confidentiality', $rptConfidentiality) }}"
                                placeholder="e.g. CONFIDENTIAL"
                                maxlength="120"
                                style="border-radius:9px;font-size:.875rem">
                            @error('report_confidentiality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Prepared By --}}
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Prepared By</label>
                            <input type="text" name="report_prepared_by" class="form-control @error('report_prepared_by') is-invalid @enderror"
                                value="{{ old('report_prepared_by', $rptPreparedBy) }}"
                                placeholder="e.g. Red Team / Security Ops"
                                maxlength="120"
                                style="border-radius:9px;font-size:.875rem">
                            @error('report_prepared_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Scanning Tool --}}
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Scanning Tool</label>
                            <input type="text" name="report_tool" class="form-control @error('report_tool') is-invalid @enderror"
                                value="{{ old('report_tool', $rptTool) }}"
                                placeholder="e.g. Tenable Nessus"
                                maxlength="120"
                                style="border-radius:9px;font-size:.875rem">
                            @error('report_tool')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Footer Text --}}
                        <div class="col-12">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">PDF Footer Text</label>
                            <input type="text" name="report_footer_text" class="form-control @error('report_footer_text') is-invalid @enderror"
                                value="{{ old('report_footer_text', $rptFooter) }}"
                                placeholder="e.g. For internal use only — do not distribute"
                                maxlength="300"
                                style="border-radius:9px;font-size:.875rem">
                            <div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem">Shown at the bottom of every PDF page. Defaults to the confidentiality label if left blank.</div>
                            @error('report_footer_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Disclaimer --}}
                        <div class="col-12">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Disclaimer / Notice</label>
                            <textarea name="report_disclaimer" rows="3"
                                class="form-control @error('report_disclaimer') is-invalid @enderror"
                                maxlength="600"
                                placeholder="e.g. This report contains sensitive security findings..."
                                style="border-radius:9px;font-size:.875rem;resize:vertical">{{ old('report_disclaimer', $rptDisclaimer) }}</textarea>
                            @error('report_disclaimer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Accent Color --}}
                        <div class="col-12">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Report Accent Color</label>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <input type="color" id="rptColorPicker" value="{{ $rptAccent }}"
                                    style="width:48px;height:48px;padding:3px;border-radius:10px;border:1.5px solid #e2e8f0;cursor:pointer;background:#fff">
                                <div style="min-width:130px;max-width:180px">
                                    <input type="text" id="rptHexInput" name="report_accent_color"
                                        value="{{ old('report_accent_color', $rptAccent) }}"
                                        maxlength="7"
                                        placeholder="#84cc16"
                                        class="form-control @error('report_accent_color') is-invalid @enderror"
                                        style="border-radius:9px;font-size:.875rem;font-family:monospace">
                                    @error('report_accent_color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div id="rptColorPreview"
                                    style="width:48px;height:48px;border-radius:10px;border:1.5px solid #e2e8f0;background:{{ $rptAccent }};flex-shrink:0;transition:background .2s"></div>
                                <div style="font-size:.78rem;color:#94a3b8">Used for headings, borders, and section titles in exported reports.</div>
                            </div>
                        </div>

                        <div class="col-12 mt-1">
                            <button type="submit" class="btn btn-sm"
                                style="background:var(--primary);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.4rem;font-size:.875rem">
                                <i class="bi bi-check-lg me-1"></i>Save Report Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ── App Logo (admin only) ── --}}
    @if(Auth::user()->isAdministrator())
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#0f172a;font-weight:700">
                    <i class="bi bi-image-fill me-2" style="color:var(--primary)"></i>Application Logo
                </h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">
                    Displayed in the sidebar. PNG, JPG, SVG or WebP — max 2 MB.
                </p>

                <div class="d-flex align-items-center gap-4 flex-wrap">
                    {{-- Current logo preview --}}
                    <div style="width:72px;height:72px;border-radius:14px;border:2px solid #e8f5c2;
                        background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                        @if($logoPath)
                            <img src="{{ Storage::url($logoPath) }}" alt="Current logo"
                                 style="width:64px;height:64px;object-fit:contain;border-radius:10px">
                        @else
                            <img src="{{ asset('favicon.ico') }}" alt="Default logo"
                                 style="width:40px;height:40px;object-fit:contain;opacity:.5">
                        @endif
                    </div>

                    <div style="flex:1;min-width:200px">
                        <form method="POST" action="{{ route('account.logo.upload') }}"
                              enctype="multipart/form-data" id="logo-form">
                            @csrf
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <label style="cursor:pointer">
                                    <input type="file" name="logo" id="logo-input" accept="image/*"
                                           style="display:none" onchange="previewLogo(this)">
                                    <span class="btn btn-sm"
                                        style="border:1.5px solid var(--primary);border-radius:9px;color:var(--primary-dark);
                                               background:#fff;font-weight:600;font-size:.82rem;padding:.4rem .9rem">
                                        <i class="bi bi-upload me-1"></i>Choose File
                                    </span>
                                </label>
                                <span id="logo-filename" style="font-size:.78rem;color:#94a3b8">No file chosen</span>
                                <button type="submit" id="logo-submit" class="btn btn-sm" disabled
                                    style="background:var(--primary);color:#fff;border-radius:9px;font-weight:600;
                                           border:none;padding:.4rem .9rem;font-size:.82rem">
                                    <i class="bi bi-check-lg me-1"></i>Upload
                                </button>
                            </div>
                            @error('logo')
                            <div style="font-size:.78rem;color:#dc2626;margin-top:.4rem">{{ $message }}</div>
                            @enderror
                        </form>

                        @if($logoPath)
                        <form method="POST" action="{{ route('account.logo.delete') }}" class="mt-2"
                              onsubmit="return confirm('Remove custom logo and restore default?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm"
                                style="border:1px solid #fca5a5;color:#dc2626;background:#fff8f8;
                                       border-radius:8px;font-size:.78rem;font-weight:600">
                                <i class="bi bi-trash me-1"></i>Remove Logo
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
    @endif

    {{-- ── LDAP Integration (admin only) ── --}}
    @if(Auth::user()->isAdministrator())
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h6 class="mb-0" style="color:#0f172a;font-weight:700">
                        <i class="bi bi-diagram-3-fill me-2" style="color:var(--primary)"></i>LDAP / Active Directory
                    </h6>
                    <span id="ldap-enabled-badge"
                        style="font-size:.7rem;font-weight:700;padding:.2rem .6rem;border-radius:20px;border:1px solid;
                               background:{{ $ldapEnabled ? '#f0fdf4' : '#f1f5f9' }};
                               color:{{ $ldapEnabled ? '#166534' : '#64748b' }};
                               border-color:{{ $ldapEnabled ? '#bbf7d0' : '#e2e8f0' }}">
                        {{ $ldapEnabled ? 'ENABLED' : 'DISABLED' }}
                    </span>
                </div>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">
                    Allow users to sign in with corporate LDAP or Active Directory credentials.
                </p>

                <form method="POST" action="{{ route('account.ldap-settings.update') }}" id="ldap-form">
                    @csrf @method('PATCH')

                    {{-- Enable toggle --}}
                    <div class="d-flex align-items-center justify-content-between p-3 mb-4"
                         style="background:#f8fafc;border-radius:12px;border:1.5px solid #e2e8f0">
                        <div>
                            <div style="font-weight:600;color:#0f172a;font-size:.875rem">Enable LDAP Authentication</div>
                            <div style="font-size:.78rem;color:#64748b;margin-top:.15rem">Show "Sign in with LDAP" on the login page.</div>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input" type="checkbox" id="ldap_enabled_toggle"
                                   style="width:2.5rem;height:1.3rem;cursor:pointer"
                                   {{ $ldapEnabled ? 'checked' : '' }}>
                        </div>
                    </div>
                    <input type="hidden" name="ldap_enabled" id="ldap_enabled_input" value="{{ $ldapEnabled ? '1' : '0' }}">

                    <div id="ldap-fields">
                        <div class="row g-3">
                            {{-- Host --}}
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">LDAP Host</label>
                                <input type="text" name="ldap_host"
                                    class="form-control @error('ldap_host') is-invalid @enderror"
                                    value="{{ old('ldap_host', $ldapHost) }}"
                                    placeholder="e.g. ldap.example.com or 192.168.1.10"
                                    style="border-radius:9px;font-size:.875rem">
                                @error('ldap_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Port --}}
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Port</label>
                                <input type="number" name="ldap_port"
                                    class="form-control @error('ldap_port') is-invalid @enderror"
                                    value="{{ old('ldap_port', $ldapPort) }}"
                                    min="1" max="65535"
                                    placeholder="389"
                                    style="border-radius:9px;font-size:.875rem">
                                @error('ldap_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Encryption --}}
                            <div class="col-md-3">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Encryption</label>
                                <select name="ldap_encryption" class="form-select @error('ldap_encryption') is-invalid @enderror"
                                        style="border-radius:9px;font-size:.875rem">
                                    <option value="none"  {{ old('ldap_encryption', $ldapEncryption) === 'none' ? 'selected' : '' }}>None (plain)</option>
                                    <option value="tls"   {{ old('ldap_encryption', $ldapEncryption) === 'tls'  ? 'selected' : '' }}>StartTLS (LDAP+TLS)</option>
                                    <option value="ssl"   {{ old('ldap_encryption', $ldapEncryption) === 'ssl'  ? 'selected' : '' }}>SSL (LDAPS port 636)</option>
                                </select>
                                @error('ldap_encryption')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Base DN --}}
                            <div class="col-12">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Base DN</label>
                                <input type="text" name="ldap_base_dn"
                                    class="form-control @error('ldap_base_dn') is-invalid @enderror"
                                    value="{{ old('ldap_base_dn', $ldapBaseDn) }}"
                                    placeholder="e.g. DC=example,DC=com"
                                    style="border-radius:9px;font-size:.875rem;font-family:monospace">
                                @error('ldap_base_dn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Bind DN --}}
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Bind DN <span style="color:#94a3b8;font-weight:400">(service account)</span></label>
                                <input type="text" name="ldap_bind_dn"
                                    class="form-control @error('ldap_bind_dn') is-invalid @enderror"
                                    value="{{ old('ldap_bind_dn', $ldapBindDn) }}"
                                    placeholder="e.g. CN=svc-ldap,OU=ServiceAccounts,DC=example,DC=com"
                                    style="border-radius:9px;font-size:.875rem;font-family:monospace">
                                @error('ldap_bind_dn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Bind Password --}}
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Bind Password</label>
                                <input type="password" name="ldap_bind_password"
                                    class="form-control @error('ldap_bind_password') is-invalid @enderror"
                                    placeholder="{{ $ldapBindPwdSet ? '(saved — leave blank to keep)' : 'Enter bind password' }}"
                                    autocomplete="new-password"
                                    style="border-radius:9px;font-size:.875rem">
                                @error('ldap_bind_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- User Filter --}}
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">User Search Filter <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
                                <input type="text" name="ldap_user_filter"
                                    class="form-control @error('ldap_user_filter') is-invalid @enderror"
                                    value="{{ old('ldap_user_filter', $ldapUserFilter) }}"
                                    placeholder="e.g. (&(objectClass=user)(memberOf=CN=VulnTrack,OU=Groups,DC=example,DC=com))"
                                    style="border-radius:9px;font-size:.875rem;font-family:monospace">
                                @error('ldap_user_filter')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- UID Attribute --}}
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Username Attribute</label>
                                <input type="text" name="ldap_uid_attribute"
                                    class="form-control @error('ldap_uid_attribute') is-invalid @enderror"
                                    value="{{ old('ldap_uid_attribute', $ldapUidAttr) }}"
                                    placeholder="sAMAccountName"
                                    style="border-radius:9px;font-size:.875rem;font-family:monospace">
                                <div style="font-size:.72rem;color:#94a3b8;margin-top:.25rem">AD: <code>sAMAccountName</code> · OpenLDAP: <code>uid</code></div>
                                @error('ldap_uid_attribute')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Test + Save --}}
                        <div class="d-flex align-items-center gap-3 flex-wrap mt-4">
                            <button type="submit" class="btn btn-sm"
                                style="background:var(--primary);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.4rem;font-size:.875rem">
                                <i class="bi bi-check-lg me-1"></i>Save LDAP Settings
                            </button>
                            <button type="button" id="ldap-test-btn" class="btn btn-sm"
                                style="border:1.5px solid var(--primary);color:var(--primary-dark);background:#fff;border-radius:9px;font-weight:600;font-size:.875rem;padding:.45rem 1rem">
                                <i class="bi bi-plug-fill me-1"></i>Test Connection
                            </button>
                            <span id="ldap-test-result" style="font-size:.82rem;display:none"></span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Azure AD (admin only) ── --}}
    @if(Auth::user()->isAdministrator())
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h6 class="mb-0" style="color:#0f172a;font-weight:700">
                        <i class="bi bi-microsoft me-2" style="color:var(--primary)"></i>Azure AD / Microsoft Entra ID
                    </h6>
                    <span id="azure-enabled-badge"
                        style="font-size:.7rem;font-weight:700;padding:.2rem .6rem;border-radius:20px;border:1px solid;
                               background:{{ $azureEnabled ? '#f0fdf4' : '#f1f5f9' }};
                               color:{{ $azureEnabled ? '#166534' : '#64748b' }};
                               border-color:{{ $azureEnabled ? '#bbf7d0' : '#e2e8f0' }}">
                        {{ $azureEnabled ? 'ENABLED' : 'DISABLED' }}
                    </span>
                </div>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">
                    Allow users to sign in with their Microsoft / Azure AD account via OAuth 2.0.
                </p>

                <form method="POST" action="{{ route('account.azure-settings.update') }}" id="azure-form">
                    @csrf @method('PATCH')

                    {{-- Enable toggle --}}
                    <div class="d-flex align-items-center justify-content-between p-3 mb-4"
                         style="background:#f8fafc;border-radius:12px;border:1.5px solid #e2e8f0">
                        <div>
                            <div style="font-weight:600;color:#0f172a;font-size:.875rem">Enable Azure AD Sign-In</div>
                            <div style="font-size:.78rem;color:#64748b;margin-top:.15rem">Show "Sign in with Microsoft" on the login page.</div>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input" type="checkbox" id="azure_enabled_toggle"
                                   style="width:2.5rem;height:1.3rem;cursor:pointer"
                                   {{ $azureEnabled ? 'checked' : '' }}>
                        </div>
                    </div>
                    <input type="hidden" name="azure_enabled" id="azure_enabled_input" value="{{ $azureEnabled ? '1' : '0' }}">

                    <div class="row g-3">
                        {{-- Tenant ID --}}
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Tenant ID (Directory ID)</label>
                            <input type="text" name="azure_tenant_id"
                                class="form-control @error('azure_tenant_id') is-invalid @enderror"
                                value="{{ old('azure_tenant_id', $azureTenant) }}"
                                placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                style="border-radius:9px;font-size:.875rem;font-family:monospace">
                            <div style="font-size:.72rem;color:#94a3b8;margin-top:.25rem">Found in Azure Portal → App Registrations → Overview.</div>
                            @error('azure_tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Client ID --}}
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Application (Client) ID</label>
                            <input type="text" name="azure_client_id"
                                class="form-control @error('azure_client_id') is-invalid @enderror"
                                value="{{ old('azure_client_id', $azureClientId) }}"
                                placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                style="border-radius:9px;font-size:.875rem;font-family:monospace">
                            @error('azure_client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Client Secret --}}
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Client Secret</label>
                            <input type="password" name="azure_client_secret"
                                class="form-control @error('azure_client_secret') is-invalid @enderror"
                                placeholder="{{ $azureSecretSet ? '(saved — leave blank to keep)' : 'Paste client secret value' }}"
                                autocomplete="new-password"
                                style="border-radius:9px;font-size:.875rem">
                            @error('azure_client_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Redirect URI --}}
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;color:#374151">Redirect URI</label>
                            <div class="input-group" style="border-radius:9px;overflow:hidden">
                                <input type="url" name="azure_redirect_uri" id="azure_redirect_uri"
                                    class="form-control @error('azure_redirect_uri') is-invalid @enderror"
                                    value="{{ old('azure_redirect_uri', $azureRedirect) }}"
                                    style="border-radius:9px 0 0 9px;font-size:.82rem;font-family:monospace">
                                <button type="button" id="copy-redirect-btn"
                                    class="btn btn-sm"
                                    style="border:1.5px solid #e2e8f0;border-left:none;background:#f8fafc;color:#64748b;border-radius:0 9px 9px 0;font-size:.8rem;padding:0 .8rem"
                                    title="Copy to clipboard">
                                    <i class="bi bi-clipboard" id="copy-redirect-icon"></i>
                                </button>
                            </div>
                            <div style="font-size:.72rem;color:#94a3b8;margin-top:.25rem">Register this exact URL in Azure Portal → App Registrations → Authentication → Redirect URIs.</div>
                            @error('azure_redirect_uri')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 mt-1">
                            <button type="submit" class="btn btn-sm"
                                style="background:var(--primary);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.4rem;font-size:.875rem">
                                <i class="bi bi-check-lg me-1"></i>Save Azure Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Security Settings ── --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#0f172a;font-weight:700">Security</h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">Control authentication and access security options.</p>

                <form method="POST" action="{{ route('account.settings.update') }}">
                    @csrf @method('PATCH')

                    {{-- MFA Toggle --}}
                    <div class="d-flex align-items-start justify-content-between p-4 mb-3" style="background:#f8fafc;border-radius:14px;border:1.5px solid #e2e8f0">
                        <div class="d-flex gap-3 align-items-start">
                            <div style="width:44px;height:44px;border-radius:12px;{{ $user->mfa_enabled ? 'background:#f0fdf4' : 'background:#fef9c3' }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-shield-lock-fill fs-5" style="color:{{ $user->mfa_enabled ? '#16a34a' : '#ca8a04' }}"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;color:#0f172a;font-size:.9rem">Two-Factor Authentication (MFA)</div>
                                <div style="font-size:.8rem;color:#64748b;margin-top:.2rem">Require a one-time code sent to your email each time you sign in.</div>
                                @if($user->mfa_enabled)
                                <span class="mt-2 d-inline-block" style="background:#f0fdf4;color:#166534;font-size:.7rem;font-weight:700;padding:.15rem .55rem;border-radius:20px;border:1px solid #bbf7d0">
                                    <i class="bi bi-check-circle-fill me-1"></i>ACTIVE
                                </span>
                                @else
                                <span class="mt-2 d-inline-block" style="background:#fef9c3;color:#854d0e;font-size:.7rem;font-weight:700;padding:.15rem .55rem;border-radius:20px;border:1px solid #fde68a">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i>INACTIVE
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-check form-switch ms-3 mt-1">
                            <input class="form-check-input" type="checkbox" id="mfa_toggle"
                                   style="width:2.5rem;height:1.3rem;cursor:pointer"
                                   {{ $user->mfa_enabled ? 'checked' : '' }}>
                        </div>
                    </div>

                    <input type="hidden" name="mfa_enabled" id="mfa_enabled_input" value="{{ $user->mfa_enabled ? '1' : '0' }}">

                    <button type="submit" class="btn btn-primary px-4" style="background:var(--primary);border-color:var(--primary);border-radius:10px;font-size:.875rem;font-weight:600">
                        Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Danger Zone ── --}}
    <div class="col-lg-5">
        <div class="card" style="border-color:#fecaca">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#991b1b;font-weight:700"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">Irreversible account actions. Proceed with caution.</p>

                <div class="p-3 mb-3" style="background:#fef2f2;border-radius:12px;border:1px solid #fecaca">
                    <div style="font-weight:600;color:#0f172a;font-size:.875rem">Sign Out All Sessions</div>
                    <div style="font-size:.8rem;color:#64748b;margin:.25rem 0 .75rem">Revoke all active sessions across all devices.</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border-radius:8px;font-size:.8rem;font-weight:600;border:1px solid #fecaca">
                            <i class="bi bi-box-arrow-right me-1"></i> Sign Out Everywhere
                        </button>
                    </form>
                </div>

                <div class="p-3" style="background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0">
                    <div style="font-weight:600;color:#0f172a;font-size:.875rem">Quick Links</div>
                    <div class="d-flex flex-column gap-2 mt-2">
                        <a href="{{ route('account.profile') }}" class="d-flex align-items-center gap-2" style="font-size:.85rem;color:var(--primary-dark);text-decoration:none">
                            <i class="bi bi-person-fill"></i> Edit Profile
                        </a>
                        <a href="{{ route('account.profile') }}#password" class="d-flex align-items-center gap-2" style="font-size:.85rem;color:var(--primary-dark);text-decoration:none">
                            <i class="bi bi-lock-fill"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
    const toggle    = document.getElementById('mfa_toggle');
    const hiddenInput = document.getElementById('mfa_enabled_input');
    toggle.addEventListener('change', () => {
        hiddenInput.value = toggle.checked ? '1' : '0';
    });

    const colorPicker = document.getElementById('colorPicker');
    const hexInput    = document.getElementById('hexInput');
    const preview     = document.getElementById('colorPreview');

    function syncFromPicker() {
        const val = colorPicker.value;
        hexInput.value = val;
        preview.style.background = val;
        colorPicker.form.querySelector('[name="theme_primary"]').value = val;
    }
    function syncFromHex() {
        const val = hexInput.value.trim();
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
            colorPicker.value = val;
            colorPicker.form.querySelector('[name="theme_primary"]').value = val;
            preview.style.background = val;
        }
    }

    colorPicker.addEventListener('input', syncFromPicker);
    hexInput.addEventListener('input', syncFromHex);

    const rptPicker  = document.getElementById('rptColorPicker');
    const rptHex     = document.getElementById('rptHexInput');
    const rptPreview = document.getElementById('rptColorPreview');

    function syncRptFromPicker() {
        const val = rptPicker.value;
        rptHex.value = val;
        rptPreview.style.background = val;
    }
    function syncRptFromHex() {
        const val = rptHex.value.trim();
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
            rptPicker.value = val;
            rptPreview.style.background = val;
        }
    }
    rptPicker.addEventListener('input', syncRptFromPicker);
    rptHex.addEventListener('input', syncRptFromHex);

    // LDAP toggle
    const ldapToggle = document.getElementById('ldap_enabled_toggle');
    const ldapInput  = document.getElementById('ldap_enabled_input');
    const ldapBadge  = document.getElementById('ldap-enabled-badge');
    if (ldapToggle) {
        ldapToggle.addEventListener('change', () => {
            ldapInput.value = ldapToggle.checked ? '1' : '0';
            ldapBadge.textContent = ldapToggle.checked ? 'ENABLED' : 'DISABLED';
            ldapBadge.style.background = ldapToggle.checked ? '#f0fdf4' : '#f1f5f9';
            ldapBadge.style.color      = ldapToggle.checked ? '#166534' : '#64748b';
            ldapBadge.style.borderColor= ldapToggle.checked ? '#bbf7d0' : '#e2e8f0';
        });
    }

    // LDAP test connection
    const ldapTestBtn = document.getElementById('ldap-test-btn');
    if (ldapTestBtn) {
        ldapTestBtn.addEventListener('click', async () => {
            const resultEl = document.getElementById('ldap-test-result');
            ldapTestBtn.disabled = true;
            ldapTestBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing…';
            resultEl.style.display = 'none';
            try {
                const resp = await fetch('{{ route('account.ldap-test') }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await resp.json();
                resultEl.style.display = 'inline';
                resultEl.style.color   = data.success ? '#166534' : '#dc2626';
                resultEl.innerHTML = (data.success
                    ? '<i class="bi bi-check-circle-fill me-1"></i>'
                    : '<i class="bi bi-x-circle-fill me-1"></i>') + data.message;
            } catch (e) {
                resultEl.style.display = 'inline';
                resultEl.style.color   = '#dc2626';
                resultEl.textContent   = 'Request failed.';
            } finally {
                ldapTestBtn.disabled = false;
                ldapTestBtn.innerHTML = '<i class="bi bi-plug-fill me-1"></i>Test Connection';
            }
        });
    }

    // Azure toggle
    const azureToggle = document.getElementById('azure_enabled_toggle');
    const azureInput  = document.getElementById('azure_enabled_input');
    const azureBadge  = document.getElementById('azure-enabled-badge');
    if (azureToggle) {
        azureToggle.addEventListener('change', () => {
            azureInput.value = azureToggle.checked ? '1' : '0';
            azureBadge.textContent = azureToggle.checked ? 'ENABLED' : 'DISABLED';
            azureBadge.style.background = azureToggle.checked ? '#f0fdf4' : '#f1f5f9';
            azureBadge.style.color      = azureToggle.checked ? '#166534' : '#64748b';
            azureBadge.style.borderColor= azureToggle.checked ? '#bbf7d0' : '#e2e8f0';
        });
    }

    // Copy redirect URI
    const copyBtn = document.getElementById('copy-redirect-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const val = document.getElementById('azure_redirect_uri').value;
            navigator.clipboard.writeText(val).then(() => {
                const icon = document.getElementById('copy-redirect-icon');
                icon.className = 'bi bi-clipboard-check';
                setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 2000);
            });
        });
    }

    function previewLogo(input) {
        const name   = input.files[0]?.name ?? 'No file chosen';
        document.getElementById('logo-filename').textContent = name;
        document.getElementById('logo-submit').disabled = !input.files.length;
        if (input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const preview = document.querySelector('#logo-form').closest('.card-body').querySelector('img');
                if (preview) { preview.src = e.target.result; preview.style.opacity = '1'; }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
