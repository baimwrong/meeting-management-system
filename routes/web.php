<?php

use App\Http\Controllers\ActionItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\MeetingAttachmentController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingMinuteController;
use App\Http\Controllers\MeetingRoomController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Authentication
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Meetings
    Route::get('/meetings/calendar', [MeetingController::class, 'calendar'])->name('meetings.calendar');
    Route::get('/meetings/check-availability', [MeetingController::class, 'checkRoomAvailability'])->name('meetings.check-availability');
    Route::get('/meetings/{meeting}/print-mom', [MeetingMinuteController::class, 'printView'])->name('meetings.print-mom');
    Route::post('/meetings/{meeting}/rsvp', [MeetingController::class, 'updateRsvp'])->name('meetings.rsvp');
    Route::patch('/meetings/{meeting}/cancel', [MeetingController::class, 'cancel'])->name('meetings.cancel');
    Route::patch('/meetings/{meeting}/status', [MeetingController::class, 'updateStatus'])->name('meetings.status');
    Route::post('/meetings/{meeting}/attendance', [MeetingController::class, 'updateAttendance'])->name('meetings.attendance');
    Route::post('/meetings/{meeting}/minutes', [MeetingMinuteController::class, 'store'])->name('meetings.minutes.store');
    Route::post('/meetings/{meeting}/action-items', [ActionItemController::class, 'store'])->name('meetings.action-items.store');
    Route::post('/meetings/{meeting}/attachments', [MeetingAttachmentController::class, 'store'])->name('meetings.attachments.store');
    Route::resource('meetings', MeetingController::class);

    // Attachments
    Route::get('/attachments/{attachment}/download', [MeetingAttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [MeetingAttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Meeting Rooms
    Route::resource('rooms', MeetingRoomController::class);

    // Action Items / Tasks
    Route::get('/action-items', [ActionItemController::class, 'index'])->name('action-items.index');
    Route::patch('/action-items/{actionItem}/status', [ActionItemController::class, 'updateStatus'])->name('action-items.status');
    Route::delete('/action-items/{actionItem}', [ActionItemController::class, 'destroy'])->name('action-items.destroy');

    // Admin Only Routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // Bulk Data Import
        Route::get('/bulk-import', [BulkImportController::class, 'index'])->name('bulk-import.index');
        Route::post('/bulk-import', [BulkImportController::class, 'import'])->name('bulk-import.store');
        Route::get('/bulk-import/template/{type}', [BulkImportController::class, 'downloadTemplate'])->name('bulk-import.template');
        Route::get('/bulk-import/{log}', [BulkImportController::class, 'show'])->name('bulk-import.show');

        // User Management
        Route::resource('users', UserController::class);

        // Department Management
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    });
});
