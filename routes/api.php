<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ClassSessionController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\CertificateTemplateController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\GuardianController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\SchoolTypeController;
use App\Http\Controllers\Api\ModalityController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\SchoolPaymentPlanController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\StudentPaymentPlanController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\WhatsAppInstanceController;
use App\Http\Controllers\Api\BannerController;

Route::prefix('v1')->group(function () {

    // Health check
    Route::get('status', function () {
        return response()->json(['status' => 'API V1 Class Up is alive!'], 200);
    });

    // Public authentication routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::get('reset-password/{token}', function (Request $request, $token) {
        $email = $request->query('email');
        return redirect("http://localhost/?token={$token}&email={$email}");
    })->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    // Public plans route
    Route::get('plans', [PlanController::class, 'index']);

    // Public school configuration routes
    Route::get('school-types', [SchoolTypeController::class, 'index']);
    Route::get('modalities', [ModalityController::class, 'index']);

    // Stripe webhook (must not require authentication)
    Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle'])->withoutMiddleware('auth:sanctum');

    // Subscription cancel (public route, no auth required)
    Route::get('subscription/checkout-canceled', [SubscriptionController::class, 'checkoutCanceled'])->name('subscription.cancel');

    Route::middleware('auth:sanctum')->group(function () {
        // Authentication endpoints
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Subscription management (requires authentication but NOT subscription validation)
        Route::get('subscription/status', [SubscriptionController::class, 'status']);
        Route::get('subscription/invoices', [SubscriptionController::class, 'invoices']);
        Route::post('subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
        Route::post('subscription/confirm', [SubscriptionController::class, 'confirm']);
        Route::post('subscription/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('subscription/resume', [SubscriptionController::class, 'resume']);

        // School configuration (not subscription dependent)
        Route::apiResource('school-types', SchoolTypeController::class)->except('index');
        Route::apiResource('modalities', ModalityController::class)->except('index');

        // Dashboard & Finance (require subscription)
        Route::middleware('validate.subscription')->group(function () {
            Route::get('dashboard/student-growth', [DashboardController::class, 'studentGrowth']);
            Route::get('finance/summary', [FinanceController::class, 'summary']);
        });

        // Student endpoints (no subscription required)
        Route::apiResource('students', StudentController::class);

        // Classes endpoint (no subscription required - users need to see their dependents' classes)
        Route::apiResource('classes', ClassSessionController::class);

        // Guardian subscriptions endpoint (no subscription required - users need to see their payment plans)
        Route::get('guardians/subscriptions', [GuardianController::class, 'subscriptions'])->name('guardians.subscriptions');

        // All protected routes that require active subscription
        Route::middleware('validate.subscription')->group(function () {
            Route::apiResource('certificate-templates', CertificateTemplateController::class);
            Route::apiResource('certificates', CertificateController::class);

            Route::apiResource('guardians', GuardianController::class);
            Route::apiResource('instructors', InstructorController::class);
            Route::apiResource('classrooms', ClassroomController::class);
            Route::apiResource('subjects', SubjectController::class);
            Route::apiResource('enrollments', EnrollmentController::class);
            Route::apiResource('attendances', AttendanceController::class);
            Route::apiResource('grades', GradeController::class);

            // Payment system routes
            Route::apiResource('school-payment-plans', SchoolPaymentPlanController::class);
            Route::apiResource('payment-methods', PaymentMethodController::class);
            Route::apiResource('student-payment-plans', StudentPaymentPlanController::class);
            Route::apiResource('payments', PaymentController::class);
            Route::post('payments/{id}/mark-as-paid', [PaymentController::class, 'markAsPaid'])->name('payments.markAsPaid');

            // Financial management routes
            Route::apiResource('products', ProductController::class);
            Route::apiResource('expenses', ExpenseController::class);
            Route::apiResource('incomes', IncomeController::class);

            // Banner routes
            Route::apiResource('banners', BannerController::class);

            // WhatsApp Integration routes
            Route::prefix('whatsapp')->group(function () {
                Route::post('instances', [WhatsAppInstanceController::class, 'create'])->name('whatsapp.instances.create');
                Route::get('instances', [WhatsAppInstanceController::class, 'indexSchoolInstances'])->name('whatsapp.instances.index');
                Route::get('instances/all', [WhatsAppInstanceController::class, 'fetchAll'])->name('whatsapp.instances.fetchAll');
                Route::get('info', [WhatsAppInstanceController::class, 'getInfo'])->name('whatsapp.info');
                Route::get('instances/{id}/status', [WhatsAppInstanceController::class, 'getStatusById'])->name('whatsapp.instances.statusById');
                Route::get('instances/status', [WhatsAppInstanceController::class, 'getStatus'])->name('whatsapp.instances.status');
                Route::post('instances/{id}/connect', [WhatsAppInstanceController::class, 'connectById'])->name('whatsapp.instances.connectById');
                Route::post('instances/connect', [WhatsAppInstanceController::class, 'connect'])->name('whatsapp.instances.connect');
                Route::delete('instances/{id}', [WhatsAppInstanceController::class, 'destroyById'])->name('whatsapp.instances.destroyById');
                Route::post('instances/{id}/delete', [WhatsAppInstanceController::class, 'deleteById'])->name('whatsapp.instances.deleteById');
                Route::post('instances/delete', [WhatsAppInstanceController::class, 'delete'])->name('whatsapp.instances.delete');
                Route::post('instances/{id}/webhook', [WhatsAppInstanceController::class, 'setWebhookById'])->name('whatsapp.webhook.setById');
                Route::post('webhook/set', [WhatsAppInstanceController::class, 'setWebhook'])->name('whatsapp.webhook.set');
                Route::get('webhook/find', [WhatsAppInstanceController::class, 'getWebhook'])->name('whatsapp.webhook.find');
            });
        });
    });
});
