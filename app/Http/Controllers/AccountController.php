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
}
