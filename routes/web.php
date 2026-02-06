<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Campaigns\CampaignList;
use App\Livewire\Campaigns\CampaignForm;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Auth-Protected Routes (require login + email verification)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    
    Route::get('/campaigns', function () {
        return view('campaigns.index');
    })->name('campaigns.index');

    Route::get('/campaigns/create', function () {
        return view('campaigns.create');
    })->name('campaigns.create');

    Route::get('/campaigns/{campaignId}/', function (int $campaignId) {
        return view('campaigns.edit', compact('campaignId'));
    })->name('campaigns.edit');


    Route::get('/subscribers', function () {
        return view('subscribers.index');
    })->name('subscribers.index');

    Route::get('/subscribers/create', function () {
        return view('subscribers.create');
    })->name('subscribers.create');

    Route::get('/subscribers/{subscriberId}/', function (int $subscriberId) {
        return view('subscribers.edit', compact('subscriberId'));
    })->name('subscribers.edit');
});

/*
|--------------------------------------------------------------------------
| Auth-Protected Routes (require login only)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
