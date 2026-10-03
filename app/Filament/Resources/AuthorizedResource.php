<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class AuthorizedResource extends Resource
{
    protected static ?string $requiredPermission = null;
    protected static bool $readOnly = false;
    protected static bool $singleton = false;

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::authorized();
    }

    public static function canCreate(): bool
    {
        return static::authorized() && ! static::$readOnly && ! static::$singleton;
    }

    public static function canEdit(Model $record): bool
    {
        return static::authorized() && ! static::$readOnly;
    }

    public static function canDelete(Model $record): bool
    {
        return static::authorized() && ! static::$readOnly && ! static::$singleton;
    }

    public static function canDeleteAny(): bool
    {
        return static::authorized() && ! static::$readOnly && ! static::$singleton;
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::authorized() && ! static::$readOnly && ! static::$singleton;
    }

    public static function canForceDeleteAny(): bool
    {
        return static::authorized() && ! static::$readOnly && ! static::$singleton;
    }

    protected static function authorized(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole('Super Admin')) {
            return true;
        }
        $permission = static::$requiredPermission;
        return $permission !== null && $user->can($permission);
    }
}
