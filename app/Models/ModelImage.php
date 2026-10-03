<?php

namespace App\Models;

use App\Support\PublicCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** A gallery image of a vehicle model, stored on the public disk under vehicles/{model_id}/. */
class ModelImage extends Model
{
    public const TYPES = ['hero', 'exterior', 'interior', 'detail'];

    protected $fillable = ['image_path', 'type', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        // The file goes with its record
        static::deleted(fn (self $image) => Storage::disk('public')->delete($image->image_path));
        static::saved(fn () => PublicCatalog::flush());
        static::deleted(fn () => PublicCatalog::flush());
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'model_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }
}
