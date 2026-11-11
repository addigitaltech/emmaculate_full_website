<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

    /**
     * Make every authorized resource available from Filament's top-level search.
     * Sensitive credential, token and opaque JSON columns are deliberately excluded.
     */
    public static function getGloballySearchableAttributes(): array
    {
        static $columns = [];
        $key = static::class;

        if (! isset($columns[$key])) {
            $excluded = [
                'password', 'password_reset_token', 'remember_token', 'pin_hash', 'pin',
                'secret', 'secret_key', 'secret_hash', 'api_key', 'token', 'payload',
                'metadata', 'content_blocks', 'theme_tokens', 'global_settings',
            ];
            $table = (new (static::getModel()))->getTable();
            $searchableTypes = ['string', 'text', 'mediumText', 'longText', 'date', 'datetime', 'timestamp', 'time', 'enum'];
            $columns[$key] = array_values(array_filter(
                Schema::getColumnListing($table),
                fn (string $column): bool => ! in_array(Str::lower($column), $excluded, true)
                    && in_array(Schema::getColumnType($table, $column), $searchableTypes, true),
            ));
        }

        return $columns[$key];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        foreach (['title', 'name', 'label', 'full_name', 'question', 'student_number', 'email', 'reference', 'receipt_number', 'code'] as $column) {
            if (filled($record->getAttribute($column))) {
                return (string) $record->getAttribute($column);
            }
        }

        return class_basename($record).' #'.$record->getKey();
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];
        foreach (['status', 'category', 'school_name', 'short_name', 'first_name', 'last_name', 'student_number', 'email'] as $column) {
            if (filled($record->getAttribute($column))) {
                $details[Str::headline($column)] = (string) $record->getAttribute($column);
            }
        }

        return $details;
    }
}
