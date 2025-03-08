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

use App\Http\Controllers\SE\ImplantController;

use App\Http\Controllers\PatientControllers\FollowUpController;

use App\Http\Controllers\Dist\FollowUpController as DistFollowUpController;
use App\Http\Controllers\SE\FollowUpController as SEFollowUpController;


use App\Http\Controllers\IpgDeviceController;

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


    Route::post('/service-engineer/upgrade-implant', [ImplantController::class, 'upgradeImplant']);

    Route::get('/service-engineer/follow-up-requests', [SEFollowUpController::class, 'getFollowUpRequests']);
    Route::get('/service-engineer/follow-up-requests/{id}', [SEFollowUpController::class, 'getFollowUpStatus']);
    Route::post('/service-engineer/follow-up-requests/{id}/complete', [SEFollowUpController::class, 'markAsComplete']);




    // Payment management routes for service engineers
    Route::post('/service-engineer/payment-request', [SEFollowUpController::class, 'createPatientPaymentRequest']);
    Route::post('/service-engineer/make-payment', [SEFollowUpController::class, 'makePayment']);

    Route::get('/service-engineer/payments/{paymentId}', [FollowUpController::class, 'getPaymentStatus']);


    Route::post('/service-engineer/follow-up-requests', [SEFollowUpController::class, 'createFollowUpRequest']);

    Route::get('/service-engineer/patient-details/{phone_number}', [SEFollowUpController::class, 'getPatientDetailsByPhone']);
    // Route::get('/service-engineer/payments/{paymentId}', [SEFollowUpController::class, 'getPaymentStatus']);

});


Route::post('/patient/register', [PatientAuthController::class, 'registerPatient']);
Route::post('/patient/login-email', [PatientAuthController::class, 'loginWithEmail']);

Route::post('/patient/login-phone', [PatientAuthController::class, 'loginWithPhone']);


Route::post('/patient/logout', [PatientAuthController::class, 'logout']);


Route::post('/admin/login', [AdminAuthController::class, 'login']);


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Admin access only']);
    });

    // Add new employee management routes
    Route::post('/admin/add-employee', [AdminController::class, 'addEmployee']);
    Route::get('/admin/employees', [AdminController::class, 'listEmployees']);

    Route::patch('/admin/update-employee/{id}', [AdminController::class, 'updateEmployee']);
    Route::delete('/admin/delete-employee/{id}', [AdminController::class, 'deleteEmployee']);

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

    Route::get('/admin/distributors/pending-replacement-requests', [DistController::class, 'listAllPendingRequests']);


    /* FOLLOW UP SERVICES ROUTES*/

    // Get list of all pending follow-up requests
    Route::get('/admin/distributors/follow-up/requests', [DistFollowUpController::class, 'getFollowUpRequests']);
    // Get status of specific follow-up request
    Route::get('/admin/distributors/follow-up/{id}', [DistFollowUpController::class, 'getFollowUpStatus']);



    // Assign service engineer to a follow-up request
    Route::post('/admin/distributors/follow-up/{id}/assign', [DistFollowUpController::class, 'assignServiceEngineer']);
    Route::get('/admin/distributors/actionables', [DistController::class, 'getAllActionables']);
});


Route::get('/ipg-devices', [IpgDeviceController::class, 'index']);
Route::get('ipg-devices/{serialNumber}', [IpgDeviceController::class, 'getDeviceBySerialNumber']);
// Route::post('/ipg-devices', [IpgDeviceController::class, 'store']);
// Route::post('/ipg-devices/link', [IpgDeviceController::class, 'linkToPatient']);
// Route::post('/ipg-devices/unlink', [IpgDeviceController::class, 'unlinkFromPatient']);
// Route::get('/ipg-devices/available', [IpgDeviceController::class, 'getAvailableDevices']);


//Replacement routes

Route::get('patient/warranty-status', [PatientImplantController::class, 'getWarrantyStatus'])
    ->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('patient/warranty-status', [PatientImplantController::class, 'getWarrantyStatus']);
    Route::post('/patient/replacement-request', [PatientImplantController::class, 'requestReplacement']);

    Route::get('/patient/replacement-request/status', [PatientImplantController::class, 'getReplacementStatus']);


    Route::post('/patient/upgrade-implant', [PatientImplantController::class, 'linkPatientToImplant']);


    //          FOLLOW UP ROUTES
    Route::post('/patient/payment-details', [FollowUpController::class, 'submitPaymentDetails']);

    //payment done or not? how many request does the patient have that the patient has paid for? 
    Route::get('/patient/payment-history', [FollowUpController::class, 'getPatientPaymentHistory']);


    Route::post('/patient/follow-up-request', [FollowUpController::class, 'createFollowUpRequest']);
    Route::get('/patient/follow-up-status', [FollowUpController::class, 'getFollowUpStatus']);
    Route::get('/patient/get-user-details', [PatientImplantController::class, 'getUserDetails']);
    Route::get('/patient/has-implant', [PatientImplantController::class, 'checkIfPatientHasImplant']);
    Route::delete('/patient/follow-up-request', [FollowUpController::class, 'deleteFollowUpRequest']);



    Route::get('/patient/payment-status', [FollowUpController::class, 'checkPaymentStatus']);



});




Route::middleware(['auth:sanctum', 'role:admin'])
    ->get('/admin/replacement-requests', [TicketController::class, 'getAllReplacementRequests']);

Route::middleware(['auth:sanctum', 'role:admin'])
    ->get('/admin/pending-replacement-requests', [TicketController::class, 'getPendingReplacementRequests']);
Route::middleware(['auth:sanctum', 'role:admin'])
    ->get('/admin/replacement-requests/{id}', [TicketController::class, 'getReplacementRequestDetails']);

Route::middleware(['auth:sanctum', 'role:admin'])
    ->post('/admin/replacement-requests/{id}/decision', [TicketController::class, 'decideReplacementRequest']);