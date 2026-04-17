<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

// 1. Routes công khai
Route::post('/login', [AuthController::class, 'login']);

// 2. Routes cần đăng nhập
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- COURSES ---
    Route::get('/courses', [CourseController::class, 'index']);
    
    Route::middleware('role:teacher,admin')->group(function () {
        Route::post('/courses', [CourseController::class, 'store']);
    });

    // --- SUBMISSIONS ---
    Route::post('/submissions/upload', [SubmissionController::class, 'submit'])
         ->middleware('role:student');

    Route::post('/submissions/{id}/grade', [SubmissionController::class, 'grade'])
         ->middleware('role:teacher');
});