<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MosqueController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\NeedRequestController;
use App\Http\Controllers\TankImageController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\AdsController;
use App\Http\Controllers\ContentTextController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\InvestorImpactController;
use App\Http\Controllers\ContactController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/users', [AuthController::class, 'getUsers'])->middleware('auth:api');
    Route::post('/users', [AuthController::class, 'createUser'])->middleware('auth:api');
    Route::get('/users/{id}', [AuthController::class, 'showUser'])->middleware('auth:api');
    Route::put('/users/{id}', [AuthController::class, 'updateUser'])->middleware('auth:api');
    Route::put('/users/{id}/role', [AuthController::class, 'updateUserRole'])->middleware('auth:api');
    Route::put('/users/{id}/toggle-status', [AuthController::class, 'toggleUserStatus'])->middleware('auth:api');
    Route::delete('/users/{id}', [AuthController::class, 'deleteUser'])->middleware('auth:api');

});

// Mosques routes
Route::get('/mosques', [MosqueController::class, 'index'])->name('mosques.index');
Route::get('/mosques/active-count', [MosqueController::class, 'getActiveMosquesCount'])->middleware('auth:api')->name('mosques.active-count');
Route::get('/mosques/{id}', [MosqueController::class, 'show'])->name('mosques.show');
Route::post('/mosques', [MosqueController::class, 'store'])->middleware('auth:api')->name('mosques.store');
Route::put('/mosques/{id}', [MosqueController::class, 'update'])->middleware('auth:api')->name('mosques.update');
Route::delete('/mosques/{id}', [MosqueController::class, 'destroy'])->middleware('auth:api')->name('mosques.destroy');
Route::get('/mosque-admins', [MosqueController::class, 'getMosqueAdmins'])->middleware('auth:api');

// Donations routes
Route::get('/donations', [DonationController::class, 'index'])->name('donations.index');
Route::get('/donations/my-history', [DonationController::class, 'myHistory'])->middleware('auth:api')->name('donations.my-history');
Route::get('/donations/count', [DonationController::class, 'getStats'])->middleware('auth:api')->name('donations.stats');
Route::get('/donations/{id}', [DonationController::class, 'show'])->name('donations.show');
Route::post('/donations', [DonationController::class, 'store'])->middleware('auth:api')->name('donations.store');
Route::put('/donations/{id}/verify', [DonationController::class, 'verify'])->middleware('auth:api')->name('donations.verify');
Route::put('/donations/{id}/status', [DonationController::class, 'updateStatus'])->middleware('auth:api')->name('donations.update-status');

// Need Requests routes
Route::get('/need-requests', [NeedRequestController::class, 'index'])->middleware('auth:api')->name('need-requests.index');
Route::get('/need-requests/my-mosque', [NeedRequestController::class, 'myMosque'])->middleware('auth:api')->name('need-requests.my-mosque');
Route::get('/need-requests/mosque/{mosqueId}', [NeedRequestController::class, 'getByMosque'])->middleware('auth:api')->name('need-requests.by-mosque');
Route::get('/need-requests/{id}', [NeedRequestController::class, 'show'])->middleware('auth:api')->name('need-requests.show');
Route::post('/need-requests', [NeedRequestController::class, 'store'])->middleware('auth:api')->name('need-requests.store');
Route::put('/need-requests/{id}/approve', [NeedRequestController::class, 'approve'])->middleware('auth:api')->name('need-requests.approve');
Route::put('/need-requests/{id}/reject', [NeedRequestController::class, 'reject'])->middleware('auth:api')->name('need-requests.reject');

// Tank Images routes
Route::get('/tank-images', [TankImageController::class, 'index'])->name('tank-images.index');
Route::get('/tank-images/my-mosque', [TankImageController::class, 'myMosque'])->middleware('auth:api')->name('tank-images.my-mosque');
Route::post('/tank-images', [TankImageController::class, 'store'])->middleware('auth:api')->name('tank-images.store');
Route::delete('/tank-images/{id}', [TankImageController::class, 'destroy'])->middleware('auth:api')->name('tank-images.destroy');

// Trucks routes
Route::get('/trucks', [TruckController::class, 'index'])->middleware('auth:api')->name('trucks.index');
Route::get('/trucks/drivers', [TruckController::class, 'getDrivers'])->middleware('auth:api')->name('trucks.drivers');
Route::get('/trucks/my-trucks', [TruckController::class, 'getMyTrucks'])->middleware('auth:api')->name('trucks.my-trucks');
Route::get('/trucks/{id}', [TruckController::class, 'show'])->middleware('auth:api')->name('trucks.show');
Route::post('/trucks', [TruckController::class, 'store'])->middleware('auth:api')->name('trucks.store');
Route::put('/trucks/{id}', [TruckController::class, 'update'])->middleware('auth:api')->name('trucks.update');
Route::put('/trucks/{id}/location', [TruckController::class, 'updateLocation'])->middleware('auth:api')->name('trucks.update-location');
Route::delete('/trucks/{id}', [TruckController::class, 'destroy'])->middleware('auth:api')->name('trucks.destroy');

// Deliveries routes
Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
Route::get('/deliveries/{id}', [DeliveryController::class, 'show'])->name('deliveries.show');
Route::post('/deliveries', [DeliveryController::class, 'store'])->middleware('auth:api')->name('deliveries.store');
Route::put('/deliveries/{id}/status', [DeliveryController::class, 'updateStatus'])->middleware('auth:api')->name('deliveries.update-status');
Route::post('/deliveries/{id}/proof', [DeliveryController::class, 'uploadProof'])->middleware('auth:api')->name('deliveries.upload-proof');

// Campaigns routes
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/{id}', [CampaignController::class, 'show'])->name('campaigns.show');
Route::post('/campaigns', [CampaignController::class, 'store'])->middleware('auth:api')->name('campaigns.store');
Route::put('/campaigns/{id}', [CampaignController::class, 'update'])->middleware('auth:api')->name('campaigns.update');
Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy'])->middleware('auth:api')->name('campaigns.destroy');

// Ads routes
Route::get('/ads', [AdsController::class, 'index'])->name('ads.index');
Route::get('/ads/{id}', [AdsController::class, 'show'])->name('ads.show');
Route::post('/ads', [AdsController::class, 'store'])->middleware('auth:api')->name('ads.store');
Route::put('/ads/{id}', [AdsController::class, 'update'])->middleware('auth:api')->name('ads.update');
Route::delete('/ads/{id}', [AdsController::class, 'destroy'])->middleware('auth:api')->name('ads.destroy');

// Content Texts routes
Route::get('/content-texts', [ContentTextController::class, 'index'])->name('content-texts.index');
Route::get('/content-texts/{key}', [ContentTextController::class, 'show'])->name('content-texts.show');
Route::post('/content-texts', [ContentTextController::class, 'store'])->middleware('auth:api')->name('content-texts.store');
Route::put('/content-texts/{key}', [ContentTextController::class, 'update'])->middleware('auth:api')->name('content-texts.update');
Route::delete('/content-texts/{key}', [ContentTextController::class, 'destroy'])->middleware('auth:api')->name('content-texts.destroy');

// Dashboard routes
Route::prefix('dashboard')->middleware('auth:api')->group(function () {
    Route::get('/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/activities', [DashboardController::class, 'activities'])->name('dashboard.activities');
    Route::get('/donation-activity', [DashboardController::class, 'donationActivity'])->name('dashboard.donation-activity');
    Route::get('/admin-summary', [DashboardController::class, 'adminSummary'])->name('dashboard.admin-summary');
});
Route::get('/landing-page', [DashboardController::class, 'landingPage'])->name('landing-page');

// Contact routes (Public - no auth required)
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

// Reports routes
Route::prefix('reports')->middleware('auth:api')->group(function () {
    Route::get('/donation-ledger', [ReportsController::class, 'donationLedger'])->name('reports.donation-ledger');
    Route::get('/export/pdf', [ReportsController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/export/excel', [ReportsController::class, 'exportExcel'])->name('reports.export.excel');
});

// Investor Impact routes
Route::prefix('investor-impact')->middleware('auth:api')->group(function () {
    Route::get('/metrics', [InvestorImpactController::class, 'metrics'])->name('investor-impact.metrics');
    Route::get('/funnel', [InvestorImpactController::class, 'funnel'])->name('investor-impact.funnel');
});
