<?php

namespace App\Domain\Results\Services;

use App\Models\SchoolClass;
use Illuminate\Validation\ValidationException;

/**
 * Builds an editable CSV (opens in Excel or Google Sheets) for adding a whole school at once: one block of empty
 * lines for every class and arm, with the class and arm already filled in. The office fills in the names and
 * uploads the file; lines left empty are ignored, and Student IDs are created automatically.
 */
final class StudentTemplateGenerator
{
    public const HEADERS = ['student_id', 'first_name', 'last_name', 'other_names', 'gender', 'date_of_birth', 'class', 'arm', 'status'];

    /** Largest file the importer accepts in one go. */
    public const MAX_LINES = 2000;

    /** @param array<int, int|string>|null $classIds  null means every active class */
    public function csv(?array $classIds = null, int $linesPerArm = 40): string
    {
        $linesPerArm = max(1, min(200, $linesPerArm));
        $classes = SchoolClass::query()->with(['arms' => fn ($query) => $query->orderBy('name')])
            ->where('is_active', true)
            ->when($classIds, fn ($query, $ids) => $query->whereIn('id', $ids))
            ->orderBy('name')->get();

        if ($classes->isEmpty()) {
            throw ValidationException::withMessages(['classes' => 'Create at least one class first (Classes & subjects > Classes).']);
        }

        $blocks = $classes->flatMap(fn (SchoolClass $class) => $class->arms->isEmpty()
            ? [[$class->name, '']]
            : $class->arms->map(fn ($arm) => [$class->name, $arm->name])->all());
        if ($blocks->count() * $linesPerArm > self::MAX_LINES) {
            throw ValidationException::withMessages(['lines_per_arm' => 'That is more than '.self::MAX_LINES.' lines. Choose fewer classes or fewer lines per arm, and make one file per group of classes.']);
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF"); // lets Excel read accents and Nigerian names correctly
        fputcsv($handle, self::HEADERS);
        foreach ($blocks as [$className, $armName]) {
            for ($i = 0; $i < $linesPerArm; $i++) {
                fputcsv($handle, ['', '', '', '', '', '', $className, $armName, 'active']);
            }
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
