<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TrainingController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
});

Route::middleware(['auth', 'account'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/ganti-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/ganti-password', [AuthController::class, 'changePassword'])->name('password.update');

    Route::get('/', fn () => redirect(AuthController::home(auth()->user())))->name('home');

    // Portal karyawan: hanya data milik akun yang login (tanpa ID karyawan di URL).
    Route::get('/saya', [PortalController::class, 'index'])->name('portal.index');

    // Pengalaman kerja: karyawan (milik sendiri) dan HR (semua) — otorisasi lewat policy.
    Route::post('/pengalaman', [ExperienceController::class, 'store'])->name('experiences.store');
    Route::put('/pengalaman/{experience}', [ExperienceController::class, 'update'])->name('experiences.update');
    Route::delete('/pengalaman/{experience}', [ExperienceController::class, 'destroy'])->name('experiences.destroy');

    Route::middleware('role:HR')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/karyawan', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/karyawan/baru', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/karyawan', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/karyawan/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::put('/karyawan/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::post('/karyawan/{employee}/status', [EmployeeController::class, 'toggleStatus'])->name('employees.status');

        Route::post('/karyawan/{employee}/pendidikan', [EducationController::class, 'store'])->name('education.store');
        Route::put('/pendidikan/{education}', [EducationController::class, 'update'])->name('education.update');
        Route::delete('/pendidikan/{education}', [EducationController::class, 'destroy'])->name('education.destroy');

        Route::post('/karyawan/{employee}/bahasa', [LanguageController::class, 'store'])->name('languages.store');
        Route::delete('/bahasa/{language}', [LanguageController::class, 'destroy'])->name('languages.destroy');

        Route::post('/karyawan/{employee}/training', [TrainingController::class, 'store'])->name('trainings.store');
        Route::put('/training/{training}', [TrainingController::class, 'update'])->name('trainings.update');
        Route::delete('/training/{training}', [TrainingController::class, 'destroy'])->name('trainings.destroy');

        Route::post('/karyawan/{employee}/penugasan', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::put('/penugasan/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/penugasan/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        Route::get('/karyawan/{employee}/foto', [PhotoController::class, 'show'])->name('photos.show');
        Route::post('/karyawan/{employee}/foto', [PhotoController::class, 'store'])->name('photos.store');
        Route::delete('/karyawan/{employee}/foto', [PhotoController::class, 'destroy'])->name('photos.destroy');

        Route::get('/proyek', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/proyek', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/proyek/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::put('/proyek/{project}', [ProjectController::class, 'update'])->name('projects.update');

        Route::get('/import', [ImportController::class, 'index'])->name('imports.index');
        Route::get('/import/template', [ImportController::class, 'template'])->name('imports.template');
        Route::get('/export', [ImportController::class, 'export'])->name('imports.export');
        Route::post('/import', [ImportController::class, 'upload'])->name('imports.upload');
        Route::get('/import/{batch}', [ImportController::class, 'show'])->name('imports.show');
        Route::post('/import/{batch}/proses', [ImportController::class, 'process'])->name('imports.process');
        Route::post('/import/{batch}/batal', [ImportController::class, 'cancel'])->name('imports.cancel');
        Route::get('/import/{batch}/laporan', [ImportController::class, 'report'])->name('imports.report');

        Route::get('/cv', [CvController::class, 'index'])->name('cv.index');
        Route::get('/cv/{employee}', [CvController::class, 'show'])->name('cv.show');
        Route::get('/cv/{employee}/preview', [CvController::class, 'preview'])->name('cv.preview');
        Route::get('/cv/{employee}/pdf', [CvController::class, 'pdf'])->name('cv.pdf');

        Route::get('/akun', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/akun', [AccountController::class, 'store'])->name('accounts.store');
        Route::post('/akun/massal', [AccountController::class, 'bulk'])->name('accounts.bulk');
        Route::post('/akun/{account}/reset', [AccountController::class, 'reset'])->name('accounts.reset');
        Route::post('/akun/{account}/status', [AccountController::class, 'toggleStatus'])->name('accounts.status');

        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
