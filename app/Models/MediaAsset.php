<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uploaded_by', 'original_name', 'path', 'disk', 'mime_type', 'size_bytes',
        'alt_text', 'caption', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'size_bytes' => 'integer'];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
