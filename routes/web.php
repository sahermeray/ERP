<?php

use App\Http\Controllers\Administration\DepartmentController as AdministrationDepartmentController;
use App\Http\Controllers\Administration\OrganizationController as AdministrationOrganizationController;
use App\Http\Controllers\Administration\UserController as AdministrationUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DocumentController::class, 'dashboard'])->name('dashboard');
    Route::get('/documents/incoming', [DocumentController::class, 'index'])->defaults('direction', 'incoming')->name('documents.incoming.index');
    Route::get('/documents/outgoing', [DocumentController::class, 'index'])->defaults('direction', 'outgoing')->name('documents.outgoing.index');
    Route::get('/documents/{direction}/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents/{direction}', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->whereNumber('document')->name('documents.edit');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->whereNumber('document')->name('documents.update');
    Route::get('/documents/{document}/attachments/{attachment}/open', [DocumentController::class, 'openAttachment'])->whereNumber(['document', 'attachment'])->name('documents.attachments.open');
    Route::get('/documents/{document}/attachments/{attachment}/download', [DocumentController::class, 'downloadAttachment'])->whereNumber(['document', 'attachment'])->name('documents.attachments.download');
    Route::delete('/documents/{document}/attachments/{attachment}', [DocumentController::class, 'deleteAttachment'])->whereNumber(['document', 'attachment'])->name('documents.attachments.destroy');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->whereNumber('document')->name('documents.show');
    Route::get('/archive', [DocumentController::class, 'archive'])->name('documents.archive');

    Route::prefix('/administration')->name('admin.')->middleware('can:manage-administration')->group(function () {
        Route::get('/organization', [AdministrationOrganizationController::class, 'edit'])->name('organization.edit');
        Route::put('/organization', [AdministrationOrganizationController::class, 'update'])->name('organization.update');

        Route::get('/departments', [AdministrationDepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/create', [AdministrationDepartmentController::class, 'create'])->name('departments.create');
        Route::post('/departments', [AdministrationDepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/{department}/edit', [AdministrationDepartmentController::class, 'edit'])->whereNumber('department')->name('departments.edit');
        Route::put('/departments/{department}', [AdministrationDepartmentController::class, 'update'])->whereNumber('department')->name('departments.update');
        Route::patch('/departments/{department}/status', [AdministrationDepartmentController::class, 'updateStatus'])->whereNumber('department')->name('departments.status');
        Route::delete('/departments/{department}', [AdministrationDepartmentController::class, 'destroy'])->whereNumber('department')->name('departments.destroy');

        Route::get('/users', [AdministrationUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdministrationUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdministrationUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdministrationUserController::class, 'edit'])->whereNumber('user')->name('users.edit');
        Route::put('/users/{user}', [AdministrationUserController::class, 'update'])->whereNumber('user')->name('users.update');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
