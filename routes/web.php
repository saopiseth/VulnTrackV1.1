<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectAssessmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VulnerabilityController;
use App\Http\Controllers\VulnAssessmentController;
use App\Http\Controllers\AssetInventoryController;

// ─── Redirect root to login ───────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));

// ─── Guest-only routes ────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
});

// ─── MFA routes (session-gated, no auth middleware) ───────────
Route::get('/mfa/verify',  [AuthController::class, 'showMfa'])->name('mfa.verify');
Route::post('/mfa/verify', [AuthController::class, 'verifyMfa'])->name('mfa.verify.post');
Route::post('/mfa/resend', [AuthController::class, 'resendMfa'])->name('mfa.resend');

// ─── Forgot password (placeholder) ───────────────────────────
Route::get('/forgot-password', fn() => view('auth.login'))->name('password.request');

// ─── Authenticated routes ─────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

    Route::resource('assessments', ProjectAssessmentController::class);
    Route::get('/assessments/{assessment}/report', [ProjectAssessmentController::class, 'report'])->name('assessments.report');
    Route::resource('users', UserController::class);

    Route::get('/vulnerabilities', [VulnerabilityController::class, 'index'])->name('vulnerabilities.index');
    Route::post('/vulnerabilities/upload', [VulnerabilityController::class, 'upload'])->name('vulnerabilities.upload');
    Route::patch('/vulnerabilities/{vulnerability}/status', [VulnerabilityController::class, 'updateStatus'])->name('vulnerabilities.status');
    Route::delete('/vulnerabilities/{vulnerability}', [VulnerabilityController::class, 'destroy'])->name('vulnerabilities.destroy');

    // Vulnerability Assessment module
    Route::get('/vuln-assessments',                                         [VulnAssessmentController::class, 'index'])->name('vuln-assessments.index');
    Route::get('/vuln-assessments/create',                                  [VulnAssessmentController::class, 'create'])->name('vuln-assessments.create');
    Route::post('/vuln-assessments',                                        [VulnAssessmentController::class, 'store'])->name('vuln-assessments.store');
    Route::get('/vuln-assessments/{vulnAssessment}',                        [VulnAssessmentController::class, 'show'])->name('vuln-assessments.show');
    Route::get('/vuln-assessments/{vulnAssessment}/findings',               [VulnAssessmentController::class, 'findings'])->name('vuln-assessments.findings');
    Route::post('/vuln-assessments/{vulnAssessment}/upload',                [VulnAssessmentController::class, 'uploadScan'])->name('vuln-assessments.upload');
    Route::patch('/vuln-assessments/{vulnAssessment}/remediations/{remediation}', [VulnAssessmentController::class, 'updateRemediation'])->name('vuln-assessments.remediation.update');
    Route::get('/vuln-assessments/{vulnAssessment}/os-assets',              [VulnAssessmentController::class, 'osAssets'])->name('vuln-assessments.os-assets');
    Route::post('/vuln-assessments/{vulnAssessment}/os-override/{hostOs}',  [VulnAssessmentController::class, 'osOverride'])->name('vuln-assessments.os-override');
    Route::post('/vuln-assessments/{vulnAssessment}/reclassify',            [VulnAssessmentController::class, 'reclassify'])->name('vuln-assessments.reclassify');
    Route::delete('/vuln-assessments/{vulnAssessment}',                     [VulnAssessmentController::class, 'destroy'])->name('vuln-assessments.destroy');

    // Asset Inventory module
    Route::get('/inventory/scan-data', [AssetInventoryController::class, 'scanData'])->name('inventory.scan-data');
    Route::post('/inventory/classify',  [AssetInventoryController::class, 'classify'])->name('inventory.classify');
    Route::resource('inventory', AssetInventoryController::class);

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout',  [AuthController::class, 'logout']);
});
