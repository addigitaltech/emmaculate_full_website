<?php

namespace App\Domain\Results\Services;

use App\Models\Arm;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Imports students from a CSV file so a school can move an existing register in one go.
 * Required columns: student_number, first_name, last_name. Optional: other_names, gender,
 * date_of_birth (YYYY-MM-DD or DD/MM/YYYY), class, arm, status.
 * Existing students are matched on student_number and updated; nothing is ever deleted.
 */
final class StudentCsvImporter
{
    private const MAX_ROWS = 2000;

    /** @return array{created: int, updated: int, errors: array<int, string>} */
    public function import(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The file could not be read.']);
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'The file is empty.']);
        }
        $header = array_map(fn ($name) => Str::snake(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $name))), $header);
        foreach (['student_number', 'first_name', 'last_name'] as $required) {
            if (! in_array($required, $header, true)) {
                fclose($handle);
                throw ValidationException::withMessages(['file' => 'Missing required column: '.$required.'. Expected columns: student_number, first_name, last_name, other_names, gender, date_of_birth, class, arm, status.']);
            }
        }

        $classes = SchoolClass::query()->with('arms')->get()->keyBy(fn (SchoolClass $class) => Str::lower(trim($class->name)));
        $created = 0;
        $updated = 0;
        $errors = [];
        $line = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if ($line - 1 > self::MAX_ROWS) {
                    $errors[] = 'Stopped after '.self::MAX_ROWS.' rows. Split the file and import the rest separately.';
                    break;
                }
                if (count(array_filter($row, fn ($cell) => trim((string) $cell) !== '')) === 0) {
                    continue;
                }
                $data = [];
                foreach ($header as $index => $name) {
                    $data[$name] = isset($row[$index]) ? trim((string) $row[$index]) : '';
                }
                $number = $data['student_number'] ?? '';
                if ($number === '' || ($data['first_name'] ?? '') === '' || ($data['last_name'] ?? '') === '') {
                    $errors[] = 'Row '.$line.': student_number, first_name and last_name are required.';
                    continue;
                }

                $classId = null;
                $armId = null;
                if (($data['class'] ?? '') !== '') {
                    $class = $classes->get(Str::lower($data['class']));
                    if (! $class) {
                        $errors[] = 'Row '.$line.': class "'.$data['class'].'" does not exist. Create it first.';
                        continue;
                    }
                    $classId = $class->id;
                    if (($data['arm'] ?? '') !== '') {
                        $arm = $class->arms->first(fn (Arm $candidate) => Str::lower($candidate->name) === Str::lower($data['arm']));
                        if (! $arm) {
                            $errors[] = 'Row '.$line.': arm "'.$data['arm'].'" is not part of class '.$class->name.'.';
                            continue;
                        }
                        $armId = $arm->id;
                    }
                }

                $dob = null;
                if (($data['date_of_birth'] ?? '') !== '') {
                    try {
                        $dob = preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $data['date_of_birth'])
                            ? Carbon::createFromFormat('d/m/Y', $data['date_of_birth'])
                            : Carbon::parse($data['date_of_birth']);
                    } catch (\Throwable) {
                        $errors[] = 'Row '.$line.': date_of_birth "'.$data['date_of_birth'].'" is not a valid date.';
                        continue;
                    }
                }

                $gender = ucfirst(Str::lower($data['gender'] ?? ''));
                $gender = in_array($gender, ['Female', 'Male'], true) ? $gender : null;
                $status = Str::lower($data['status'] ?? '') ?: 'active';

                $student = Student::query()->firstOrNew(['student_number' => $number]);
                $isNew = ! $student->exists;
                $student->fill([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'other_names' => ($data['other_names'] ?? '') !== '' ? $data['other_names'] : $student->other_names,
                    'gender' => $gender ?? $student->gender,
                    'date_of_birth' => $dob ?? $student->date_of_birth,
                    'school_class_id' => $classId ?? $student->school_class_id,
                    'arm_id' => $classId ? $armId : $student->arm_id,
                    'status' => $status,
                ]);
                try {
                    $student->save();
                } catch (ValidationException $exception) {
                    $errors[] = 'Row '.$line.': '.collect($exception->errors())->flatten()->implode(' ');
                    continue;
                }
                $isNew ? $created++ : $updated++;
            }
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            fclose($handle);
            throw $exception;
        }
        fclose($handle);

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }
}
