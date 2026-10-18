<?php

namespace App\Domain\Results\Services;

use App\Models\AuditLog;
use App\Models\SchoolSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Issues PINs and prints them on A4 slips. Used by the Students list (selected students) and by the
 * PIN slips page (a whole class, one arm, or the whole school). PINs exist only on the printed slips.
 */
final class PinSlipService
{
    /** @param Collection<int, \App\Models\Student> $students  @return Collection<int, array{student: \App\Models\Student, pin: string}> */
    public function issue(Collection $students): Collection
    {
        $pins = app(StudentPinService::class);
        $slips = $students->map(fn ($student) => ['student' => $student->loadMissing(['schoolClass', 'arm']), 'pin' => $pins->issue($student)]);
        AuditLog::record(auth()->user(), 'students.pins_issued', null, ['count' => $slips->count()]);

        return $slips;
    }

    /** @param Collection<int, array{student: \App\Models\Student, pin: string}> $slips */
    public function pdf(Collection $slips): string
    {
        return Pdf::loadView('portal.pin-slips', ['slips' => $slips->values(), 'settings' => SchoolSettings::current(), 'loginUrl' => route('login.student')])
            ->setPaper('a4', 'portrait')
            ->output();
    }

    /** One PDF of slips for the given students. */
    public function download(Collection $students, ?string $name = null): StreamedResponse
    {
        $pdf = $this->pdf($this->issue($students));

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, ($name ?: 'student-pin-slips-'.now()->format('Ymd-His')).'.pdf', ['Content-Type' => 'application/pdf']);
    }

    /** The whole school: one PDF per class inside one ZIP, so no single file gets too big to build. */
    public function downloadSchoolZip(Collection $students): StreamedResponse
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['scope' => 'This server cannot create ZIP files. Print one class at a time instead.']);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pinslips');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw ValidationException::withMessages(['scope' => 'The ZIP file could not be created.']);
        }
        $used = [];
        foreach ($students->groupBy(fn ($student) => $student->schoolClass?->name ?? 'No class') as $className => $group) {
            $file = Str::slug((string) $className) ?: 'class';
            $file = $used[$file] = isset($used[$file]) ? $file.'-'.(count($used) + 1) : $file;
            $zip->addFromString('pin-slips-'.$file.'.pdf', $this->pdf($this->issue($group->values())));
        }
        $zip->close();

        return response()->streamDownload(function () use ($tmp): void {
            readfile($tmp);
            @unlink($tmp);
        }, 'student-pin-slips-school-'.now()->format('Ymd-His').'.zip', ['Content-Type' => 'application/zip']);
    }
}
