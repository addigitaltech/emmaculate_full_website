<?php

namespace App\Domain\Backups;

use App\Models\AcademicTerm;
use App\Models\ParentProfile;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

final class BackupArchiveService
{
    public function resultsZip(?AcademicTerm $term = null): string
    {
        $termQuery = $term ? 'term-'.$term->id : 'all-terms';
        $path = storage_path('app/'.Str::uuid().'-results.zip');
        $zip = $this->zip($path);
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['student_id', 'student_name', 'subject_code', 'subject_name', 'session', 'term', 'class', 'arm', 'ca1_score', 'ca2_score', 'ca3_score', 'exam_score', 'total_score', 'is_offered', 'grade', 'remark', 'teacher_remark', 'principal_remark', 'status', 'published_at']);

        Result::query()->with(['student', 'subject', 'academicSession', 'academicTerm', 'schoolClass', 'arm'])
            ->when($term, fn ($query) => $query->where('academic_term_id', $term->id))
            ->orderBy('id')->chunkById(500, function ($results) use ($handle): void {
                foreach ($results as $result) {
                    fputcsv($handle, [
                        $result->student?->student_number, $result->student?->fullName(), $result->subject?->code,
                        $result->subject?->name, $result->academicSession?->name, $result->academicTerm?->name,
                        $result->schoolClass?->name, $result->arm?->name, $result->ca1_score, $result->ca2_score,
                        $result->ca3_score, $result->exam_score, $result->total_score, $result->is_offered ? 1 : 0,
                        $result->grade, $result->remark, $result->teacher_remark, $result->principal_remark,
                        $result->status, $result->published_at?->toIso8601String(),
                    ]);
                }
            });
        rewind($handle);
        $zip->addFromString('results.csv', stream_get_contents($handle));
        fclose($handle);
        $zip->addFromString('README.txt', "Emmaculate Academy results backup\nScope: {$termQuery}\n\nImport matches students by student_id and subjects by subject_code (or subject_name). It never deletes existing records.\n");
        $zip->close();
        return $path;
    }

    public function peopleZip(?string $passphrase = null): string
    {
        $path = storage_path('app/'.Str::uuid().'-people.zip');
        $zip = $this->zip($path);
        $users = fopen('php://temp', 'w+');
        fputcsv($users, ['email', 'name', 'roles', 'email_verified_at']);
        User::query()->with('roles')->orderBy('id')->chunkById(500, function ($records) use ($users): void {
            foreach ($records as $user) fputcsv($users, [$user->email, $user->name, $user->roles->pluck('name')->implode('|'), $user->email_verified_at?->toIso8601String()]);
        });
        rewind($users); $zip->addFromString('users.csv', stream_get_contents($users)); fclose($users);
        if (filled($passphrase)) {
            $credentials = User::query()->with('roles')->orderBy('id')->get()->map(fn (User $user): array => [
                'email' => $user->email, 'name' => $user->name, 'roles' => $user->roles->pluck('name')->values()->all(), 'password_hash' => $user->getRawOriginal('password'),
            ])->values()->all();
            $zip->addFromString('credentials.enc', $this->encryptCredentials($credentials, $passphrase));
        }

        foreach ([
            'students' => [Student::query()->with(['user', 'schoolClass', 'arm']), ['email', 'name', 'student_id', 'first_name', 'last_name', 'other_names', 'gender', 'date_of_birth', 'class', 'arm', 'status']],
            'teachers' => [Teacher::query()->with('user'), ['email', 'name', 'staff_number', 'first_name', 'last_name', 'other_names', 'phone', 'gender', 'status']],
            'parents' => [ParentProfile::query()->with('user'), ['email', 'name', 'full_name', 'phone']],
        ] as $name => [$query, $header]) {
            $handle = fopen('php://temp', 'w+'); fputcsv($handle, $header);
            $query->orderBy('id')->chunkById(500, function ($records) use ($handle, $name): void {
                foreach ($records as $record) {
                    $data = $name === 'students'
                        ? [$record->user?->email, $record->user?->name, $record->student_number, $record->first_name, $record->last_name, $record->other_names, $record->gender, $record->date_of_birth?->toDateString(), $record->schoolClass?->name, $record->arm?->name, $record->status]
                        : ($name === 'teachers'
                            ? [$record->user?->email, $record->user?->name, $record->staff_number, $record->first_name, $record->last_name, $record->other_names, $record->phone, $record->gender, $record->status]
                            : [$record->user?->email, $record->user?->name, $record->full_name, $record->phone]);
                    fputcsv($handle, $data);
                }
            });
            rewind($handle); $zip->addFromString($name.'.csv', stream_get_contents($handle)); fclose($handle);
        }
        $zip->addFromString('README.txt', "Emmaculate Academy people backup\n\nWhen a passphrase was supplied, credentials.enc contains password hashes encrypted with that passphrase. The passphrase is required to restore accounts with their existing passwords. Plain-text passwords are never exported. Without a passphrase, the archive contains profile data only.\n");
        $zip->close();
        return $path;
    }

    public function importResults(string $path): array
    {
        $zip = $this->open($path); $stream = $zip->getStream('results.csv');
        if (! $stream) throw ValidationException::withMessages(['file' => 'The ZIP must contain results.csv.']);
        $header = fgetcsv($stream); $header = array_map(fn ($value) => Str::snake(trim((string) $value)), $header ?: []);
        foreach (['student_id', 'subject_name', 'session', 'term'] as $required) if (! in_array($required, $header, true)) throw ValidationException::withMessages(['file' => 'results.csv is missing '.$required.'.']);
        $created = 0; $updated = 0; $errors = []; $line = 1;
        DB::transaction(function () use ($stream, $header, &$created, &$updated, &$errors, &$line): void {
            while (($row = fgetcsv($stream)) !== false) {
                $line++; $data = array_combine($header, array_pad($row, count($header), ''));
                $student = Student::query()->where('student_number', trim((string) ($data['student_id'] ?? '')))->first();
                $subject = Subject::query()->where('code', trim((string) ($data['subject_code'] ?? '')))->when(! $data['subject_code'], fn ($q) => $q->where('name', $data['subject_name'] ?? ''))->first();
                $term = AcademicTerm::query()->whereHas('session', fn ($q) => $q->where('name', $data['session'] ?? ''))->where('name', $data['term'] ?? '')->first();
                if (! $student || ! $subject || ! $term) { $errors[] = 'Row '.$line.': student, subject or academic term could not be matched.'; continue; }
                $result = Result::query()->firstOrNew(['student_id' => $student->id, 'subject_id' => $subject->id, 'academic_session_id' => $term->academic_session_id, 'academic_term_id' => $term->id]);
                $isNew = ! $result->exists;
                $result->fill(array_intersect_key($data, array_flip(['ca1_score','ca2_score','ca3_score','exam_score','total_score','grade','remark','teacher_remark','principal_remark','status'])));
                $result->is_offered = (bool) ($data['is_offered'] ?? 1); $result->school_class_id = $student->school_class_id; $result->arm_id = $student->arm_id; $result->save();
                $isNew ? $created++ : $updated++;
            }
        });
        $zip->close(); return compact('created', 'updated', 'errors');
    }

    public function importPeople(string $path, ?string $passphrase = null): array
    {
        $zip = $this->open($path); $updated = 0; $created = 0; $errors = [];
        $credentialsStream = $zip->getStream('credentials.enc');
        if ($credentialsStream) {
            if (blank($passphrase)) throw ValidationException::withMessages(['passphrase' => 'Enter the backup passphrase to restore login accounts.']);
            $credentials = $this->decryptCredentials(stream_get_contents($credentialsStream), $passphrase);
            foreach ($credentials as $credential) {
                $email = trim((string) ($credential['email'] ?? '')); $hash = (string) ($credential['password_hash'] ?? '');
                if (! filter_var($email, FILTER_VALIDATE_EMAIL) || blank($hash)) { $errors[] = 'A credential row was invalid and was skipped.'; continue; }
                $user = User::query()->firstOrNew(['email' => $email]); $isNew = ! $user->exists;
                $user->name = (string) ($credential['name'] ?? $email); if ($isNew) $user->password = Hash::make(Str::random(64)); $user->save();
                DB::table('users')->where('id', $user->id)->update(['password' => $hash]);
                $roles = array_values(array_filter((array) ($credential['roles'] ?? [])));
                if ($roles !== []) $user->syncRoles($roles);
                $isNew ? $created++ : $updated++;
            }
        }
        foreach (['students' => Student::class, 'teachers' => Teacher::class, 'parents' => ParentProfile::class] as $file => $model) {
            $stream = $zip->getStream($file.'.csv'); if (! $stream) continue;
            $header = array_map(fn ($value) => Str::snake(trim((string) $value)), fgetcsv($stream) ?: []); $line = 1;
            while (($row = fgetcsv($stream)) !== false) {
                $line++; $data = array_combine($header, array_pad($row, count($header), '')); $email = trim((string) ($data['email'] ?? '')); $user = $email ? User::query()->where('email', $email)->first() : null;
                if (! $user) { $errors[] = $file.' row '.$line.': no existing user matched by email; no login account was created.'; continue; }
                $query = $model === Student::class ? ['student_number' => $data['student_id'] ?? ''] : ['user_id' => $user->id];
                $record = $model === Student::class && blank($query['student_number']) ? null : $model::query()->firstOrNew($query); if (! $record) { $errors[] = $file.' row '.$line.': student_id is required.'; continue; }
                $record->user_id = $user->id;
                $record->fill($model === Student::class ? ['student_number' => $data['student_id'] ?? null] + array_intersect_key($data, array_flip(['first_name','last_name','other_names','gender','date_of_birth','status'])) : ($model === Teacher::class ? array_intersect_key($data, array_flip(['staff_number','first_name','last_name','other_names','phone','gender','status'])) : ['full_name' => $data['full_name'] ?? $data['name'] ?? '', 'phone' => $data['phone'] ?? null]));
                $record->save(); $record->wasRecentlyCreated ? $created++ : $updated++;
            }
        }
        $zip->close(); return compact('created', 'updated', 'errors');
    }

    private function zip(string $path): ZipArchive { $zip = new ZipArchive(); if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw ValidationException::withMessages(['file' => 'Could not create the backup ZIP.']); return $zip; }
    private function open(string $path): ZipArchive { $zip = new ZipArchive(); if ($zip->open($path) !== true) throw ValidationException::withMessages(['file' => 'The backup ZIP could not be opened.']); return $zip; }

    private function encryptCredentials(array $credentials, string $passphrase): string
    {
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = sodium_crypto_pwhash(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, $passphrase, $salt, SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE);
        $ciphertext = sodium_crypto_secretbox(json_encode($credentials, JSON_THROW_ON_ERROR), $nonce, $key);
        return "EMMACULATE-CREDENTIALS-V2\n".base64_encode($salt).'.'.base64_encode($nonce).'.'.base64_encode($ciphertext);
    }

    private function decryptCredentials(string $payload, string $passphrase): array
    {
        [$header, $encoded] = array_pad(explode("\n", $payload, 2), 2, '');
        if ($header !== 'EMMACULATE-CREDENTIALS-V2') throw ValidationException::withMessages(['passphrase' => 'This credentials file is not a supported backup format.']);
        [$salt, $nonce, $ciphertext] = array_pad(explode('.', trim($encoded), 3), 3, '');
        $salt = base64_decode($salt, true); $nonce = base64_decode($nonce, true); $ciphertext = base64_decode($ciphertext, true);
        if ($salt === false || $nonce === false || $ciphertext === false) throw ValidationException::withMessages(['file' => 'The encrypted credentials file is invalid.']);
        $key = sodium_crypto_pwhash(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, $passphrase, $salt, SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE);
        $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        if ($plain === false) throw ValidationException::withMessages(['passphrase' => 'The backup passphrase is incorrect.']);
        $decoded = json_decode($plain, true);
        if (! is_array($decoded)) throw ValidationException::withMessages(['file' => 'The encrypted credentials file is invalid.']);
        return $decoded;
    }
}
