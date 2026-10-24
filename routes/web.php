<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\AuthenticatedSessionController as Auth;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolAdminController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\V3Controller as V;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->user()?->isSystemAdmin() ? 'system.schools' : 'dashboard'));
Route::middleware('guest')->group(function () {
    Route::get('/pengaturan-awal', [SetupController::class, 'create'])->name('setup.create');
    Route::post('/pengaturan-awal', [SetupController::class, 'store'])->middleware('throttle:6,1')->name('setup.store');
    Route::get('/login', [Auth::class, 'create'])->name('login');
    Route::post('/login', [Auth::class, 'store']);
});
Route::post('/logout', [Auth::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'active', 'school.active'])->group(function () {
    Route::get('/dashboard', [V::class, 'index'])->name('dashboard');
    Route::get('/persetujuan', [ApprovalController::class, 'index'])->name('v3.approvals');
    Route::post('/kegiatan', [ActivityController::class, 'activityStore'])->name('v3.activity.store');
    Route::get('/kegiatan/{id}', [ActivityController::class, 'activity'])->whereNumber('id')->name('v3.activity');
    Route::get('/kegiatan/{id}/dokumen.pdf', [ActivityController::class, 'activityDocuments'])->whereNumber('id')->name('v3.activity.documents');
    Route::get('/kegiatan/{activity}/belanja/baru', [PurchaseController::class, 'form'])->whereNumber('activity')->name('v3.create');
    Route::post('/kegiatan/{activity}/belanja', [PurchaseController::class, 'save'])->whereNumber('activity')->name('v3.store');
    Route::get('/kegiatan/{activity}/belanja/{id}/edit', [PurchaseController::class, 'form'])->whereNumber(['activity', 'id'])->name('v3.edit');
    Route::put('/kegiatan/{activity}/belanja/{id}', [PurchaseController::class, 'save'])->whereNumber(['activity', 'id'])->name('v3.update');
    Route::post('/belanja/{id}/penerimaan', [ReceiptController::class, 'store'])->whereNumber('id')->name('v3.receipts.store');
    Route::get('/belanja/{id}', [PurchaseController::class, 'purchase'])->whereNumber('id')->name('v3.purchase');
    Route::post('/belanja/{id}/dokumen', [DocumentController::class, 'issue'])->whereNumber('id')->name('v3.issue');
    Route::post('/belanja/{id}/status', [PurchaseController::class, 'transition'])->whereNumber('id')->name('v3.transition');
    Route::post('/belanja/{id}/bukti', [PurchaseController::class, 'upload'])->whereNumber('id')->name('v3.upload');
    Route::get('/bukti/{id}', [PurchaseController::class, 'attachment'])->whereNumber('id')->name('v3.attachment');
    Route::get('/dokumen/{id}', [DocumentController::class, 'document'])->whereNumber('id')->name('v3.document');
    Route::post('/dokumen/{id}/batalkan', [DocumentController::class, 'cancelDocument'])->whereNumber('id')->name('v3.document.cancel');
    Route::get('/pengaturan', [SettingsController::class, 'settings'])->name('v3.settings');
    Route::post('/pengaturan', [SettingsController::class, 'settingsSave'])->name('v3.settings.save');
    Route::get('/logo/{kind}', [SettingsController::class, 'logo'])->name('v3.logo');
    Route::get('/anggaran', [BudgetController::class, 'budgets'])->name('v3.budgets');
    Route::post('/anggaran', [BudgetController::class, 'budgetSave'])->name('v3.budgets.save');
    Route::get('/anggaran/{id}/edit', [BudgetController::class, 'budgetEdit'])->whereNumber('id')->name('v3.budgets.edit');
    Route::put('/anggaran/{id}', [BudgetController::class, 'budgetUpdate'])->whereNumber('id')->name('v3.budgets.update');
    Route::delete('/anggaran/{id}', [BudgetController::class, 'budgetDestroy'])->whereNumber('id')->name('v3.budgets.destroy');
    Route::get('/akun', [AccountController::class, 'accounts'])->name('admin.users.create');
    Route::post('/akun', [AccountController::class, 'accountSave'])->name('v3.accounts.save');
    Route::patch('/akun/{id}', [AccountController::class, 'accountUpdate'])->whereNumber('id')->name('v3.accounts.update');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/laporan', [ReportController::class, 'reports'])->name('v3.reports');
    Route::get('/laporan.xlsx', [ReportController::class, 'excel'])->name('v3.excel');
    Route::get('/laporan.csv', [ReportController::class, 'csv'])->name('v3.csv');

});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/sistem/sekolah', [SchoolAdminController::class, 'index'])->name('system.schools');
    Route::post('/sistem/sekolah', [SchoolAdminController::class, 'store'])->middleware('throttle:10,1')->name('system.schools.store');
    Route::patch('/sistem/sekolah/{school}', [SchoolAdminController::class, 'update'])->whereNumber('school')->name('system.schools.update');
    Route::get('/sistem/cadangan', [BackupController::class, 'index'])->name('system.backups');
    Route::post('/sistem/cadangan/periksa', [BackupController::class, 'checkRestore'])->middleware('throttle:3,1')->name('system.backups.check');
    Route::post('/cadangan', [BackupController::class, 'backup'])->middleware('throttle:3,1')->name('v3.backup');
});
