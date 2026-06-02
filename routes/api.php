<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiUserController;
use App\Http\Controllers\Api\ApiStudentController;
use App\Http\Controllers\Api\ApiClassSectionSubjectController;
use App\Http\Controllers\Api\ApiMarkAssessmentController;
use App\Http\Controllers\Api\ApiPaymentController;
use App\Http\Controllers\Api\ApiMessageController;
use App\Http\Controllers\Api\ApiTicketController;
use App\Http\Controllers\Api\ApiUtilityController;

/*
|--------------------------------------------------------------------------
| Tenant API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by bootstrap/app.php in the tenant context.
| They are prefixed with 'api/v1' and are fully multi-tenant aware.
|
*/

// Public Utility Routes (For registration/onboarding lists)
Route::prefix('utilities')->group(function () {
    Route::get('nationalities', [ApiUtilityController::class, 'nationalities']);
    Route::get('states/{nal_id}', [ApiUtilityController::class, 'states']);
    Route::get('lgas/{state_id}', [ApiUtilityController::class, 'lgas']);
});

// Authentication Routes
Route::post('auth/login', [ApiAuthController::class, 'login']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth Profile
    Route::prefix('auth')->group(function () {
        Route::post('logout', [ApiAuthController::class, 'logout']);
        Route::get('me', [ApiAuthController::class, 'me']);
        Route::put('profile', [ApiAuthController::class, 'updateProfile']);
        Route::put('password', [ApiAuthController::class, 'changePassword']);
    });

    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [ApiUserController::class, 'index']);
        Route::post('/', [ApiUserController::class, 'store']);
        Route::get('{id}', [ApiUserController::class, 'show']);
        Route::put('{id}', [ApiUserController::class, 'update']);
        Route::delete('{id}', [ApiUserController::class, 'destroy']);
        Route::post('{id}/reset-password', [ApiUserController::class, 'resetPassword']);
        Route::post('block-state', [ApiUserController::class, 'updateBlockedState']);
    });

    // Student Records
    Route::prefix('students')->group(function () {
        Route::get('/', [ApiStudentController::class, 'index']);
        Route::post('/', [ApiStudentController::class, 'store']);
        Route::get('{id}', [ApiStudentController::class, 'show']);
        Route::put('{id}', [ApiStudentController::class, 'update']);
        Route::delete('{id}', [ApiStudentController::class, 'destroy']);
        Route::post('block-class', [ApiStudentController::class, 'blockClass']);
    });

    // Classes, Sections, & Subjects
    Route::get('classes', [ApiClassSectionSubjectController::class, 'classes']);
    Route::get('sections/{class_id}', [ApiClassSectionSubjectController::class, 'sections']);
    Route::get('subjects/{class_id}', [ApiClassSectionSubjectController::class, 'subjects']);

    // Academic Marks & Exams
    Route::get('exams', [ApiMarkAssessmentController::class, 'exams']);
    Route::get('marks/manage/{exam_id}/{class_id}/{subject_id}/{section_id?}', [ApiMarkAssessmentController::class, 'getMarks']);
    Route::post('marks/update', [ApiMarkAssessmentController::class, 'updateMarks']);
    Route::get('tabulation/{exam_id}/{class_id}/{section_id}/{year}', [ApiMarkAssessmentController::class, 'tabulationSheet']);

    // Payments & Invoices
    Route::prefix('payments')->group(function () {
        Route::get('/', [ApiPaymentController::class, 'index']);
        Route::get('invoice/{student_id}/{year?}', [ApiPaymentController::class, 'invoice']);
        Route::post('pay/{pr_id}', [ApiPaymentController::class, 'payNow']);
        Route::get('receipt/{pr_id}', [ApiPaymentController::class, 'receipt']);
    });

    // Messaging (Messenger)
    Route::prefix('messages')->group(function () {
        Route::get('/', [ApiMessageController::class, 'threads']);
        Route::get('{id}', [ApiMessageController::class, 'threadMessages']);
        Route::post('/', [ApiMessageController::class, 'store']);
        Route::post('{id}/reply', [ApiMessageController::class, 'reply']);
    });

    // Support Tickets (Helpdesk)
    Route::prefix('tickets')->group(function () {
        Route::get('/', [ApiTicketController::class, 'index']);
        Route::post('/', [ApiTicketController::class, 'store']);
        Route::get('{id}', [ApiTicketController::class, 'show']);
        Route::post('{id}/reply', [ApiTicketController::class, 'reply']);
        Route::post('{id}/close', [ApiTicketController::class, 'close']);
    });

    // Authorized Utilities
    Route::prefix('utilities')->group(function () {
        Route::get('class-students/{class_id}', [ApiUtilityController::class, 'classStudents']);
        Route::get('class-subjects/{class_id}', [ApiUtilityController::class, 'classSubjects']);
        Route::get('year-exams/{year}', [ApiUtilityController::class, 'yearExams']);
    });
});
