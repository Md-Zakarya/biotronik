<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminIdRequestController; 

Route::get('/', function () {
    return view('welcome');
});



Route::get('/id-requests-demo', [AdminIdRequestController::class, 'showPublicIdRequests'])->name('admin.id-requests.demo.index'); // Example method name
Route::put('/admin/id-requests-demo/{id}/status', [AdminIdRequestController::class, 'updatePublicIdRequestStatus'])->name('admin.id-requests.demo.updateStatus');