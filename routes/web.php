<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AssessmentScopeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SlaPolicyController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\VulnerabilityController;
use App\Http\Controllers\VulnAssessmentController;

// ─── Redirect root to login ───────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));

// ─── Guest-only routes ────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});

// Self-registration is disabled — new users are created by administrators via /users.
// Redirect /register to login so stale bookmarks or bots don't get a 404.
Route::get('/register',  fn() => redirect()->route('login'))->name('register');
Route::post('/register', fn() => redirect()->route('login'));

// ─── MFA routes (session-gated, no auth middleware) ───────────
Route::get('/mfa/verify',  [AuthController::class, 'showMfa'])->name('mfa.verify');
Route::post('/mfa/verify', [AuthController::class, 'verifyMfa'])->name('mfa.verify.post')->middleware('throttle:mfa');
Route::post('/mfa/resend', [AuthController::class, 'resendMfa'])->name('mfa.resend')->middleware('throttle:mfa');

// ─── Forgot password (placeholder) ───────────────────────────
Route::get('/forgot-password', fn() => view('auth.login'))->name('password.request');

// ─── Authenticated routes ─────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/dashboard',  [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/layout', [DashboardController::class, 'saveLayout'])->name('dashboard.layout');

    Route::resource('users', UserController::class);
    Route::resource('user-groups', UserGroupController::class);

    Route::resource('sla-policies', SlaPolicyController::class)->except(['show']);

    Route::get('/vulnerabilities', [VulnerabilityController::class, 'index'])->name('vulnerabilities.index');
    Route::post('/vulnerabilities/upload', [VulnerabilityController::class, 'upload'])->middleware('throttle:upload')->name('vulnerabilities.upload');
    Route::patch('/vulnerabilities/{vulnerability}/status', [VulnerabilityController::class, 'updateStatus'])->name('vulnerabilities.status');
    Route::delete('/vulnerabilities/{vulnerability}', [VulnerabilityController::class, 'destroy'])->name('vulnerabilities.destroy');

    // Assessment Scope Groups
    Route::get('/assessment-scope',                                                               [AssessmentScopeController::class, 'index'])->name('assessment-scope.index');
    Route::post('/assessment-scope',                                                              [AssessmentScopeController::class, 'store'])->name('assessment-scope.store');
    Route::get('/assessment-scope/{assessmentScopeGroup}',                                        [AssessmentScopeController::class, 'show'])->name('assessment-scope.show');
    Route::patch('/assessment-scope/{assessmentScopeGroup}',                                      [AssessmentScopeController::class, 'update'])->name('assessment-scope.update');
    Route::delete('/assessment-scope/{assessmentScopeGroup}',                                     [AssessmentScopeController::class, 'destroy'])->name('assessment-scope.destroy');
    // Assessment Scope Items
    Route::post('/assessment-scope/{assessmentScopeGroup}/items',                                 [AssessmentScopeController::class, 'storeItem'])->name('assessment-scope.items.store');
    Route::patch('/assessment-scope/{assessmentScopeGroup}/items/{item}',                         [AssessmentScopeController::class, 'updateItem'])->name('assessment-scope.items.update');
    Route::delete('/assessment-scope/{assessmentScopeGroup}/items/{item}',                        [AssessmentScopeController::class, 'destroyItem'])->name('assessment-scope.items.destroy');
    Route::post('/assessment-scope/{assessmentScopeGroup}/import',                                [AssessmentScopeController::class, 'importBatch'])->name('assessment-scope.import');
    Route::get('/assessment-scope/{assessmentScopeGroup}/export',                                 [AssessmentScopeController::class, 'export'])->name('assessment-scope.export');
    Route::get('/assessment-scope/{assessmentScopeGroup}/items-json',                             [AssessmentScopeController::class, 'itemsJson'])->name('assessment-scope.items.json');

    // Vulnerability Assessment module
    Route::get('/vuln-assessments',                                         [VulnAssessmentController::class, 'index'])->name('vuln-assessments.index');
    Route::get('/vuln-assessments/create',                                  [VulnAssessmentController::class, 'create'])->name('vuln-assessments.create');
    Route::post('/vuln-assessments',                                        [VulnAssessmentController::class, 'store'])->name('vuln-assessments.store');
    Route::get('/vuln-assessments/{vulnAssessment}',                        [VulnAssessmentController::class, 'show'])->name('vuln-assessments.show');
    Route::get('/vuln-assessments/{vulnAssessment}/findings',               [VulnAssessmentController::class, 'findings'])->name('vuln-assessments.findings');
    Route::get('/vuln-assessments/{vulnAssessment}/progress',               [VulnAssessmentController::class, 'progress'])->name('vuln-assessments.progress');
    Route::post('/vuln-assessments/{vulnAssessment}/upload',                [VulnAssessmentController::class, 'uploadScan'])->middleware('throttle:upload')->name('vuln-assessments.upload');
    Route::get('/vuln-assessments/{vulnAssessment}/scan-status/{scan}',     [VulnAssessmentController::class, 'uploadStatus'])->name('vuln-assessments.upload.status');
    Route::post('/vuln-assessments/{vulnAssessment}/upload-chunk',          [VulnAssessmentController::class, 'uploadChunk'])->middleware('throttle:upload')->name('vuln-assessments.upload.chunk');
    Route::patch('/vuln-assessments/{vulnAssessment}/remediations/{remediation}', [VulnAssessmentController::class, 'updateRemediation'])->name('vuln-assessments.remediation.update');
    Route::patch('/vuln-assessments/{vulnAssessment}/remediations-bulk',          [VulnAssessmentController::class, 'bulkUpdateRemediation'])->name('vuln-assessments.remediation.bulk-update');
    Route::get('/vuln-assessments/{vulnAssessment}/os-assets',              [VulnAssessmentController::class, 'osAssets'])->name('vuln-assessments.os-assets');
    Route::post('/vuln-assessments/{vulnAssessment}/os-override/{hostOs}',  [VulnAssessmentController::class, 'osOverride'])->name('vuln-assessments.os-override');
    Route::post('/vuln-assessments/{vulnAssessment}/reclassify',            [VulnAssessmentController::class, 'reclassify'])->name('vuln-assessments.reclassify');
    Route::delete('/vuln-assessments/{vulnAssessment}',                     [VulnAssessmentController::class, 'destroy'])->name('vuln-assessments.destroy');
    Route::patch('/vuln-assessments/{vulnAssessment}/scope-group',          [VulnAssessmentController::class, 'updateScopeGroup'])->name('vuln-assessments.scope-group.update');
    Route::get('/vuln-assessments/{vulnAssessment}/report/pdf',             [VulnAssessmentController::class, 'reportPdf'])->name('vuln-assessments.report.pdf');
    Route::get('/vuln-assessments/{vulnAssessment}/report/word',            [VulnAssessmentController::class, 'reportWord'])->name('vuln-assessments.report.word');
    Route::get('/vuln-assessments/{vulnAssessment}/report/excel',           [VulnAssessmentController::class, 'reportExcel'])->name('vuln-assessments.report.excel');


    // Account
    Route::get('/account/profile',           [AccountController::class, 'profile'])->name('account.profile');
    Route::patch('/account/profile',         [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::patch('/account/password',        [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::get('/account/settings',          [AccountController::class, 'settings'])->name('account.settings');
    Route::patch('/account/settings',        [AccountController::class, 'updateSettings'])->name('account.settings.update');
    Route::post('/account/logo',             [AccountController::class, 'uploadLogo'])->name('account.logo.upload');
    Route::delete('/account/logo',           [AccountController::class, 'deleteLogo'])->name('account.logo.delete');
    Route::patch('/account/company-name',    [AccountController::class, 'updateCompanyName'])->name('account.company-name.update');
    Route::patch('/account/theme-color',     [AccountController::class, 'updateThemeColor'])->name('account.theme-color.update');
    Route::patch('/account/report-settings', [AccountController::class, 'updateReportSettings'])->name('account.report-settings.update');
    Route::patch('/account/ldap-settings',  [AccountController::class, 'updateLdapSettings'])->name('account.ldap-settings.update');
    Route::get('/account/ldap-test',        [AccountController::class, 'testLdapConnection'])->name('account.ldap-test');
    Route::patch('/account/azure-settings', [AccountController::class, 'updateAzureSettings'])->name('account.azure-settings.update');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout',  [AuthController::class, 'logout']);
});
