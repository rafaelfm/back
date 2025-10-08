<?php

use App\Http\Controllers\DestinationController;
use App\Http\Controllers\TravelRequestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [UserController::class, 'login']);
Route::get('user', [UserController::class, 'show']);

Route::middleware('auth.jwt')->group(function (): void {
    Route::get('destinations', [DestinationController::class, 'index']);
    Route::get('travel-requests', [TravelRequestController::class, 'index']);
    Route::post('travel-requests', [TravelRequestController::class, 'store']);
    Route::get('travel-requests/{travelRequest}', [TravelRequestController::class, 'show']);
    Route::put('travel-requests/{travelRequest}', [TravelRequestController::class, 'update']);
    Route::patch('travel-requests/{travelRequest}/status', [TravelRequestController::class, 'updateStatus']);
    Route::delete('travel-requests/{travelRequest}', [TravelRequestController::class, 'destroy']);
});
