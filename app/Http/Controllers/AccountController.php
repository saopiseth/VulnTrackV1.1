<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    // ─── Profile ─────────────────────────────────────────────

    public function profile()
    {
        return view('account.profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,' . $user->id],
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password'      => ['required'],
            'password'              => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withFragment('password');
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.')->withFragment('password');
    }

    // ─── Settings ────────────────────────────────────────────

    public function settings()
    {
        return view('account.settings', ['user' => Auth::user()]);
    }

    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'mfa_enabled' => ['required', 'boolean'],
        ]);

        $user->update(['mfa_enabled' => $request->boolean('mfa_enabled')]);

        return back()->with('success', 'Settings saved.');
    }

    public function updateCompanyName(Request $request)
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);

        $request->validate([
            'company_name' => ['required', 'string', 'max:80'],
        ]);

        SiteSetting::set('company_name', trim($request->company_name));

        return back()->with('success', 'Company name updated.');
    }

    public function updateReportSettings(Request $request)
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);

        $data = $request->validate([
            'report_company'         => ['nullable', 'string', 'max:120'],
            'report_confidentiality' => ['nullable', 'string', 'max:120'],
            'report_prepared_by'     => ['nullable', 'string', 'max:120'],
            'report_tool'            => ['nullable', 'string', 'max:120'],
            'report_footer_text'     => ['nullable', 'string', 'max:300'],
            'report_disclaimer'      => ['nullable', 'string', 'max:600'],
            'report_accent_color'    => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        foreach ($data as $key => $value) {
            SiteSetting::set($key, $value ?? '');
        }

        return back()->with('success', 'Report settings saved.');
    }

    public function updateThemeColor(Request $request)
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);

        $request->validate([
            'theme_primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        SiteSetting::set('theme_primary', strtolower($request->theme_primary));

        return back()->with('success', 'Theme color updated.');
    }

    public function uploadLogo(Request $request)
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        // Delete old logo if exists
        $old = SiteSetting::get('logo_path');
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('logo')->store('logos', 'public');
        SiteSetting::set('logo_path', $path);

        return back()->with('success', 'Logo updated successfully.');
    }

    public function deleteLogo()
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);

        $path = SiteSetting::get('logo_path');
        if ($path) {
            Storage::disk('public')->delete($path);
            SiteSetting::set('logo_path', null);
        }

        return back()->with('success', 'Logo removed. Default icon restored.');
    }

    // ─── LDAP Integration ────────────────────────────────────────

    public function updateLdapSettings(Request $request)
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);

        $data = $request->validate([
            'ldap_enabled'       => ['required', 'boolean'],
            'ldap_host'          => ['nullable', 'string', 'max:255'],
            'ldap_port'          => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ldap_encryption'    => ['nullable', 'in:none,tls,ssl'],
            'ldap_base_dn'       => ['nullable', 'string', 'max:500'],
            'ldap_bind_dn'       => ['nullable', 'string', 'max:500'],
            'ldap_bind_password' => ['nullable', 'string', 'max:500'],
            'ldap_user_filter'   => ['nullable', 'string', 'max:500'],
            'ldap_uid_attribute' => ['nullable', 'string', 'max:100'],
        ]);

        SiteSetting::set('ldap_enabled',      $data['ldap_enabled'] ? '1' : '0');
        SiteSetting::set('ldap_host',         $data['ldap_host']         ?? '');
        SiteSetting::set('ldap_port',         (string) ($data['ldap_port'] ?? 389));
        SiteSetting::set('ldap_encryption',   $data['ldap_encryption']   ?? 'none');
        SiteSetting::set('ldap_base_dn',      $data['ldap_base_dn']      ?? '');
        SiteSetting::set('ldap_bind_dn',      $data['ldap_bind_dn']      ?? '');
        SiteSetting::set('ldap_user_filter',  $data['ldap_user_filter']  ?? '');
        SiteSetting::set('ldap_uid_attribute',$data['ldap_uid_attribute'] ?? 'sAMAccountName');

        if (!empty($data['ldap_bind_password'])) {
            SiteSetting::set('ldap_bind_password', encrypt($data['ldap_bind_password']));
        }

        return back()->with('success', 'LDAP settings saved.');
    }

    public function testLdapConnection(): \Illuminate\Http\JsonResponse
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);

        if (!function_exists('ldap_connect')) {
            return response()->json(['success' => false, 'message' => 'PHP LDAP extension is not installed on this server.']);
        }

        $host       = SiteSetting::get('ldap_host');
        $port       = (int) (SiteSetting::get('ldap_port') ?: 389);
        $bindDn     = SiteSetting::get('ldap_bind_dn');
        $encPwd     = SiteSetting::get('ldap_bind_password');
        $encryption = SiteSetting::get('ldap_encryption', 'none');

        if (!$host) {
            return response()->json(['success' => false, 'message' => 'LDAP host is not configured. Save settings first.']);
        }

        $bindPassword = '';
        if ($encPwd) {
            try { $bindPassword = decrypt($encPwd); } catch (\Exception) {}
        }

        try {
            $prefix = $encryption === 'ssl' ? 'ldaps://' : 'ldap://';
            $conn   = @ldap_connect($prefix . $host, $port);

            if (!$conn) {
                return response()->json(['success' => false, 'message' => 'Could not reach LDAP host — check host/port.']);
            }

            ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);

            if ($encryption === 'tls') {
                @ldap_start_tls($conn);
            }

            $bound = $bindDn
                ? @ldap_bind($conn, $bindDn, $bindPassword)
                : @ldap_bind($conn);

            ldap_close($conn);

            return $bound
                ? response()->json(['success' => true,  'message' => 'Connection successful — bind OK.'])
                : response()->json(['success' => false, 'message' => 'Connected but bind failed. Check Bind DN and password.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // ─── Azure AD ────────────────────────────────────────────────

    public function updateAzureSettings(Request $request)
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);

        $data = $request->validate([
            'azure_enabled'       => ['required', 'boolean'],
            'azure_tenant_id'     => ['nullable', 'string', 'max:100'],
            'azure_client_id'     => ['nullable', 'string', 'max:100'],
            'azure_client_secret' => ['nullable', 'string', 'max:500'],
            'azure_redirect_uri'  => ['nullable', 'url', 'max:500'],
        ]);

        SiteSetting::set('azure_enabled',      $data['azure_enabled'] ? '1' : '0');
        SiteSetting::set('azure_tenant_id',    $data['azure_tenant_id']   ?? '');
        SiteSetting::set('azure_client_id',    $data['azure_client_id']   ?? '');
        SiteSetting::set('azure_redirect_uri', $data['azure_redirect_uri'] ?? url('/auth/azure/callback'));

        if (!empty($data['azure_client_secret'])) {
            SiteSetting::set('azure_client_secret', encrypt($data['azure_client_secret']));
        }

        return back()->with('success', 'Azure AD settings saved.');
    }
}
