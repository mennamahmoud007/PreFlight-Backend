<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\ImprovementController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StressTestController;
use Illuminate\Support\Facades\Route;

Route::middleware('device')->group(function () {
    Route::apiResource('projects', ProjectController::class);
    Route::post('projects/{project}/analysis', [AnalysisController::class, 'store']);
    Route::post('projects/{project}/stress-test', [StressTestController::class, 'store']);
    Route::post('projects/{project}/improvement', [ImprovementController::class, 'store']);
    Route::patch('projects/{project}/improvement/{improvement}/status', [ImprovementController::class, 'updateStatus']);
});
