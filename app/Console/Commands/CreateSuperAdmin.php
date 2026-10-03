<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateSuperAdmin extends Command
{
    protected $signature = 'school:create-super-admin';
    protected $description = 'Create the first school Super Admin using an interactive, non-logged secret prompt';

    public function handle(): int
    {
        if (! Role::query()->where('name', 'Super Admin')->where('guard_name', 'web')->exists()) {
            $this->error('Run php artisan migrate --seed first so school roles are available.');
            return self::FAILURE;
        }
        if (User::query()->whereHas('roles', fn (Builder $query) => $query->where('name', 'Super Admin')->where('guard_name', 'web'))->exists()) {
            $this->error('A Super Admin already exists. Use the authenticated user-management workflow for any additional account.');
            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Administrator name'));
        $email = Str::lower(trim((string) $this->ask('Administrator email')));
        $password = (string) $this->secret('Password (hidden; at least 12 characters)');
        $confirmation = (string) $this->secret('Repeat password');
        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            ['name' => ['required', 'string', 'max:180'], 'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'], 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()]],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) $this->error($error);
            return self::FAILURE;
        }

        $user = User::query()->create(['name' => $name, 'email' => $email, 'email_verified_at' => now(), 'password' => Hash::make($password)]);
        $user->assignRole('Super Admin');
        AuditLog::record(null, 'identity.super_admin.created', $user, ['email_hash' => hash('sha256', $email)]);
        $this->info('Super Admin account created. Keep the password manager entry secure and remove any temporary deployment access.');
        return self::SUCCESS;
    }
}
