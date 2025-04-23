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
use App\Http\Controllers\Dist\IpgModelController;

use App\Http\Controllers\SE\FollowUpController as SEFollowUpController;

use App\Http\Controllers\Admin\LeadController;


use App\Http\Controllers\IpgDeviceController;

use App\Http\Controllers\PatientAuth\PatientAuthController;
use App\Http\Controllers\PatientControllers\WarrantyExtensionController;


use App\Http\Controllers\PatientControllers\BackupServiceController;
use App\Http\Controllers\PatientControllers\IdRequestController;


use App\Http\Controllers\Admin\YourActionables;
use App\Http\Controllers\SE\BackupServices;
use App\Http\Controllers\Admin\AdminIdRequestController;



// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/email-login', [AuthController::class, 'login']);

Route::post('/send-otp', [OtpController::class, 'sendOtp']);
Route::post('/send-login-otp', [OtpController::class, 'sendLoginOtp']);
Route::post('/send-registration-otp', [OtpController::class, 'sendRegistrationOtp']);
Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);

// Route::post('/phone-login', [OtpController::class, 'phoneLogin']);
// Route::post('/phone-login/verify', [OtpController::class, 'verifyPhoneLogin']);




Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/patient-implant', [PatientImplantController::class, 'store']);
    Route::post('/patient/register-profile', [PatientImplantController::class, 'updatePatientInfo']);
    Route::post('/patient/register-implant', [PatientImplantController::class, 'registerImplant']);


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
    Route::get('/service-engineer/implant-list/{id}', [PatientImplantController::class, 'getImplantAndPatientDetails']);
    Route::post('/service-engineer/implant-replacement', [PatientImplantController::class, 'submitReplacement']);
    Route::get('/service-engineer/get-implant-details/{ipg_serial_number}', [PatientImplantController::class, 'getPatientDetailsByIpg']);


    Route::post('/service-engineer/upgrade-implant', [ImplantController::class, 'upgradeImplant']);

    Route::get('/service-engineer/follow-up-requests', [SEFollowUpController::class, 'getFollowUpRequests']);
    Route::get('/service-engineer/follow-up-requests/{id}', [SEFollowUpController::class, 'getFollowUpStatus']);
    Route::post('/service-engineer/follow-up-requests/{id}/complete', [SEFollowUpController::class, 'markAsComplete']);




    // Payment management routes for service engineers
    Route::post('/service-engineer/payment-request', [SEFollowUpController::class, 'createPatientPaymentRequest']);
    Route::post('/service-engineer/make-payment', [SEFollowUpController::class, 'makePayment']);

    // Route::get('/service-engineer/payments/{paymentId}', [FollowUpController::class, 'getPaymentStatus']);


    Route::post('/service-engineer/follow-up-requests', [SEFollowUpController::class, 'createFollowUpRequest']);

    Route::get('/service-engineer/patient-details/{phone_number}', [SEFollowUpController::class, 'getPatientDetailsByPhone']);
    Route::get('/service-engineer/payments/{paymentId}', [SEFollowUpController::class, 'getPaymentStatus']);

    Route::get('/service-engineer/actionables', [SEFollowUpController::class, 'getAllActionables']);
    Route::get('/service-engineer/actionable-counts', [SEFollowUpController::class, 'getActionableCounts']);

    Route::get('/service-engineer/actionables', [SEFollowUpController::class, 'getAllActionables']);
    Route::get('/service-engineer/replacement-request/{id}', [SEFollowUpController::class, 'getReplacementDetails']);
    //implant replacement new assigned by the distrubuter
    Route::get('/service-engineer/assigned-ipg-serials', [SEFollowUpController::class, 'getAssignedIpgSerials']);


    //backup services 
    Route::get('/service-engineer/backup-service/{id}', [BackupServices::class, 'getBackupServiceDetails']);
    Route::post('/service-engineer/backup-service/{id}/complete', [BackupServices::class, 'completeBackupService']);

});

Route::middleware(['auth:sanctum', 'role:admin|logistics'])->group(function () {
    Route::post('/ipg-models/associate-serial', [IpgModelController::class, 'associateMultipleSerials']);
    Route::get('/admin/distributors-list', [AdminController::class, 'listDistributors']);

    Route::post('admin/ipg-serials/assign-distributor', [IpgModelController::class, 'assignDistributor']);
    Route::get('admin/ipg-serials', [IpgModelController::class, 'getAllSerials']);

    Route::get('/admin/device-types', [IpgModelController::class, 'getDeviceTypes']);
    Route::post('/admin/ipg-models', [IpgModelController::class, 'store']);
    Route::get('/admin/get-all-ipg-models', [IpgModelController::class, 'getAllIpgModels']);
    Route::get('/admin/ipg-serials/export', [IpgDeviceController::class, 'exportToExcel']);

    Route::get('/admin/ipg-models/export', [IpgDeviceController::class, 'exportModelDetails']);

    Route::post('/admin/leads/bulk-store', [LeadController::class, 'bulkUpload']);
    Route::get('/admin/leads', [LeadController::class, 'getLeads']);
    Route::get('/admin/lead-models', [LeadController::class, 'getLeadModels']);
    Route::post('/admin/lead-models', [LeadController::class, 'createLeadModel']);
    Route::post('/admin/lead-serials/assign-distributor', [LeadController::class, 'assignDistributor']);
    Route::get('/admin/leads/export', [LeadController::class, 'exportLeadsCSV']);
    Route::get('/admin/lead-models/export', [LeadController::class, 'exportLeadModelsCSV']);
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


    Route::get('/admin/dashboard-counts', [AdminController::class, 'getDashboardCounts']);


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


    //commented for testing purposes
    // Route::post('/ipg-models/associate-serial', [IpgModelController::class, 'associateMultipleSerials']);
    // Route::get('/admin/distributors-list', [AdminController::class, 'listDistributors']);

    // Route::post('admin/ipg-serials/assign-distributor', [IpgModelController::class, 'assignDistributor']);
    // Route::get('admin/ipg-serials', [IpgModelController::class, 'getAllSerials']);

    // Route::get('/admin/device-types', [IpgModelController::class, 'getDeviceTypes']);
    // Route::post('/admin/ipg-models', [IpgModelController::class, 'store']);
    // Route::get('/admin/get-all-ipg-models', [IpgModelController::class, 'getAllIpgModels']);
    // Route::get('/admin/ipg-serials/export', [IpgDeviceController::class, 'exportToExcel']);

    // Route::get('/admin/ipg-models/export', [IpgDeviceController::class, 'exportModelDetails']);

    // Route::post('/admin/leads/bulk-store', [LeadController::class, 'bulkUpload']);
    // Route::get('/admin/leads', [LeadController::class, 'getLeads']);
    // Route::get('/admin/lead-models', [LeadController::class, 'getLeadModels']);
    // Route::post('/admin/lead-models', [LeadController::class, 'createLeadModel']);
    // Route::post('/admin/lead-serials/assign-distributor', [LeadController::class, 'assignDistributor']);
    // Route::get('/admin/leads/export', [LeadController::class, 'exportLeadsCSV']);
    // Route::get('/admin/lead-models/export', [LeadController::class, 'exportLeadModelsCSV']);


 

    Route::get('/admin/your-actionables', [DistController::class, 'listAllPendingItems']);
    Route::get('/admin/pending-implant/{id}', [DistController::class, 'getPendingImplantDetails']);
    Route::put('/admin/pending-implants/{id}/approve', [DistController::class, 'approvePendingImplant']);
    Route::put('/admin/pending-implants/{id}/reject', [DistController::class, 'rejectPendingImplant']);

    //request ID flow
    Route::get('/admin/id-requests', [AdminIdRequestController::class, 'index']);
    Route::put('/admin/id-requests/{id}/status', [AdminIdRequestController::class, 'updateStatus']);
});

Route::middleware(['auth:sanctum', 'role:distributor'])->group(function () {
    Route::get('/admin/distributors/dashboard-counts', [DistController::class, 'getDashboardCounts']);
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


    Route::get('/admin/distributors/backup-service/{id}', [YourActionables::class, 'getBackupServiceDetails']);
    Route::post('/admin/distributors/backup-service/{id}/assign', [YourActionables::class, 'assignServiceEngineer']);
    Route::post('/admin/distributors/backup-service/{id}/confirm', [YourActionables::class, 'confirmBackupService']);

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



    Route::get('/patient/implant-details/{ipg_serial_number}', [PatientImplantController::class, 'getImplantDetailsBySerial']);


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




    //warrnaty extension 
    Route::post('patient/warranty-extension', [WarrantyExtensionController::class, 'extendWarranty']);
    Route::get('patient/warranty-details', [WarrantyExtensionController::class, 'getWarrantyDetails']);




    //backup Services

    Route::post('patient/register-backup-service', [BackupServiceController::class, 'store']);
    Route::get('/patient/backup-service/status', [BackupServiceController::class, 'getBackupServiceStatus']);
    Route::post('/patient/backup-service/cancel', [BackupServiceController::class, 'cancelBackupService']);
    Route::get('/Patient/current-implant', [PatientImplantController::class, 'getCurrentImplant']);


    //ID Request Routes patient section
    Route::post('/patient/id-request', [IdRequestController::class, 'submitRequest']);
    Route::get('/patient/id-request/status', [IdRequestController::class, 'trackRequest']);


});



Route::middleware(['auth:sanctum', 'role:logistics'])->group(function () {
    Route::get('/logistics-only', function () {
        return response()->json(['message' => 'Logistics access only']);
    });
});





Route::get('/admin/distributors/models', [IpgModelController::class, 'index']);



Route::middleware(['auth:sanctum', 'role:admin'])
    ->get('/admin/replacement-requests', [TicketController::class, 'getAllReplacementRequests']);

Route::middleware(['auth:sanctum', 'role:admin'])
    ->get('/admin/pending-replacement-requests', [TicketController::class, 'getPendingReplacementRequests']);
Route::middleware(['auth:sanctum', 'role:admin'])
    ->get('/admin/replacement-requests/{id}', [TicketController::class, 'getReplacementRequestDetails']);

Route::middleware(['auth:sanctum', 'role:admin'])
    ->post('/admin/replacement-requests/{id}/decision', [TicketController::class, 'decideReplacementRequest']);



//public API 


Route::get('/admin/distributors/models', [IpgModelController::class, 'index']);

Route::get('/get-all-ipg-models', [IpgModelController::class, 'index']);


Route::get('ipg-serials/search', [IpgModelController::class, 'searchSerials']);

Route::get('ipg-serials/search-available-serials', [IpgModelController::class, 'searchAvailableSerials']);
Route::get('/lead-serials/search', [LeadController::class, 'searchLeadSerials']);


