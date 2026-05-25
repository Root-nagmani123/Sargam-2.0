<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LoginCarouselImage extends Model
{
    protected static ?bool $tableExistsCache = null;

    protected $fillable = [
        'image_path',
        'sort_order',
        'active_inactive',
        'created_by_pk',
        'updated_by_pk',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'active_inactive' => 'boolean',
        'created_by_pk' => 'integer',
        'updated_by_pk' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', true);
    }

    public static function tableExists(): bool
    {
        if (static::$tableExistsCache !== null) {
            return static::$tableExistsCache;
        }

        return static::$tableExistsCache = Schema::hasTable((new static)->getTable());
    }

    public static function thumbPathFor(string $path): string
    {
        $dir = dirname($path);

        return ($dir !== '.' ? $dir.'/' : '').'thumbs/'.basename($path);
    }

    public function previewUrl(): string
    {
        $thumb = static::thumbPathFor($this->image_path);

        if (Storage::disk('public')->exists($thumb)) {
            return Storage::disk('public')->url($thumb);
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function ensureThumbnail(): void
    {
        if (! $this->image_path || ! Storage::disk('public')->exists($this->image_path)) {
            return;
        }

        $thumb = static::thumbPathFor($this->image_path);
        if (Storage::disk('public')->exists($thumb)) {
            return;
        }

        static::generateThumbnail($this->image_path);
    }

    public static function generateThumbnail(string $storedPath): void
    {
        if ($storedPath === '') {
            return;
        }

        $full = storage_path('app/public/'.$storedPath);
        if (! is_file($full)) {
            return;
        }

        $thumbRelative = static::thumbPathFor($storedPath);
        $thumbFull = storage_path('app/public/'.$thumbRelative);

        if (is_file($thumbFull)) {
            return;
        }

        @mkdir(dirname($thumbFull), 0755, true);

        $info = @getimagesize($full);
        if ($info === false) {
            return;
        }

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($full),
            IMAGETYPE_PNG => @imagecreatefrompng($full),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : null,
            default => null,
        };

        if (! $src) {
            return;
        }

        $maxW = 320;
        $maxH = 180;
        $w = imagesx($src);
        $h = imagesy($src);
        $ratio = min($maxW / $w, $maxH / $h, 1);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagejpeg($dst, $thumbFull, 82);
        imagedestroy($src);
        imagedestroy($dst);
    }

    public function deleteStoredFiles(): void
    {
        if (! $this->image_path) {
            return;
        }

        $paths = array_filter([
            $this->image_path,
            static::thumbPathFor($this->image_path),
        ]);

        Storage::disk('public')->delete($paths);
    }

    public static function activeForLogin()
    {
        if (! static::tableExists()) {
            return collect();
        }

        return static::query()
            ->active()
            ->select(['id', 'image_path', 'sort_order', 'active_inactive'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
