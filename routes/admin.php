<?php

use App\Http\Controllers\Admin\CaptureController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DeviceController;
use App\Http\Controllers\Admin\FindingController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');

Route::resource('clients', ClientController::class)
    ->parameters(['clients' => 'client']);

// Equipos (Fase 5)
Route::get('devices/{device}/compare', [DeviceController::class, 'compare'])->name('devices.compare');
Route::resource('devices', DeviceController::class)
    ->only(['index', 'show', 'edit', 'update'])
    ->parameters(['devices' => 'device']);

Route::get('captures/status', [CaptureController::class, 'status'])->name('captures.status');
Route::resource('captures', CaptureController::class)
    ->only(['index', 'create', 'store', 'show', 'destroy'])
    ->parameters(['captures' => 'capture']);

// Reportes (Fase 4)
Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('captures/{capture}/report', [ReportController::class, 'forCapture'])->name('reports.for-capture');
Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
Route::put('reports/{report}', [ReportController::class, 'update'])->name('reports.update');
Route::post('reports/{report}/issue', [ReportController::class, 'issue'])->name('reports.issue');
Route::post('reports/{report}/new-version', [ReportController::class, 'newVersion'])->name('reports.new-version');
Route::get('reports/{report}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

// Dictamen metodológico (entregable al cliente)
Route::get('methodology', [\App\Http\Controllers\Admin\MethodologyController::class, 'show'])->name('methodology');
Route::get('methodology/pdf', [\App\Http\Controllers\Admin\MethodologyController::class, 'pdf'])->name('methodology.pdf');

// Gestión de usuarios (solo admin)
Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
    ->except(['show'])
    ->parameters(['users' => 'user']);

// Administración (Fase 7)
Route::get('rules', [\App\Http\Controllers\Admin\AnalyzerRuleController::class, 'index'])->name('rules.index');
Route::put('rules/{rule}', [\App\Http\Controllers\Admin\AnalyzerRuleController::class, 'update'])->name('rules.update');
Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
Route::put('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
Route::get('audit', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit.index');
Route::view('api-docs', 'admin.api-docs')->name('api-docs');

// Notificaciones (Fase 6)
Route::post('notifications/read-all', function () {
    auth()->user()->unreadNotifications->markAsRead();

    return back();
})->name('notifications.read-all');

// Exportación (Fase 6)
Route::get('captures/{capture}/export/json', [\App\Http\Controllers\Admin\ExportController::class, 'json'])->name('captures.export.json');
Route::get('captures/{capture}/export/excel', [\App\Http\Controllers\Admin\ExportController::class, 'excel'])->name('captures.export.excel');

// Hallazgos editables (Fase 4)
Route::post('captures/{capture}/findings', [FindingController::class, 'store'])->name('findings.store');
Route::put('findings/{finding}', [FindingController::class, 'update'])->name('findings.update');
Route::delete('findings/{finding}', [FindingController::class, 'destroy'])->name('findings.destroy');
Route::post('findings/{finding}/attachments', [FindingController::class, 'storeAttachment'])->name('findings.attachments.store');
Route::delete('finding-attachments/{attachment}', [FindingController::class, 'destroyAttachment'])->name('findings.attachments.destroy');
