<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class InitialiseSchool extends Command
{
    protected $signature = 'school:init';
    protected $description = 'Seed first-time data and (optionally) create the first Super Admin from SCHOOL_ADMIN_* environment variables. Safe to run on every deploy.';

    public function handle(): int
    {
        if (! Role::query()->where('guard_name', 'web')->exists()) {
            $this->info('Seeding roles, public content and academic defaults...');
            $this->call('db:seed', ['--force' => true]);
        } else {
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\DesignRefreshSeeder', '--force' => true]);
        }

        $this->bootstrapAdmin();

        return self::SUCCESS;
    }

    private function bootstrapAdmin(): void
    {
        $name = trim((string) env('SCHOOL_ADMIN_NAME'));
        $email = Str::lower(trim((string) env('SCHOOL_ADMIN_EMAIL')));
        $password = (string) env('SCHOOL_ADMIN_PASSWORD');
        if ($name === '' || $email === '' || $password === '') {
            return;
        }
        if (User::query()->whereHas('roles', fn (Builder $query) => $query->where('name', 'Super Admin')->where('guard_name', 'web'))->exists()) {
            $this->line('A Super Admin already exists; SCHOOL_ADMIN_* variables are ignored. You can delete them now.');
            return;
        }
        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            ['name' => ['required', 'string', 'max:180'], 'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'], 'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()]],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return;
        }
        $user = User::query()->create(['name' => $name, 'email' => $email, 'email_verified_at' => now(), 'password' => Hash::make($password)]);
        $user->assignRole('Super Admin');
        AuditLog::record(null, 'identity.super_admin.created', $user, ['email_hash' => hash('sha256', $email), 'via' => 'school:init']);
        $this->info('Super Admin account created. Remove the SCHOOL_ADMIN_* environment variables now.');
    }
}
