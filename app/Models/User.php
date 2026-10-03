<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([
            'Super Admin', 'Website Admin', 'Results Admin', 'Finance Admin', 'Content Editor',
        ]);
    }

    public function studentProfile()
    {
        return $this->hasOne(Student::class);
    }

    public function teacherProfile()
    {
        return $this->hasOne(Teacher::class);
    }

    public function parentProfile()
    {
        return $this->hasOne(ParentProfile::class, 'user_id');
    }
}
