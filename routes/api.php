<?php

use App\Http\Controllers\Account\SubAccountController;
use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\InterpreterController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\StatesController;
use App\Http\Controllers\SubClientAccountController;
use App\Http\Controllers\SubClientTemplateController;
use App\Http\Controllers\SubClientTypeController;
use App\Http\Controllers\SubUserController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\VenderController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckClient;
use App\Http\Middleware\CheckInterpreter;
use App\Http\Middleware\CheckVendor;

// use Illuminate\Support\Facades\Broadcast;

// Broadcast::routes(['middleware' => ['auth:api']]);

Route::post('signup', [AuthController::class, 'signup']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

Route::post('asterisk-webhook', [AppointmentController::class, 'asterisk_webhook']);

Route::post('changepassword', [AuthController::class, 'changePassword'])->middleware('auth:api');

Route::get('/get-appointment/{token}', [AppointmentController::class, 'getWithToken']);
Route::post('/accept-appointment/{token}', [AppointmentController::class, 'acceptWithToken']);
Route::post('/reject-appointment/{token}', [AppointmentController::class, 'rejectWithToken']);

Route::get('/get-appointment-requester/{token}', [AppointmentController::class, 'getWithTokenForRequester']);
Route::post('/join-room/{token}', [AppointmentController::class, 'joinRoomWithToken']);

Route::post('/100ms-webhook', [WebhookController::class, 'handle']);



Route::middleware(['auth:api'])->group(function () {
    Route::prefix('client')->group(function () {
        Route::prefix('language')->group(function () {
            Route::get('/list', [LanguageController::class, 'index']);
        });
    });

    Route::prefix('client')->middleware(CheckAdmin::class)->group(function () {


        Route::prefix('city')->group(function () {
            Route::get('/list', [CityController::class, 'index']);
            Route::post('/', [CityController::class, 'store']);
            Route::post('/edit/{id}', [CityController::class, 'update']);
            Route::post('/delete/{id}', [CityController::class, 'destroy']);
            Route::post('/records', [CityController::class, 'deleteMultipleRecords']);
            Route::post('/status/{id}', [CityController::class, 'changeStatus']);
        });

        Route::prefix('language')->group(function () {
            // Route::get('/list', [LanguageController::class, 'index']);
            Route::get('/tier-languages', [LanguageController::class, 'tierLanguages']);
            Route::post('/', [LanguageController::class, 'store']);
            Route::post('/edit/{id}', [LanguageController::class, 'update']);
            Route::post('/delete/{id}', [LanguageController::class, 'destroy']);
            Route::post('/records', [LanguageController::class, 'deleteMultipleRecords']);
            Route::post('/status/{id}', [LanguageController::class, 'changeStatus']);
        });

        Route::prefix('state')->group(function () {
            Route::get('/list', [StatesController::class, 'index']);
            Route::post('/', [StatesController::class, 'store']);
            Route::post('/edit/{id}', [StatesController::class, 'update']);
            Route::post('/delete/{id}', [StatesController::class, 'destroy']);
            Route::post('/records', [StatesController::class, 'deleteMultipleRecords']);
            Route::post('/status/{id}', [StatesController::class, 'changeStatus']);
        });

        Route::prefix('subclients')->group(function () {

            Route::prefix('types')->group(function () {
                Route::get('/list', [SubClientTypeController::class, 'index']);
                Route::get('/list-page', [SubClientTypeController::class, 'page']);
                Route::post('/', [SubClientTypeController::class, 'store']);
                Route::post('/edit/{id}', [SubClientTypeController::class, 'update']);
                Route::post('/delete/{id}', [SubClientTypeController::class, 'destroy']);
                Route::post('/records', [SubClientTypeController::class, 'deleteMultipleRecords']);
                Route::post('/status/{id}', [SubClientTypeController::class, 'changeStatus']);

                Route::prefix('facilities')->group(function () {
                    Route::get('/list', [FacilityController::class, 'index']);
                    Route::post('/', [FacilityController::class, 'store']);
                    Route::post('/edit/{id}', [FacilityController::class, 'update']);
                    Route::post('/delete/{id}', [FacilityController::class, 'destroy']);
                    Route::post('/records', [FacilityController::class, 'deleteMultipleRecords']);
                    Route::post('/status/{id}', [FacilityController::class, 'changeStatus']);
                });

                Route::prefix('departments')->group(function () {
                    Route::get('/list', [DepartmentController::class, 'index']);
                    Route::post('/', [DepartmentController::class, 'store']);
                    Route::post('/edit/{id}', [DepartmentController::class, 'update']);
                    Route::post('/delete/{id}', [DepartmentController::class, 'destroy']);
                    Route::post('/records', [DepartmentController::class, 'deleteMultipleRecords']);
                    Route::post('/status/{id}', [DepartmentController::class, 'changeStatus']);
                });
            });
        });

        Route::prefix('dashboard')->group(function () {
            Route::get('/analytics', [DashboardController::class, 'analytics']);
            Route::get('/appointments', [DashboardController::class, 'appointments']);
        });

        Route::prefix('appointments')->group(function () {
            Route::get('/get/{id}', [AppointmentController::class, 'get']);
            Route::get('/get-logs/{id}', [AppointmentController::class, 'getLogs']);
            Route::get('/list', [AppointmentController::class, 'index']);

            Route::post('/', [AppointmentController::class, 'store']);
            Route::post('/edit/{id}', [AppointmentController::class, 'update']);
            Route::post('/delete/{id}', [AppointmentController::class, 'destroy']);
            Route::post('/records', [AppointmentController::class, 'deleteMultipleRecords']);
            Route::post('/status/{id}', [AppointmentController::class, 'changeStatus']);

            Route::post('/filter-patient', [AppointmentController::class, 'filterPatient']);

            Route::post('/invite-interpreters/{id}', [AppointmentController::class, 'inviteInterpreters']);
            Route::post('/clear-invites/{id}', [AppointmentController::class, 'clearInvites']);
            Route::get('/get-interpreters/{id}', [AppointmentController::class, 'getInterpreters']);
            Route::get('/get-invited-interpreters/{id}', [AppointmentController::class, 'getInvitedInterpreters']);
            Route::get('/get-interpreters-with-appointments/{id}', [AppointmentController::class, 'getInterpretersWithAppointments']);

            Route::prefix('actions')->group(function () {
                Route::post('/reschedule/{id}', [AppointmentController::class, 'actions_reschedule']);
                Route::post('/assign/{id}', [AppointmentController::class, 'actions_assign']);
                Route::post('/dnc/{id}', [AppointmentController::class, 'actions_dnc']);
                Route::post('/cnc/{id}', [AppointmentController::class, 'actions_cnc']);
                Route::post('/cancel/{id}', [AppointmentController::class, 'actions_cancel']);
                Route::post('/extra_mileage/{id}', [AppointmentController::class, 'extra_mileage']);
                Route::post('/mileage-approval/{id}', [AppointmentController::class, 'action_approval']);
                Route::post('/decline', [AppointmentController::class, 'actions_decline']);
                Route::post('/adjustment/{id}', [AppointmentController::class, 'actions_adjustment']);
                Route::post('/add-patient/{id}', [AppointmentController::class, 'add_patient']);
                Route::post('/hang-up-call/{id}', [AppointmentController::class, 'hang_up_call']);
                
                Route::post('/invite-guests/{id}', [AppointmentController::class, 'inviteGuests']);
                Route::post('/join-room/{id}', [AppointmentController::class, 'joinRoom']);

                Route::post('/auto-invite-by-call/{id}', [AppointmentController::class, 'actions_auto_invite_by_call']);
                Route::post('/auto-invite-by-email/{id}', [AppointmentController::class, 'actions_auto_invite_by_email']);
            });
        });

        Route::prefix('translations')->group(function () {
            Route::get('/get/{id}', [TranslationController::class, 'get']);
            Route::get('/get-logs/{id}', [TranslationController::class, 'getLogs']);
            Route::get('/list', [TranslationController::class, 'index']);

            Route::post('/', [TranslationController::class, 'store']);
            Route::post('/edit/{id}', [AppointmentController::class, 'update']);
            Route::post('/delete/{id}', [AppointmentController::class, 'destroy']);
            Route::post('/records', [AppointmentController::class, 'deleteMultipleRecords']);
            Route::post('/status/{id}', [AppointmentController::class, 'changeStatus']);
            
            Route::get('/get-all-translators', [TranslationController::class, 'getAllInterpreters']);
            Route::get('/get-translators/{id}', [TranslationController::class, 'getInterpreters']);
            Route::post('/invite-translators/{id}', [TranslationController::class, 'inviteInterpreters']);

            // Route::post('/clear-invites/{id}', [AppointmentController::class, 'clearInvites']);
            Route::get('/get-invited-translators/{id}', [TranslationController::class, 'getInvitedInterpreters']);

            Route::prefix('actions')->group(function () {
                Route::post('/assign-translator/{id}', [TranslationController::class, 'assign_translator']);
                
                Route::post('/upload-translated-files/{id}', [TranslationController::class, 'upload_translated_files']);
                
                Route::post('/approve-submission/{id}', [TranslationController::class, 'approve_submission']);
                
                Route::post('/reject-submission/{id}', [TranslationController::class, 'reject_submission']);
                
                Route::post('/change-invoice-status/{id}', [TranslationController::class, 'change_invoice_status']);
                
                Route::post('/cancel/{id}', [TranslationController::class, 'cancel']);

                // Route::post('/decline-invite/{id}', [TranslationController::class, 'decline_invite']);
            });
            // Route::get('/get-interpreters-with-appointments/{id}', [AppointmentController::class, 'getInterpretersWithAppointments']);

        });
        Route::prefix('quotations')->group(function () {
            Route::get('/list', [App\Http\Controllers\QuotationController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\QuotationController::class, 'show']);
            Route::get('/latest/{translation_id}', [App\Http\Controllers\QuotationController::class, 'getLatest']);
            Route::post('/update/{id}', [App\Http\Controllers\QuotationController::class, 'update']);
            Route::post('/approve/{id}', [App\Http\Controllers\QuotationController::class, 'approve']);
            Route::post('/reject/{id}', [App\Http\Controllers\QuotationController::class, 'reject']);
        
        });

        Route::prefix('invoices')->group(function () {
            Route::get('/list', [InvoiceController::class, 'index']);
            Route::get('/appt-clients', [InvoiceController::class, 'AppointClients']);
            Route::get('/appt-clients-list', [InvoiceController::class, 'AppointClientsListPage']);
            Route::get('/generated-invoices', [InvoiceController::class, 'generatedInvoices']);
            Route::post('/make-invoice', [InvoiceController::class, 'makeInvoices']);
            Route::post('/delete-invoices/{id}', [InvoiceController::class, 'destroyInvoices']);
            Route::post('/records-invoices', [InvoiceController::class, 'deleteMultipleInvoices']);
            Route::post('/edit/{id}', [InvoiceController::class, 'update']);
            Route::post('/status/{id}', [InvoiceController::class, 'changeStatus']);
        });

        Route::prefix('payments')->group(function () {
            Route::get('/list-interpreter', [PaymentController::class, 'index']);
            Route::get('/list-vendor', [PaymentController::class, 'indexVendor']);
            Route::post('/status', [PaymentController::class, 'changeStatus']);
        });

        Route::prefix('vendor')->group(function () {
            Route::get('/list', [VenderController::class, 'index']);
            Route::get('/list-page', [VenderController::class, 'page']);
            Route::post('/', [VenderController::class, 'store']);
            Route::post('/edit/{id}', [VenderController::class, 'update']);
            Route::post('/delete/{id}', [VenderController::class, 'destroy']);
            Route::post('/changepassword/{id}', [VenderController::class, 'changePassword']);
            Route::post('/records', [VenderController::class, 'deleteMultipleRecords']);
            Route::post('/status/{id}', [VenderController::class, 'changeStatus']);
        });

        Route::prefix('interpreters')->group(function () {
            Route::get('/list', [InterpreterController::class, 'index']);
            Route::post('/', [InterpreterController::class, 'store']);
            Route::post('/edit/{id}', [InterpreterController::class, 'update']);
            Route::post('/delete/{id}', [InterpreterController::class, 'destroy']);
            Route::post('/records', [InterpreterController::class, 'deleteMultipleRecords']);
            Route::post('/status/{id}', [InterpreterController::class, 'changeStatus']);
        });

        Route::prefix('permissions')->group(function () {
            Route::prefix('groups')->group(function () {
                Route::get('/list', [PermissionController::class, 'index']);
                Route::get('/accesses', [PermissionController::class, 'accesses']);
                Route::post('/', [PermissionController::class, 'store']);
                Route::post('/edit/{id}', [PermissionController::class, 'update']);
                Route::post('/delete/{id}', [PermissionController::class, 'destroy']);
                Route::post('/records', [PermissionController::class, 'deleteMultipleRecords']);
                Route::post('/status/{id}', [PermissionController::class, 'changeStatus']);
            });
            Route::prefix('sub-users')->group(function () {
                Route::get('/list', [SubUserController::class, 'index']);
                Route::post('/', [SubUserController::class, 'store']);
                Route::post('/edit/{id}', [SubUserController::class, 'update']);
                Route::post('/delete/{id}', [SubUserController::class, 'destroy']);
                Route::post('/records', [SubUserController::class, 'deleteMultipleRecords']);
                Route::post('/status/{id}', [SubUserController::class, 'changeStatus']);
            });
        });

        Route::prefix('tools')->group(function () {});
    });

    Route::prefix('account')->middleware(CheckClient::class)->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('/analytics', [\App\Http\Controllers\Account\DashboardController::class, 'analytics']);
            Route::get('/appointments', [\App\Http\Controllers\Account\DashboardController::class, 'appointments']);
        });

        Route::prefix('profile')->group(function () {
            Route::get('/', [\App\Http\Controllers\Account\ProfileController::class, 'getProfile']);
            Route::post('/update', [\App\Http\Controllers\Account\ProfileController::class, 'updateProfile']);
        });

        Route::prefix('invoices')->group(function () {
            Route::get('/list', [\App\Http\Controllers\Account\BillingController::class, 'getProfile']);
        });

        Route::prefix('appointments')->group(function () {
            Route::get('/get/{id}', [App\Http\Controllers\Account\AppointmentController::class, 'get']);
            Route::get('/get-logs/{id}', [App\Http\Controllers\Account\AppointmentController::class, 'getLogs']);
            Route::get('/list', [App\Http\Controllers\Account\AppointmentController::class, 'index']);
        });

        Route::prefix('translations')->group(function () {
            Route::get('/get/{id}', [TranslationController::class, 'get']);
            Route::get('/get-logs/{id}', [TranslationController::class, 'getLogs']);
            Route::get('/list', [TranslationController::class, 'index']);

            Route::post('/', [TranslationController::class, 'store']);
           
            Route::prefix('actions')->group(function () {                
                Route::post('/cancel/{id}', [TranslationController::class, 'cancel']);
            });
        });

        Route::prefix('quotations')->group(function () {
            Route::post('/approve/{id}', [App\Http\Controllers\QuotationController::class, 'approve']);
            Route::post('/reject/{id}', [App\Http\Controllers\QuotationController::class, 'reject']);
        });

    });

    Route::prefix('interpreter')->middleware(CheckInterpreter::class)->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('/analytics', [\App\Http\Controllers\Interpreter\DashboardController::class, 'analytics']);
            Route::get('/appointments', [\App\Http\Controllers\Interpreter\DashboardController::class, 'appointments']);
        });

        Route::prefix('profile')->group(function () {
            Route::get('/', [\App\Http\Controllers\Interpreter\ProfileController::class, 'getProfile']);
            Route::post('/update', [\App\Http\Controllers\Interpreter\ProfileController::class, 'updateProfile']);
        });

        Route::prefix('payments')->group(function () {
            Route::get('/list', [\App\Http\Controllers\Interpreter\PaymentController::class, 'index']);
        });

        Route::prefix('appointments')->group(function () {
            Route::get('/get/{id}', [App\Http\Controllers\Interpreter\AppointmentController::class, 'get']);
            Route::get('/get-logs/{id}', [App\Http\Controllers\Interpreter\AppointmentController::class, 'getLogs']);
            Route::get('/list', [App\Http\Controllers\Interpreter\AppointmentController::class, 'index']);
            
            Route::post('/actions/join-room/{id}', [App\Http\Controllers\Interpreter\AppointmentController::class, 'joinRoom']);

            Route::post('/actions/answer-vri-call/{id}', [App\Http\Controllers\Interpreter\AppointmentController::class, 'answerVriCall']);
            

        });

        Route::prefix('translations')->group(function () {
            Route::get('/get/{id}', [TranslationController::class, 'get']);
            Route::get('/get-logs/{id}', [TranslationController::class, 'getLogs']);
            Route::get('/list', [TranslationController::class, 'index']);
            Route::prefix('actions')->group(function () {
                Route::post('/accept-invite/{id}', [TranslationController::class, 'accept_invite']);
                Route::post('/decline-invite/{id}', [TranslationController::class, 'decline_invite']);
                Route::post('/decline-translation/{id}', [TranslationController::class, 'decline_translation']);
                Route::post('/upload-translated-files/{id}', [TranslationController::class, 'upload_translated_files']);
            });
        });
    });

    Route::prefix('vendor')->middleware(CheckVendor::class)->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('/analytics', [\App\Http\Controllers\Vendor\DashboardController::class, 'analytics']);
            Route::get('/appointments', [\App\Http\Controllers\Vendor\DashboardController::class, 'appointments']);
        });

        Route::prefix('profile')->group(function () {
            Route::get('/', [\App\Http\Controllers\Vendor\ProfileController::class, 'getProfile']);
            Route::post('/update', [\App\Http\Controllers\Vendor\ProfileController::class, 'updateProfile']);
        });

        Route::prefix('payments')->group(function () {
            Route::get('/list', [\App\Http\Controllers\Vendor\PaymentController::class, 'index']);
        });

        Route::prefix('appointments')->group(function () {
            Route::get('/get/{id}', [App\Http\Controllers\Vendor\AppointmentController::class, 'get']);
            Route::get('/get-logs/{id}', [App\Http\Controllers\Vendor\AppointmentController::class, 'getLogs']);
            Route::get('/list', [App\Http\Controllers\Vendor\AppointmentController::class, 'index']);
            Route::prefix('actions')->group(function () {
                Route::post('/assign/{id}', [App\Http\Controllers\Vendor\AppointmentController::class, 'actions_assign']);
            });
        });

        Route::prefix('interpreters')->group(function () {
            Route::get('/list', [App\Http\Controllers\Vendor\InterpreterController::class, 'index']);
            Route::get('/available-interpreters', [App\Http\Controllers\Vendor\InterpreterController::class, 'available_interpreters']);
            Route::post('/', [App\Http\Controllers\Vendor\InterpreterController::class, 'store']);
            Route::post('/edit/{id}', [App\Http\Controllers\Vendor\InterpreterController::class, 'update']);
            Route::post('/delete/{id}', [App\Http\Controllers\Vendor\InterpreterController::class, 'destroy']);
            Route::post('/records', [App\Http\Controllers\Vendor\InterpreterController::class, 'deleteMultipleRecords']);
            Route::post('/status/{id}', [App\Http\Controllers\Vendor\InterpreterController::class, 'changeStatus']);
        });
    });
});

