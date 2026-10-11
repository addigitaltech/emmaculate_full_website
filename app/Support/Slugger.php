<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Makes the web address part ("slug") of a title, so editors never have to type one. */
final class Slugger
{
    public static function unique(Model $model, string $title): string
    {
        $base = Str::limit(Str::slug($title), 80, '') ?: 'item';
        $slug = $base;
        $counter = 2;

        while (self::taken($model, $slug)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private static function taken(Model $model, string $slug): bool
    {
        $query = $model->newQuery()->withoutGlobalScopes()->where('slug', $slug);
        if ($model->exists) {
            $query->whereKeyNot($model->getKey());
        }

        return $query->exists();
    }
}
