<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V3Controller as V;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\Auth\AuthenticatedSessionController as Auth;
use App\Http\Controllers\Auth\PasswordController;
Route::get('/',fn()=>redirect()->route(auth()->user()?->isSystemAdmin() ? 'system.schools' : 'dashboard'));
Route::middleware('guest')->group(function(){
 Route::get('/pengaturan-awal',[SetupController::class,'create'])->name('setup.create');
 Route::post('/pengaturan-awal',[SetupController::class,'store'])->middleware('throttle:6,1')->name('setup.store');
 Route::get('/login',[Auth::class,'create'])->name('login');
 Route::post('/login',[Auth::class,'store']);
});
Route::post('/logout',[Auth::class,'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth','active','school.active'])->group(function(){
 Route::get('/dashboard',[V::class,'index'])->name('dashboard');
 Route::get('/persetujuan',[\App\Http\Controllers\ApprovalController::class,'index'])->name('v3.approvals');
 Route::post('/kegiatan',[V::class,'activityStore'])->name('v3.activity.store');
 Route::get('/kegiatan/{id}',[V::class,'activity'])->whereNumber('id')->name('v3.activity');
 Route::get('/kegiatan/{id}/dokumen.pdf',[V::class,'activityDocuments'])->whereNumber('id')->name('v3.activity.documents');
 Route::get('/kegiatan/{activity}/belanja/baru',[V::class,'form'])->whereNumber('activity')->name('v3.create');
 Route::post('/kegiatan/{activity}/belanja',[V::class,'save'])->whereNumber('activity')->name('v3.store');
 Route::get('/kegiatan/{activity}/belanja/{id}/edit',[V::class,'form'])->whereNumber(['activity','id'])->name('v3.edit');
 Route::put('/kegiatan/{activity}/belanja/{id}',[V::class,'save'])->whereNumber(['activity','id'])->name('v3.update');
 Route::get('/belanja/{id}',[V::class,'purchase'])->whereNumber('id')->name('v3.purchase');
 Route::post('/belanja/{id}/dokumen',[V::class,'issue'])->whereNumber('id')->name('v3.issue');
 Route::post('/belanja/{id}/status',[V::class,'transition'])->whereNumber('id')->name('v3.transition');
 Route::post('/belanja/{id}/bukti',[V::class,'upload'])->whereNumber('id')->name('v3.upload');
 Route::get('/bukti/{id}',[V::class,'attachment'])->whereNumber('id')->name('v3.attachment');
 Route::get('/dokumen/{id}',[V::class,'document'])->whereNumber('id')->name('v3.document');
 Route::post('/dokumen/{id}/batalkan',[V::class,'cancelDocument'])->whereNumber('id')->name('v3.document.cancel');
 Route::get('/pengaturan',[V::class,'settings'])->name('v3.settings');
 Route::post('/pengaturan',[V::class,'settingsSave'])->name('v3.settings.save');
 Route::get('/logo/{kind}',[V::class,'logo'])->name('v3.logo');
 Route::get('/anggaran',[V::class,'budgets'])->name('v3.budgets');
 Route::post('/anggaran',[V::class,'budgetSave'])->name('v3.budgets.save');
 Route::get('/anggaran/{id}/edit',[V::class,'budgetEdit'])->whereNumber('id')->name('v3.budgets.edit');
 Route::put('/anggaran/{id}',[V::class,'budgetUpdate'])->whereNumber('id')->name('v3.budgets.update');
 Route::delete('/anggaran/{id}',[V::class,'budgetDestroy'])->whereNumber('id')->name('v3.budgets.destroy');
 Route::get('/akun',[V::class,'accounts'])->name('admin.users.create');
 Route::post('/akun',[V::class,'accountSave'])->name('v3.accounts.save');
 Route::patch('/akun/{id}',[V::class,'accountUpdate'])->whereNumber('id')->name('v3.accounts.update');
 Route::put('/password',[PasswordController::class,'update'])->name('password.update');
 Route::get('/laporan',[V::class,'reports'])->name('v3.reports');
 Route::get('/laporan.xlsx',[V::class,'excel'])->name('v3.excel');
 Route::get('/laporan.csv',[V::class,'csv'])->name('v3.csv');

});

Route::middleware(['auth','active'])->group(function(){
 Route::get('/sistem/sekolah',[\App\Http\Controllers\SchoolAdminController::class,'index'])->name('system.schools');
 Route::post('/sistem/sekolah',[\App\Http\Controllers\SchoolAdminController::class,'store'])->middleware('throttle:10,1')->name('system.schools.store');
 Route::patch('/sistem/sekolah/{school}',[\App\Http\Controllers\SchoolAdminController::class,'update'])->whereNumber('school')->name('system.schools.update');
 Route::post('/cadangan',[V::class,'backup'])->middleware('throttle:3,1')->name('v3.backup');
});
