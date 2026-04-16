<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\EmailAccountController;
use App\Http\Controllers\EmailWarmupController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\CampaignAnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;

// Stateless API auth routes (no session middleware)
Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('logout');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Authenticated routes (all routes require auth:sanctum middleware)
Route::middleware(['auth:sanctum'])->group(function () {
    // User endpoint
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Get current user's team
    Route::get('/teams/current', function (Request $request) {
        $user = $request->user();
        $team = $user->teams()->first() ?? $user->teamMemberships()->first();
        return response()->json(['team' => $team]);
    });

    // Team management
    Route::post('/teams', [TeamController::class, 'create']);
    Route::post('/teams/{team}/invite', [TeamController::class, 'invite']);
    Route::get('/teams/{team}/members', [TeamController::class, 'members']);
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember']);

    // Campaign management
    Route::apiResource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/launch', [CampaignController::class, 'launch']);
    Route::get('campaigns/{campaign}/analytics', [CampaignAnalyticsController::class, 'show']);

    // Lead management
    Route::apiResource('leads', LeadController::class);

    // Email account management
    Route::apiResource('email-accounts', EmailAccountController::class);

    // Warmup system
    Route::get('warmup', [EmailWarmupController::class, 'index']);
    Route::post('warmup/{emailAccount}/toggle', [EmailWarmupController::class, 'toggle']);

    // Unified inbox
    Route::get('inbox', [ReplyController::class, 'index']);
    Route::get('inbox/{reply}', [ReplyController::class, 'show']);
    Route::post('inbox/{reply}/read', [ReplyController::class, 'markRead']);
    Route::post('inbox/{reply}/reply', [ReplyController::class, 'reply']);

    // Dashboard analytics
    Route::get('dashboard', [DashboardController::class, 'index']);
});
