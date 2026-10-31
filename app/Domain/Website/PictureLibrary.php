<?php

namespace App\Domain\Website;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Keeps the website's pictures in one place you can move between hosts:
 *  - lists every picture the database points to and which of them are missing as files;
 *  - packs every picture file into one ZIP (download it from the old site);
 *  - unpacks such a ZIP into the picture folder (upload it to the new site, or copy its files into the project).
 */
final class PictureLibrary
{
    /** table => columns that hold a picture path */
    private const REFERENCES = [
        'media_assets' => ['path'],
        'school_settings' => ['logo_path', 'favicon_path'],
        'hero_slides' => ['image_path', 'mobile_image_path'],
        'gallery_images' => ['image_path'],
        'academic_programmes' => ['image_path'],
        'leadership_profiles' => ['photo_path'],
    ];

    private const ALLOWED = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'ico', 'pdf'];

    private const MAX_FILE = 10 * 1024 * 1024;

    /** @return Collection<int, array{table: string, column: string, path: string, exists: bool}> */
    public function references(): Collection
    {
        $rows = collect();
        foreach (self::REFERENCES as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }
                $query = DB::table($table)->whereNotNull($column)->where($column, '!=', '');
                if ($table === 'media_assets' && Schema::hasColumn('media_assets', 'disk')) {
                    $query->where('disk', 'public');
                }
                foreach ($query->pluck($column) as $path) {
                    $path = trim((string) $path);
                    if ($path === '' || preg_match('#^https?://#i', $path)) {
                        continue;
                    }
                    $rows->push(['table' => $table, 'column' => $column, 'path' => $path, 'exists' => Storage::disk('public')->exists($path)]);
                }
            }
        }

        return $rows->unique(fn ($row) => $row['path'])->values();
    }

    public function missing(): Collection
    {
        return $this->references()->where('exists', false)->values();
    }

    /** Every picture file in the public disk. @return array<int, string> */
    public function files(): array
    {
        return collect(Storage::disk('public')->allFiles())
            ->filter(fn ($path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::ALLOWED, true) && ! str_contains($path, '/.') && ! str_starts_with($path, '.'))
            ->values()->all();
    }

    /** Pack every picture into a temporary ZIP and return its path. */
    public function zip(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['zip' => 'This server cannot create ZIP files.']);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pictures');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw ValidationException::withMessages(['zip' => 'The ZIP file could not be created.']);
        }
        $manifest = "path,used_by\n";
        $used = $this->references()->groupBy('path');
        foreach ($this->files() as $path) {
            $full = Storage::disk('public')->path($path);
            if (is_file($full)) {
                $zip->addFile($full, $path);
                $manifest .= '"'.str_replace('"', '""', $path).'","'.$used->get($path, collect())->map(fn ($row) => $row['table'].'.'.$row['column'])->implode(' ').'"'."\n";
            }
        }
        $zip->addFromString('manifest.csv', $manifest);
        $zip->close();

        return $tmp;
    }

    /**
     * Unpack a ZIP of pictures into the public disk. Existing files are never overwritten.
     *
     * @return array{added: int, skipped: int, rejected: array<int, string>}
     */
    public function import(string $zipPath): array
    {
        $zip = new ZipArchive();
        if (! class_exists(ZipArchive::class) || $zip->open($zipPath) !== true) {
            throw ValidationException::withMessages(['zip' => 'That file is not a valid ZIP.']);
        }
        $added = 0;
        $skipped = 0;
        $rejected = [];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < min($zip->numFiles, 5000); $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_ends_with($name, '/') || $name === 'manifest.csv') {
                continue;
            }
            $clean = str_replace('\\', '/', $name);
            $extension = strtolower(pathinfo($clean, PATHINFO_EXTENSION));
            // Plain relative paths only, with an allowed extension: no "..", no hidden files, no scripts.
            if ($clean === '' || str_starts_with($clean, '/') || str_contains($clean, '..') || preg_match('#(^|/)\.#', $clean) || strlen($clean) > 250 || ! in_array($extension, self::ALLOWED, true)) {
                $rejected[] = $name;
                continue;
            }
            $stat = $zip->statIndex($i);
            if (($stat['size'] ?? 0) > self::MAX_FILE) {
                $rejected[] = $name.' (too large)';
                continue;
            }
            $content = $zip->getFromIndex($i);
            $mime = $content === false ? '' : (string) $finfo->buffer($content);
            if ($content === false || ! (str_starts_with($mime, 'image/') || $mime === 'application/pdf')) {
                $rejected[] = $name.' (not a picture)';
                continue;
            }
            if (Storage::disk('public')->exists($clean)) {
                $skipped++;
                continue;
            }
            Storage::disk('public')->put($clean, $content);
            $added++;
        }
        $zip->close();

        return ['added' => $added, 'skipped' => $skipped, 'rejected' => $rejected];
    }
}
