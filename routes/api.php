<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\OtpController;
use App\Http\Controllers\PatientImplantController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminController;

use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\DistController;



use App\Http\Controllers\PatientAuth\PatientAuthController;

// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/email-login', [AuthController::class, 'login']);

Route::post('/send-otp', [OtpController::class, 'sendOtp']);
Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);

// Route::post('/phone-login', [OtpController::class, 'phoneLogin']);
// Route::post('/phone-login/verify', [OtpController::class, 'verifyPhoneLogin']);




Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/patient-implant', [PatientImplantController::class, 'store']);
});




Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin-only', function () {
    return response()->json(['message' => 'Admin access only']);
});



// SERVICE ENGINEER ROUTES
Route::middleware(['auth:sanctum', 'role:sales-representative'])->group(function () {
    Route::get('/service-engineer-only', function () {
        return response()->json(['message' => 'Service Engineer access only']);
    });

    Route::post('/service-engineer/new-implant', [PatientImplantController::class, 'submitByEngineer']);
    Route::get('/service-engineer/implant-list', [PatientImplantController::class, 'getEngineerImplants']);
    Route::post('/service-engineer/implant-replacement', [PatientImplantController::class, 'submitReplacement']);
    Route::get('/service-engineer/get-implant-details/{ipg_serial_number}', [PatientImplantController::class, 'getPatientDetailsByIpg']);

});


Route::post('/patient/register', [PatientAuthController::class, 'registerPatient']);
Route::post('/patient/login-email', [PatientAuthController::class, 'loginWithEmail']);

Route::post('/patient/login-phone', [PatientAuthController::class, 'loginWithPhone']);


Route::post('/patient/logout',[PatientAuthController::class, 'logout']);


Route::post('/admin/login', [AdminAuthController::class, 'login']);


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Admin access only']);
    });
    
    // Add new employee management routes
    Route::post('/admin/add-employee', [AdminController::class, 'addEmployee']);
    Route::get('/admin/employees', [AdminController::class, 'listEmployees']);

    // route::get('/admin/distributors', [DistController::class, 'listRequests']); 
    // Route::get('/admin/distributors/sales-representatives', [DistController::class, 'listSalesRepresentatives']);   
    // Route::post('/admin/distributors/assign-engineer', [DistController::class, 'assignServiceEngineer']);   
    // Route::get('/admin/distributors/replacement/{id}', [DistController::class, 'getReplacementDetails']); 

    // Route::post('/admin/distributors/replacement/assign-ipg-serial', [DistController::class, 'assignNewIpgSerialNumber']);


   
});

Route::middleware(['auth:sanctum', 'role:distributor'])->group(function () {
    Route::get('/admin/distributors', [DistController::class, 'listRequests']); 
    Route::get('/admin/distributors/sales-representatives', [DistController::class, 'listSalesRepresentatives']);   
    Route::post('/admin/distributors/assign-engineer', [DistController::class, 'assignServiceEngineer']);   
    Route::get('/admin/distributors/replacement/{id}', [DistController::class, 'getReplacementDetails']); 
    Route::post('/admin/distributors/replacement/assign-ipg-serial', [DistController::class, 'assignNewIpgSerialNumber']);
});



//Replacement routes

Route::get('patient/warranty-status', [PatientImplantController::class, 'getWarrantyStatus'])
 ->middleware('auth:sanctum');

 Route::middleware('auth:sanctum')->group(function () {

    Route::get('patient/warranty-status', [PatientImplantController::class, 'getWarrantyStatus']);
    Route::post('/patient/replacement-request', [PatientImplantController::class, 'requestReplacement']);

    Route::get('/patient/replacement-request/status', [PatientImplantController::class, 'getReplacementStatus']);




    
});




Route::middleware(['auth:sanctum', 'role:admin'])
->get('/admin/replacement-requests', [TicketController::class, 'getAllReplacementRequests']);

Route::middleware(['auth:sanctum', 'role:admin'])
->get('/admin/pending-replacement-requests', [TicketController::class, 'getPendingReplacementRequests']);
Route::middleware(['auth:sanctum', 'role:admin'])
->get('/admin/replacement-requests/{id}', [TicketController::class, 'getReplacementRequestDetails']);

Route::middleware(['auth:sanctum','role:admin'])
->post('/admin/replacement-requests/{id}/decision', [TicketController::class, 'decideReplacementRequest']);