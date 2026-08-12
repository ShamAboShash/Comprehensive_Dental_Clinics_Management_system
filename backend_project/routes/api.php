<?php

use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\CenterController;
use App\Http\Controllers\Admin\DentistController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\OpirationsController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PatientAuthController;
use App\Http\Controllers\Dentist\AppointmentController;
use App\Http\Controllers\Dentist\DentistProfileController;
use App\Http\Controllers\Dentist\LabOrderController as DentistLabOrderController;
use App\Http\Controllers\Dentist\PatientController;
use App\Http\Controllers\Dentist\PrescriptionController;
use App\Http\Controllers\Dentist\SessionController;
use App\Http\Controllers\Dentist\ToothTreatmentController;
use App\Http\Controllers\Dentist\TreatmentController;
use App\Http\Controllers\Dentist\TreatmentPhotoController;
use App\Http\Controllers\Dentist\WorkScheduleController;
use App\Http\Controllers\Employee\EmployeeProfileController;
use App\Http\Controllers\Generals\ShowThingsController;
use App\Http\Controllers\LabManager\LabDashboardController;
use App\Http\Controllers\LabManager\LabItemController;
use App\Http\Controllers\LabManager\LabOrderController;
use App\Http\Controllers\LabManager\LabOrderTypeCategoryController;
use App\Http\Controllers\LabManager\LabStockController;
use App\Http\Controllers\LabManager\MaterialRequestController;
use App\Http\Controllers\LabManager\NotificationController;
use App\Http\Controllers\LabManager\TechnicianController;
use App\Http\Controllers\LabManager\WarehouseMaterialController;
use App\Http\Controllers\Patient\DentistScheduleController;
use App\Http\Controllers\Patient\PatientAppointmentController;
use App\Http\Controllers\Patient\PatientComplaintController;
use App\Http\Controllers\Patient\PatientDentistController;
use App\Http\Controllers\Patient\PatientDentistRatingController;
use App\Http\Controllers\Patient\PatientInvoiceController;
use App\Http\Controllers\Patient\PatientOfferController;
use App\Http\Controllers\Patient\PatientPrescriptionController;
use App\Http\Controllers\Patient\PatientProfileController;
use App\Http\Controllers\Patient\PatientTreatmentController;
use App\Http\Controllers\Patient\PatientTreatmentPhotoController;
use App\Http\Controllers\Secretary\AppointmentController as SecretaryAppointmentController;
use App\Http\Controllers\Secretary\AttendanceController as SecretaryAttendanceController;
use App\Http\Controllers\Secretary\ComplaintController as SecretaryComplaintController;
use App\Http\Controllers\Secretary\MaterialRequestController as SecretaryMaterialRequestController;
use App\Http\Controllers\Secretary\OfferController as SecretaryOfferController;
use App\Http\Controllers\Secretary\PatientController as SecretaryPatientController;
use App\Http\Controllers\Secretary\WarehouseMaterialController as SecretaryWarehouseMaterialController;
use App\Http\Controllers\WarehouseManager\InventoryController;
use App\Http\Controllers\WarehouseManager\MaterialController;
use App\Http\Controllers\WarehouseManager\MaterialRequestController as WarehouseMaterialRequestController;
use App\Http\Controllers\WarehouseManager\PurchaseInvoiceController;
use App\Http\Controllers\WarehouseManager\ReportController;
use App\Http\Controllers\WarehouseManager\WarehouseStockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ====================== SHARED-APIs ======================

Route::prefix('forgotPassword')->group(function () {
    Route::post('/sendCode', [ForgotPasswordController::class, 'sendCode']);
    Route::post('/verifyCode', [ForgotPasswordController::class, 'verifyCode']);
    Route::post('/resetPassword', [ForgotPasswordController::class, 'resetPassword']);
});

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/showAllEmployees', [EmployeeProfileController::class, 'showAllEmployees']);
    Route::get('/showAllPatients', [PatientProfileController::class,  'showAllPatients']);
    Route::get('/showAllDentists', [DentistProfileController::class,  'showAllDentists']);

});

Route::get('/roles', [ShowThingsController::class, 'roles']);
Route::get('/medications', [ShowThingsController::class,      'medications']);
Route::get('/specializations', [ShowThingsController::class,      'specializations']);
Route::get('/diseaseCategories', [ShowThingsController::class,      'diseaseCategories']);
Route::get('/appointmentTypes', [ShowThingsController::class, 'appointmentTypes']);
Route::get('/treatmentTypes', [ShowThingsController::class, 'treatmentTypes']);
Route::get('/procedures', [ShowThingsController::class, 'procedures']);
Route::get('/labOrderTypeCategories', [ShowThingsController::class, 'labOrderTypeCategories']);
Route::get('/labOrderTypes', [ShowThingsController::class, 'labOrderTypes']);
Route::get('/centerInfo', [ShowThingsController::class, 'centerInfo']);

// 16

// ====================== EMPLOYEE ======================

Route::prefix('employee')->group(function () {

    Route::post('/login', [EmployeeAuthController::class, 'login']);
    Route::post('/sendVerification', [EmployeeAuthController::class, 'sendVerification']);
    Route::post('/verifyCode', [EmployeeAuthController::class, 'verifyCode']);
    Route::post('/setPassword', [EmployeeAuthController::class, 'setPassword']);

    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        Route::post('/logout', [EmployeeAuthController::class,   'logout']);
        Route::get('/showProfile', [EmployeeProfileController::class, 'showProfile']);
        Route::post('/editProfile', [EmployeeProfileController::class, 'editProfile']);

    });

});

// 7

// ====================== PATIENT ======================

Route::prefix('patient')->group(function () {

    Route::post('/register', [PatientAuthController::class, 'register']);
    Route::post('/login', [PatientAuthController::class, 'login']);
    Route::post('/sendVerification', [PatientAuthController::class, 'sendVerification']);
    Route::post('/verifyEmail', [PatientAuthController::class, 'verifyEmail']);

    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        Route::post('/logout', [PatientAuthController::class,  'logout']);
        Route::get('/showProfile', [PatientProfileController::class, 'showProfile']);
        Route::post('/editProfile', [PatientProfileController::class, 'editProfile']);

        // Appointments
        Route::get('showAppointments', [PatientAppointmentController::class, 'index']);
        Route::get('showPreviousAppointments', [PatientAppointmentController::class, 'previous']);
        Route::get('showAppointmentdetails/{id}', [PatientAppointmentController::class, 'show']);
        Route::post('addAppointment', [PatientAppointmentController::class, 'store']);
        Route::post('updateAppointments/{id}', [PatientAppointmentController::class, 'update']);
        Route::post('cancelAppointment/{id}', [PatientAppointmentController::class, 'cancel']);

        // Dentists
        Route::get('showDentistsBySpecialization/{specialization_id}', [PatientDentistController::class, 'index']);
        Route::get('showDentistSchedule/{dentist}/', [DentistScheduleController::class, 'availableSlots']);
        Route::post('addDentistRate/{dentistId}', [PatientDentistRatingController::class, 'rate']);
        Route::get('showDentistRate/{dentistId}', [PatientDentistRatingController::class, 'myRating']);
        Route::get('showDentistDetails/{dentist}', [PatientDentistController::class, 'show']);
        Route::get('dentistsByAppointmentType/{appointmentTypeId}', [PatientDentistController::class, 'byAppointmentType']);

        // Treatments
        Route::get('showAllTreatments', [PatientTreatmentController::class, 'index']);
        Route::get('showTreatmentdetails/{id}', [PatientTreatmentController::class, 'show']);
        Route::get('treatmentsByType/{treatmentTypeId}', [PatientTreatmentController::class, 'treatmentsByType']);
        // Prescriptions
        Route::get('showAllPrescriptions', [PatientPrescriptionController::class, 'index']);
        Route::get('showPrescriptionDetails/{id}', [PatientPrescriptionController::class, 'show']);

        // Treatment photos
        Route::get('showAllTreatmentPhotos', [PatientTreatmentPhotoController::class, 'index']);

        // Complaints
        Route::get('showAllComplaints', [PatientComplaintController::class, 'index']);
        Route::post('addComplaint', [PatientComplaintController::class, 'store']);

        // Invoices
        Route::get('showAllInvoices', [PatientInvoiceController::class, 'summary']);
        Route::get('invoiceForTreatment/{treatmentId}', [PatientInvoiceController::class, 'show']);

        // Offers
        Route::get('showOffers', [PatientOfferController::class, 'index']);
        Route::post('applyToOffer/{offerId}', [PatientOfferController::class, 'apply']);

    });
});

// 31

// ====================== SECRETARY ======================

Route::prefix('secretary')->middleware(['auth:sanctum', 'active'])->group(function () {

    // Appointments
    Route::get('/showAllAppointments', [SecretaryAppointmentController::class, 'index']);
    Route::get('/dailyAppointments', [SecretaryAppointmentController::class, 'daily']);
    Route::get('/monthlyAppointments', [SecretaryAppointmentController::class, 'monthly']);
    Route::get('/showAppointment/{id}', [SecretaryAppointmentController::class, 'show']);
    Route::post('/addAppointment', [SecretaryAppointmentController::class, 'store']);
    Route::post('/updateAppointment/{id}', [SecretaryAppointmentController::class, 'update']);
    Route::post('/updateAppointmentStatus/{id}', [SecretaryAppointmentController::class, 'updateStatus']);
    Route::get('/dentistAppointments/{dentistId}', [SecretaryAppointmentController::class, 'dentistAppointments']);
    Route::get('/lateAppointments', [SecretaryAppointmentController::class, 'late']);

    // Warehouse materials
    Route::get('/showAllWarehouseMaterials', [SecretaryWarehouseMaterialController::class, 'index']);
    Route::get('/showWarehouseMaterial/{id}', [SecretaryWarehouseMaterialController::class, 'show']);

    // Material requests
    Route::get('/showAllMaterialRequests', [SecretaryMaterialRequestController::class, 'index']);
    Route::get('/showMaterialRequest/{id}', [SecretaryMaterialRequestController::class, 'show']);
    Route::post('/addMaterialRequest', [SecretaryMaterialRequestController::class, 'store']);
    Route::post('/updateMaterialRequest/{id}', [SecretaryMaterialRequestController::class, 'update']);
    Route::post('/deleteMaterialRequest/{id}', [SecretaryMaterialRequestController::class, 'destroy']);
    Route::post('/updateMaterialRequestStatus/{id}', [SecretaryMaterialRequestController::class, 'updateStatus']);

    // Attendance
    Route::get('/dailyAttendance', [SecretaryAttendanceController::class, 'daily']);
    Route::get('/monthlyAttendance', [SecretaryAttendanceController::class, 'monthly']);
    Route::post('/checkIn', [SecretaryAttendanceController::class, 'checkIn']);
    Route::post('/checkOut', [SecretaryAttendanceController::class, 'checkOut']);
    Route::post('/markAbsent', [SecretaryAttendanceController::class, 'markAbsent']);
    Route::post('/updateAttendance/{id}', [SecretaryAttendanceController::class, 'update']);

    // Patients
    Route::get('/showAllPatients', [SecretaryPatientController::class, 'index']);
    Route::get('/showPatient/{id}', [SecretaryPatientController::class, 'show']);
    Route::post('/addPatient', [SecretaryPatientController::class, 'store']);
    Route::post('/updatePatient/{id}', [SecretaryPatientController::class, 'update']);

    // Payments
    Route::post('/addPayment', [SecretaryPatientController::class, 'addPayment']);
    Route::get('/patientInvoices/{patientId}', [SecretaryPatientController::class, 'patientInvoices']);
    Route::get('/showInvoice/{invoiceId}', [SecretaryPatientController::class, 'showInvoice']);

    // Offers
    Route::get('/showAllOffers', [SecretaryOfferController::class, 'index']);
    Route::get('/showOffer/{id}', [SecretaryOfferController::class, 'show']);
    Route::post('/addOffer', [SecretaryOfferController::class, 'store']);
    Route::post('/updateOffer/{id}', [SecretaryOfferController::class, 'update']);
    Route::post('/deleteOffer/{id}', [SecretaryOfferController::class, 'destroy']);
    Route::post('/toggleOffer/{id}', [SecretaryOfferController::class, 'toggle']);

    // Complaints
    Route::get('/showAllComplaints', [SecretaryComplaintController::class, 'index']);
    Route::get('/showComplaint/{id}', [SecretaryComplaintController::class, 'show']);
    Route::post('/addComplaint', [SecretaryComplaintController::class, 'store']);
    Route::post('/updateComplaintStatus/{id}', [SecretaryComplaintController::class, 'updateStatus']);
});

// 2

// ====================== ADMIN ======================

Route::prefix('admin')->middleware(['auth:sanctum', 'active'])->group(function () {
    // profile
    Route::get('/showProfile', [AdminProfileController::class, 'showProfile']);
    Route::get('/showPatientProfile/{patientId}', [EmployeeController::class,     'showPatientProfile']);
    Route::post('/toggleUser/{user}', [EmployeeController::class,     'toggleUser']);
    // dentist
    Route::post('/createDentist', [DentistController::class,      'createDentist']);
    Route::post('/updateDentist/{user}', [DentistController::class,      'updateDentist']);
    Route::get('/showDentistDetails/{user}', [DentistController::class,      'showDentistDetails']);
    Route::get('/dentistMonthlyAttendance/{dentist}', [DentistController::class, 'dentistMonthly']);
    Route::post('/updateDentistWorkSchedule/{schedule}', [DentistController::class, 'updateDentistWS']);
    Route::get('/showDentistBySpecialization/{specializationId}', [DentistController::class, 'bySpecialization']);
    // center info
    Route::get('/warehouseInventory', [CenterController::class, 'getWarehouseInventory']);
    // employee
    Route::get('/showEmployeeDetails/{employeeId}', [EmployeeController::class,     'showEmployeeDetails']);
    Route::post('/createEmployee', [EmployeeController::class,     'createEmployee']);
    Route::post('/updateEmployee/{employee}', [EmployeeController::class,     'updateEmployee']);
    Route::get('/employeeMonthlyAttendance/{employee}', [EmployeeController::class, 'employeeMonthly']);
    Route::post('/updateEmployeeWorkSchedule/{schedule}', [EmployeeController::class, 'updateEmployeeWS']);
    // clinic
    Route::get('/showAllClinics', [CenterController::class, 'ClinicIndex']);
    Route::post('/addClinic', [CenterController::class, 'clinicStore']);
    Route::post('/updateClinic/{clinic}', [CenterController::class, 'clinicUpdate']);
    // Specialization
    Route::post('/addSpecialization', [OpirationsController::class, 'specializationStore']);
    Route::post('/updateSpecialization/{specialization}', [OpirationsController::class, 'specializationUpdate']);
    Route::post('/deleteSpecialization/{specialization}', [OpirationsController::class, 'specializationDestroy']);
    // Treatment Types
    Route::post('/addTreatmentType', [OpirationsController::class, 'treatmentStore']);
    Route::post('/updateTreatmentType/{treatmentType}', [OpirationsController::class, 'treatmentUpdate']);
    Route::post('/deleteTreatmentType/{treatmentType}', [OpirationsController::class, 'treatmentDestroy']);
    // Medications
    Route::post('/addMedication', [OpirationsController::class, 'medicationStore']);
    Route::post('/updateMedication/{medication}', [OpirationsController::class, 'medicationUpdate']);
    Route::post('/deleteMedication/{medication}', [OpirationsController::class, 'medicationDestroy']);
    // Procedures
    Route::post('/addProcedure', [OpirationsController::class, 'procedureStore']);
    Route::post('/updateProcedure/{procedure}', [OpirationsController::class, 'procedureUpdate']);
    Route::post('/deleteProcedure/{procedure}', [OpirationsController::class, 'procedureDestroy']);
    // Complaints
    Route::get('/showComplaints', [CenterController::class, 'complaintIndex']);
    Route::post('/updateComplaintStatus/{complaint}', [CenterController::class, 'complaintUpdate']);
    // Center info
    Route::post('/updateCenterInfo', [CenterController::class, 'updateCenterInfo']);
});

// 35

// ====================== DENTIST ======================

Route::prefix('dentist')->middleware(['auth:sanctum', 'active'])->group(function () {

    // Profile
    Route::get('/showProfile', [DentistProfileController::class, 'showProfile']);
    Route::post('/editProfile', [DentistProfileController::class, 'editProfile']);

    // Appointments
    Route::get('/dailyAppointments', [AppointmentController::class, 'daily']);
    Route::get('/dentistAppointments/{patient}', [AppointmentController::class, 'dentistAppointments']);
    Route::get('/yesterdayAppointments', [AppointmentController::class, 'yesterday']);
    Route::get('/weeklyAppointments', [AppointmentController::class, 'weekly']);
    Route::get('/monthlyAppointments', [AppointmentController::class, 'monthly']);
    Route::get('/showAppointment/{appointment}', [AppointmentController::class, 'show']);
    Route::post('/updateAppointmentStatus/{appointment}', [AppointmentController::class, 'updateStatus']);

    // Work Schedule
    Route::get('/showWeekSchedule', [WorkScheduleController::class, 'index']);

    // Patients
    Route::get('/showSAllPatients', [PatientController::class, 'index']);
    Route::get('/showPatientDetails/{patient}', [PatientController::class, 'show']);
    Route::post('/addPatientMedicalInfo/{patient}', [PatientController::class, 'addMedicalInfo']);

    // Treatments
    Route::get('/showAllPatientTreatments/{patient}', [TreatmentController::class, 'index']);
    Route::post('/addPatientTreatment/{patient}', [TreatmentController::class, 'store']);
    Route::get('/showTreatmentDetails/{treatment}', [TreatmentController::class, 'show']);
    Route::post('/updateTreatmentDetails/{treatment}', [TreatmentController::class, 'update']);

    // Sessions
    Route::get('/showTreatmentSession/{treatment}/{session}', [SessionController::class, 'show']);
    Route::post('/addTreatmentSession/{treatment}', [SessionController::class, 'store']);
    Route::post('/updateTreatmentSession/{treatment}/{session}', [SessionController::class, 'update']);

    // Tooth Treatments
    Route::post('/addToothTreatment/{treatment}/{session}', [ToothTreatmentController::class, 'store']);
    Route::post('/deleteToothTreatment/{treatment}/{session}/{toothTreatment}', [ToothTreatmentController::class, 'destroy']);

    // Prescriptions
    Route::post('/addPrescription/{treatment}/{session}', [PrescriptionController::class, 'store']);
    Route::post('/updatePrescription/{treatment}/{session}', [PrescriptionController::class, 'update']);

    // Treatment Photos
    Route::post('/addTreatmentPhoto/{treatment}/{session}', [TreatmentPhotoController::class, 'store']);
    Route::post('/deleteTreatmentPhoto/{treatment}/{session}/{photo}', [TreatmentPhotoController::class, 'destroy']);

    // Lab Orders
    Route::get('/showAllLabOrders', [DentistLabOrderController::class, 'index']);
    Route::post('/addLabOrder', [DentistLabOrderController::class, 'store']);
    Route::get('/showLabOrderDetails/{labOrder}', [DentistLabOrderController::class, 'show']);
    Route::post('/updateLabOrder/{labOrder}', [DentistLabOrderController::class, 'update']);
    Route::post('/cancelLabOrder/{labOrder}', [DentistLabOrderController::class, 'cancel']);
    Route::post('/addLabOrderModification/{labOrder}', [DentistLabOrderController::class, 'storeModification']);
    Route::post('/updateLabOrderModification/{labOrder}/{modification}', [DentistLabOrderController::class, 'updateModification']);
    Route::post('/cancelLabOrderModification/{labOrder}/{modification}', [DentistLabOrderController::class, 'cancelModification']);

});
// 34

// ====================== LAB MANAGER ======================

Route::prefix('labManager')->middleware(['auth:sanctum', 'active'])->group(function () {

    // Dashboard
    Route::get('countOrders', [LabDashboardController::class, 'orderCounts']);
    Route::get('countTechnicians', [LabDashboardController::class, 'technicianCounts']);
    Route::get('countStock', [LabDashboardController::class, 'stockCounts']);

    // Item Categories
    Route::get('showAllItemsCategories', [LabOrderTypeCategoryController::class, 'index']);
    Route::get('showItemCategory/{id}', [LabOrderTypeCategoryController::class, 'show']);
    Route::post('addItemCategory', [LabOrderTypeCategoryController::class, 'store']);
    Route::post('updateItemCategory/{id}', [LabOrderTypeCategoryController::class, 'update']);
    Route::post('deleteItemCategory/{id}', [LabOrderTypeCategoryController::class, 'destroy']);

    // Lab Items
    Route::get('showAllLabItems', [LabItemController::class, 'index']);
    Route::get('showLabItem/{id}', [LabItemController::class, 'show']);
    Route::post('addLabItem', [LabItemController::class, 'store']);
    Route::post('updateLabItem/{id}', [LabItemController::class, 'update']);
    Route::post('deleteLabItem/{id}', [LabItemController::class, 'destroy']);
    Route::post('updateLabItemStatus/{id}', [LabItemController::class, 'toggle']);

    // Lab Orders
    Route::get('showAllLabOrders', [LabOrderController::class, 'index']);
    Route::get('showAllTodayLabOrders', [LabOrderController::class, 'today']);
    Route::get('showLabOrder/{id}', [LabOrderController::class, 'show']);
    Route::post('setStatusInProgress/{id}', [LabOrderController::class, 'setInProgress']);
    Route::post('setStatusCompleted/{id}', [LabOrderController::class, 'setCompleted']);
    Route::post('setStatusCancelled/{id}', [LabOrderController::class, 'setCancelled']);

    // Technicians
    Route::get('showAllTechnicians', [TechnicianController::class, 'index']);
    Route::get('showTechniciansPerformance', [TechnicianController::class, 'performance']);
    Route::get('showTechnician/{id}', [TechnicianController::class, 'show']);
    Route::post('updateTechnicianWorkSchedule/{id}', [TechnicianController::class, 'updateWorkTime']);

    // Lab Stock
    Route::get('showLabStock', [LabStockController::class, 'index']);
    Route::get('showLabStockLogs', [LabStockController::class, 'logs']);
    Route::get('showStock/{id}', [LabStockController::class, 'show']);
    Route::post('addToStock/{id}', [LabStockController::class, 'add']);
    Route::post('subtractFromStock/{id}', [LabStockController::class, 'subtract']);
    Route::post('notifyLowStock', [LabStockController::class, 'notifyShort']);

    // Warehouse Materials
    Route::get('showAllWarehouseMaterials', [WarehouseMaterialController::class, 'index']);
    Route::get('showWarehouseMaterial/{id}', [WarehouseMaterialController::class, 'show']);

    // Material Requests
    Route::get('showAllMaterialRequests', [MaterialRequestController::class, 'index']);
    Route::get('showMaterialRequest/{id}', [MaterialRequestController::class, 'show']);
    Route::post('addMaterialRequest', [MaterialRequestController::class, 'store']);
    Route::post('deleteMaterialRequest/{id}', [MaterialRequestController::class, 'destroy']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // reports
    Route::get('reports', [LabDashboardController::class, 'reports']);
});

// 39

// add the counts for each one

// ====================== WAREHOUSE MANAGER ======================

Route::prefix('warehouseManager')->middleware(['auth:sanctum', 'active'])->group(function () {

    // Materials
    Route::get('showALLMaterials', [MaterialController::class, 'index']);
    Route::get('showMaterialDetails/{id}', [MaterialController::class, 'show']);
    Route::post('addMaterial', [MaterialController::class, 'store']);
    Route::post('updateMaterial/{id}', [MaterialController::class, 'update']);
    Route::post('updateStatus/{id}', [MaterialController::class, 'updateStatus']);
    // Route::delete('materials/{id}',  [MaterialController::class, 'destroy']);

    // Stock
    Route::get('showAllStock', [WarehouseStockController::class, 'index']);
    Route::get('showStockLogs', [WarehouseStockController::class, 'logs']);
    Route::get('showStockDetails/{id}', [WarehouseStockController::class, 'show']);
    Route::post('adjustStockQuantity/{id}', [WarehouseStockController::class, 'adjust']);
    // Route::post('addStockBatch', [WarehouseStockController::class, 'storeBatch']);

    // Material Requests
    Route::get('showAllMaterialRequests', [WarehouseMaterialRequestController::class, 'index']);
    Route::get('showMaterialRequestDetails/{id}', [WarehouseMaterialRequestController::class, 'show']);
    Route::post('materialRequestFulfill/{id}', [WarehouseMaterialRequestController::class, 'fulfill']);
    Route::post('materialRequestReject/{id}', [WarehouseMaterialRequestController::class, 'reject']);

    // Purchase Invoices
    Route::get('showAllPurchaseInvoices', [PurchaseInvoiceController::class, 'index']);
    Route::get('showPurchaseInvoiceDetails/{id}', [PurchaseInvoiceController::class, 'show']);
    Route::post('addPurchaseInvoice', [PurchaseInvoiceController::class, 'store']);
    Route::post('updatePurchaseInvoice/{id}', [PurchaseInvoiceController::class, 'update']);

    // Inventory
    Route::get('mostRequestedMaterials', [InventoryController::class, 'mostRequested']);
    Route::get('expiringSoonMaterials', [InventoryController::class, 'expiringSoon']);
    Route::get('lowStockMaterials', [InventoryController::class, 'lowStock']);

    // Reports
    Route::get('stock-movement', [ReportController::class, 'stockMovement']);
    Route::get('purchase-invoices', [ReportController::class, 'purchaseInvoices']);
    Route::get('material-requests', [ReportController::class, 'materialRequests']);

    Route::get('reports/dashboard',   [ReportController::class, 'dashboard']);
    Route::post('reports/save-month', [ReportController::class, 'saveMonth']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);

});
