<?php

namespace App\Http\Controllers;

use App\Domain\Auth\Support\PortalAccess;
use App\Domain\Results\Services\StudentLookup;
use App\Domain\Results\Services\StudentPinService;
use App\Domain\Results\Services\TermReportBuilder;
use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Result;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public "check my result" page for when student and parent portals are switched off.
 * Admission number + surname (+ PIN when the system setting is on), current published term only, read-only, links expire after 20 minutes.
 */
class ResultCheckController extends Controller
{
    private const GENERIC_ERROR = 'No published result was found for those details. Check the admission number, the surname in CAPITAL LETTERS and the PIN, or contact the school.';

    public function form(): View
    {
        return $this->page(['enabled' => PortalAccess::checkerEnabled(), 'student' => null, 'links' => []]);
    }

    public function lookup(Request $request, StudentLookup $lookup, StudentPinService $pins)
    {
        abort_unless(PortalAccess::checkerEnabled(), 404);
        $pinRequired = PortalAccess::checkerPinRequired();
        $data = $request->validate([
            'admission_number' => ['required', 'string', 'max:60'],
            'surname' => ['required', 'string', 'max:80'],
        ] + ($pinRequired ? ['pin' => ['required', 'string', 'regex:/^\s*\d{6}\s*$/']] : []), ['pin.regex' => 'Enter the 6-digit PIN from your PIN slip.']);

        $key = 'result-check:'.hash('sha256', StudentLookup::normaliseNumber($data['admission_number']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['admission_number' => 'Too many attempts. Please wait 15 minutes, or contact the school.'])->onlyInput('admission_number');
        }

        $student = $lookup->find($data['admission_number'], $data['surname']);
        if (! $student) {
            RateLimiter::hit($key, 900);

            return back()->withErrors(['admission_number' => self::GENERIC_ERROR])->onlyInput('admission_number');
        }

        // The PIN is checked only while "Require PIN to check results" is switched on in the system settings.
        $outcome = $pinRequired ? $pins->attempt($student, (string) ($data['pin'] ?? '')) : 'ok';
        if ($outcome === 'no_pin') {
            return back()->withErrors(['admission_number' => 'No PIN has been issued for this student yet. Please contact the school office for your PIN slip.'])->onlyInput('admission_number');
        }
        if ($outcome === 'locked') {
            return back()->withErrors(['admission_number' => 'Checking is locked for a while after too many wrong PINs. Try again later, or contact the school to reset your PIN.'])->onlyInput('admission_number');
        }
        if ($outcome !== 'ok') {
            RateLimiter::hit($key, 900);

            return back()->withErrors(['admission_number' => self::GENERIC_ERROR])->onlyInput('admission_number');
        }

        $term = AcademicTerm::query()
            ->with('session')
            ->where('is_current', true)
            ->whereHas('session', fn ($query) => $query->where('is_active', true))
            ->first();
        if (! $term || ! Result::published()->where('student_id', $student->id)->where('academic_term_id', $term->id)->exists()) {
            return back()->withErrors(['admission_number' => self::GENERIC_ERROR])->onlyInput('admission_number');
        }
        if (PortalAccess::feesBlockResults($student)) {
            return back()->withErrors(['admission_number' => self::GENERIC_ERROR])->onlyInput('admission_number');
        }

        RateLimiter::clear($key);
        AuditLog::record(null, 'results.public_check', $student);
        $links = [[
            'term' => $term,
            'url' => URL::temporarySignedRoute('result-check.report', now()->addMinutes(20), ['student' => $student->id, 'term' => $term->id]),
        ]];

        return $this->page(['enabled' => true, 'student' => $student->loadMissing('schoolClass', 'arm'), 'links' => $links]);
    }

    public function report(Request $request, Student $student, AcademicTerm $term, TermReportBuilder $builder)
    {
        abort_unless(PortalAccess::checkerEnabled(), 404);
        abort_unless($student->status === 'active', 404);
        abort_unless($term->is_current && $term->session()->where('is_active', true)->exists(), 404);
        abort_unless(Result::published()->where('student_id', $student->id)->where('academic_term_id', $term->id)->exists(), 404);
        abort_if(PortalAccess::feesBlockResults($student), 403);

        $data = $builder->build($student, $term, false);
        if ($request->query('download') === 'pdf') {
            AuditLog::record(null, 'results.public_report_downloaded', $student, ['term_id' => $term->id]);

            return Pdf::loadView('portal.report', $data + ['isPdf' => true])->setPaper('a4', 'portrait')
                ->download('report-'.Str::slug($student->fullName()).'-'.$term->id.'.pdf');
        }

        $pdfUrl = URL::temporarySignedRoute('result-check.report', now()->addMinutes(20), ['student' => $student->id, 'term' => $term->id, 'download' => 'pdf']);

        return view('portal.report', $data + ['isPdf' => false, 'pdfUrl' => $pdfUrl]);
    }

    private function page(array $data): View
    {
        return view('site.check-result', $data + ['heroImage' => asset('storage/migrated-images/students-group.jpg')]);
    }
}
