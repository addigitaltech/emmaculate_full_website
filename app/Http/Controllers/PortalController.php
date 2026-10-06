<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\FooterSection;
use App\Models\NavigationItem;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentTransaction;
use App\Models\PortalLink;
use App\Models\Result;
use App\Models\SchoolSettings;
use App\Models\StudentFeeAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->user()) {
            return view('portal.dashboard', $this->layoutData() + $this->dashboardData($request));
        }
        return view('site.portal', $this->layoutData() + [
            'portalLinks' => PortalLink::query()->publiclyVisible()->get(),
        ]);
    }

    public function dashboard(Request $request): View
    {
        abort_unless($request->user(), 401);
        return view('portal.dashboard', $this->layoutData() + $this->dashboardData($request));
    }

    private function layoutData(): array
    {
        return [
            'settings' => SchoolSettings::current(),
            'navigation' => NavigationItem::query()->where('menu', 'main')->whereNull('parent_id')->where('is_visible', true)->with('children')->orderBy('sort_order')->get(),
            'footerSections' => FooterSection::query()->where('is_visible', true)->orderBy('sort_order')->get(),
        ];
    }

    private function dashboardData(Request $request): array
    {
        $user = $request->user();
        $kind = 'account';
        $profile = null;
        $results = collect();
        $children = collect();
        $assignments = collect();
        $payments = collect();
        $fees = collect();
        $activity = collect();
        $pendingResults = collect();
        $reportTerms = collect();
        $childTerms = [];
        $pendingTotal = 0;

        if ($user->hasRole('Student')) {
            $kind = 'student';
            $profile = $user->studentProfile()->with(['schoolClass', 'arm'])->first();
            if ($profile) {
                $reportTerms = $this->termsWithPublishedResults([$profile->id])[$profile->id] ?? collect();
                $results = Result::published()->where('student_id', $profile->id)->with(['subject', 'academicSession', 'academicTerm'])->orderByDesc('academic_session_id')->orderByDesc('academic_term_id')->limit(30)->get();
                $fees = StudentFeeAssignment::query()->where('student_id', $profile->id)->whereIn('status', ['unpaid', 'overdue'])->with('feeType')->orderBy('due_date')->get();
                $payments = PaymentTransaction::query()->where('student_id', $profile->id)->with(['feeAssignment.feeType', 'receipt'])->whereIn('status', ['successful', 'pending', 'failed'])->latest()->limit(10)->get();
            }
        } elseif ($user->hasRole('Parent')) {
            $kind = 'parent';
            $profile = $user->parentProfile()->with(['students.schoolClass', 'students.arm'])->first();
            $children = $profile?->students ?? collect();
            $ids = $children->modelKeys();
            $childTerms = $this->termsWithPublishedResults($ids);
            $results = Result::published()->whereIn('student_id', $ids)->with(['student', 'subject', 'academicSession', 'academicTerm'])->orderByDesc('academic_session_id')->orderByDesc('academic_term_id')->limit(50)->get();
            $fees = StudentFeeAssignment::query()->whereIn('student_id', $ids)->whereIn('status', ['unpaid', 'overdue'])->with(['student', 'feeType'])->orderBy('due_date')->limit(100)->get();
            $payments = PaymentTransaction::query()->whereIn('student_id', $ids)->with(['student', 'feeAssignment.feeType', 'receipt'])->whereIn('status', ['successful', 'pending', 'failed'])->latest()->limit(20)->get();
        } elseif ($user->hasRole('Teacher')) {
            $kind = 'teacher';
            $profile = $user->teacherProfile()->first();
            if ($profile) {
                $assignments = $profile->assignments()->with(['schoolClass', 'arm', 'subject'])->get();
            }
        } else {
            $kind = 'admin';
            if ($user->can('view audit logs')) {
                $activity = AuditLog::query()->latest('created_at')->with('actor')->limit(8)->get();
            }
            if ($user->can('publish results')) {
                $pendingTotal = Result::query()->whereIn('status', ['pending', 'draft'])->count();
            }
            if ($user->can('manage payments')) {
                $payments = PaymentTransaction::query()->with(['student', 'feeAssignment.feeType', 'receipt'])->latest()->limit(20)->get();
            }
        }

        $gatewayOptions = PaymentGatewayConfig::query()->where('is_enabled', true)->where('supports_online_checkout', true)->get()
            ->filter(fn (PaymentGatewayConfig $provider) => match ($provider->driver) {
                'paystack' => filled(config('services.paystack.secret_key')),
                'flutterwave' => filled(config('services.flutterwave.secret_key')) && filled(config('services.flutterwave.secret_hash')),
                default => false,
            })
            ->map(fn (PaymentGatewayConfig $provider) => ['driver' => $provider->driver, 'name' => $provider->display_name])->values();

        return compact('user', 'kind', 'profile', 'results', 'children', 'assignments', 'payments', 'fees', 'gatewayOptions', 'activity', 'pendingResults', 'reportTerms', 'childTerms', 'pendingTotal');
    }

    /**
     * Terms that have at least one published result, per student, newest first.
     *
     * @param  array<int, int|string>  $studentIds
     * @return array<int|string, \Illuminate\Support\Collection<int, AcademicTerm>>
     */
    private function termsWithPublishedResults(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }
        $pairs = Result::published()->whereIn('student_id', $studentIds)->get(['student_id', 'academic_term_id'])
            ->unique(fn ($row) => $row->student_id.'-'.$row->academic_term_id);
        $terms = AcademicTerm::query()->with('session')->whereIn('id', $pairs->pluck('academic_term_id')->unique())
            ->orderByDesc('academic_session_id')->orderByDesc('sequence')->get()->keyBy('id');
        $byStudent = [];
        foreach ($studentIds as $id) {
            $byStudent[$id] = $pairs->where('student_id', $id)->map(fn ($row) => $terms->get($row->academic_term_id))->filter()->sortByDesc(fn ($term) => ($term->academic_session_id * 10) + $term->sequence)->values();
        }

        return $byStudent;
    }
}
