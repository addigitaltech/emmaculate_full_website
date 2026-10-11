<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultCheckController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\StudentAccessController;
use App\Http\Controllers\StaffResultsController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/about', [SiteController::class, 'about'])->name('about');
Route::get('/history', [SiteController::class, 'history'])->name('history');
Route::get('/mission-vision', [SiteController::class, 'missionVision'])->name('mission');
Route::get('/academics', [SiteController::class, 'academics'])->name('academics');
Route::get('/academics/{slug}', [SiteController::class, 'programme'])->where('slug', '[A-Za-z0-9-]+')->name('academics.programme');
Route::get('/admissions', [SiteController::class, 'admissions'])->name('admissions');
Route::post('/admissions/apply', [SiteController::class, 'storeAdmissionApplication'])->middleware('throttle:contact')->name('admissions.apply');
Route::get('/leadership', [SiteController::class, 'leadership'])->name('leadership');
Route::get('/news', [SiteController::class, 'news'])->name('news.index');
Route::get('/news/{slug}', [SiteController::class, 'newsShow'])->where('slug', '[A-Za-z0-9-]+')->name('news.show');
Route::get('/events', [SiteController::class, 'events'])->name('events.index');
Route::get('/events/{slug}', [SiteController::class, 'eventShow'])->where('slug', '[A-Za-z0-9-]+')->name('events.show');
Route::get('/announcements', [SiteController::class, 'announcements'])->name('announcements.index');
Route::get('/gallery', [SiteController::class, 'gallery'])->name('gallery');
Route::get('/faq', [SiteController::class, 'faq'])->name('faq');
Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
Route::post('/contact', [SiteController::class, 'storeContact'])->middleware('throttle:contact')->name('contact.send');
Route::post('/newsletter', [SiteController::class, 'subscribeNewsletter'])->middleware('throttle:contact')->name('newsletter.subscribe');
Route::get('/payments/return', [PaymentController::class, 'providerReturn'])->name('payments.return');

Route::get('/portal', [PortalController::class, 'index'])->middleware('portal.enabled')->name('portal');
Route::get('/designed-by', [SiteController::class, 'credit'])->name('credit');
Route::get('/newsletter/unsubscribe/{subscriber}', [SiteController::class, 'unsubscribe'])->whereNumber('subscriber')->middleware('signed')->name('newsletter.unsubscribe');
Route::get('/check-result', [ResultCheckController::class, 'form'])->name('result-check');
Route::post('/check-result', [ResultCheckController::class, 'lookup'])->middleware('throttle:results')->name('result-check.lookup');
Route::get('/check-result/{student}/{term}', [ResultCheckController::class, 'report'])->whereNumber(['student', 'term'])->middleware(['signed', 'throttle:results'])->name('result-check.report');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login.submit');
    Route::post('/student-login', [StudentAccessController::class, 'login'])->middleware('throttle:student-auth')->name('student.login');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth')->name('password.update');
});
Route::middleware(['auth', 'portal.enabled'])->group(function (): void {
    Route::get('/portal/dashboard', [PortalController::class, 'dashboard'])->name('portal.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/portal/results/entry', fn () => redirect()->route('staff.results'))->middleware('role:Teacher,Results Admin,Super Admin')->name('results.entry');
    Route::post('/portal/results/entry', [ResultsController::class, 'store'])->middleware('role:Teacher,Results Admin,Super Admin')->name('results.store');
    Route::post('/portal/results/{result}/publish', [ResultsController::class, 'publish'])->middleware('permission:publish results')->name('results.publish');
    Route::get('/portal/results/{result}/report', [ResultsController::class, 'report'])->name('results.report');
    Route::get('/portal/reports/{student}/{term}', [ReportController::class, 'show'])->whereNumber(['student', 'term'])->name('reports.show');
    Route::middleware('role:Teacher,Results Admin,Super Admin')->prefix('portal/staff')->name('staff.')->group(function (): void {
        Route::get('/results', [StaffResultsController::class, 'home'])->name('results');
        Route::get('/results/class', [StaffResultsController::class, 'students'])->name('results.class');
        Route::get('/results/archive', [StaffResultsController::class, 'archive'])->name('results.archive');
        Route::post('/results/publish', [StaffResultsController::class, 'publishClass'])->middleware('permission:publish results')->name('results.publish-class');
        Route::post('/results/unpublish', [StaffResultsController::class, 'unpublishClass'])->middleware('permission:publish results')->name('results.unpublish-class');
        Route::get('/results/student/{student}', [StaffResultsController::class, 'studentSheet'])->whereNumber('student')->name('results.student');
        Route::post('/results/student/{student}', [StaffResultsController::class, 'saveStudentSheet'])->whereNumber('student')->name('results.student.save');
        Route::get('/results/subject/{assignment}', [StaffResultsController::class, 'subjectSheet'])->whereNumber('assignment')->name('results.subject');
        Route::post('/results/subject/{assignment}', [StaffResultsController::class, 'saveSubjectSheet'])->whereNumber('assignment')->name('results.subject.save');
    });
    Route::post('/portal/fees/{assignment}/checkout/{gateway}', [PaymentController::class, 'checkout'])->where('gateway', 'paystack|flutterwave|moniepoint')->middleware('throttle:payment-initiation')->name('payments.checkout');
});

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// One-segment CMS pages are deliberately last so they cannot shadow system or collection routes.
Route::get('/{slug}', [SiteController::class, 'page'])->where('slug', '[A-Za-z0-9-]+')->name('pages.show');
