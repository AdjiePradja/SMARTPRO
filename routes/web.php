<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserApprovalController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

/*
|--------------------------------------------------------------------------
| Guest routes (login / registration)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);
});

/*|--------------------------------------------------------------------------

| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Accessible to authenticated-but-inactive accounts (pending/rejected).
    Route::get('pending', [PageController::class, 'pending'])->name('pending');

    // Everything below requires an active account.
    Route::middleware('active')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // In-app notifications (bell)
        Route::get('notifications/{id}/open', [\App\Http\Controllers\NotificationController::class, 'open'])->name('notifications.open');
        Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.readAll');

        // Documents (Fase 2)
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::middleware('can:document.create')->group(function () {
            Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
            Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        });
        Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::post('documents/{document}/step', [DocumentController::class, 'saveStep'])->name('documents.saveStep');
        Route::post('documents/{document}/autosave', [DocumentController::class, 'autosave'])->name('documents.autosave');
        Route::get('documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
        Route::get('documents/{document}/pdf', [DocumentController::class, 'pdf'])->name('documents.pdf');
        Route::post('documents/{document}/submit', [DocumentController::class, 'submit'])->name('documents.submit');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        // Dokumen Revisi — rejected docs (Revisi Tipe A, Fase 4)
        Route::get('dokumen-revisi', [DocumentController::class, 'revisi'])->name('documents.revisi');

        // Dokumen Berlaku + Ajukan Revisi (Tipe B, Fase 5)
        Route::get('dokumen-berlaku', [DocumentController::class, 'published'])->name('documents.published');
        Route::post('documents/{document}/request-revision', [DocumentController::class, 'requestRevision'])
            ->middleware('can:document.request_revision')->name('documents.requestRevision');

        // Peninjauan (Fase 4)
        Route::middleware('can:document.review')->group(function () {
            Route::get('review', [ReviewController::class, 'index'])->name('review.index');
            Route::get('review/{document}', [ReviewController::class, 'show'])->name('review.show');
            Route::post('review/{document}', [ReviewController::class, 'store'])->name('review.store');
            Route::post('review/{document}/ai', [ReviewController::class, 'aiAnalyze'])->name('review.ai');
        });

        // Persetujuan (Fase 4)
        Route::middleware('can:document.approve')->group(function () {
            Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
            Route::get('approvals/{document}', [ApprovalController::class, 'show'])->name('approvals.show');
            Route::post('approvals/{document}', [ApprovalController::class, 'store'])->name('approvals.store');
        });

        // Registration approval (Task 1.5)
        Route::middleware('can:user.approve_registration')->group(function () {
            Route::get('users/pending', [UserApprovalController::class, 'index'])->name('users.pending');
            Route::post('users/{user}/approve', [UserApprovalController::class, 'approve'])->name('users.approve');
            Route::post('users/{user}/reject', [UserApprovalController::class, 'reject'])->name('users.reject');
        });

        // Admin user management (Task 1.6)
        Route::middleware('can:user.manage')->group(function () {
            Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
            Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
            Route::post('users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggleStatus');
        });
    });
});
