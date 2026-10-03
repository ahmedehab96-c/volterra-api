<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Support\PublicCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A VOLTERRA vehicle model (table "models"; the class avoids clashing with Eloquent's Model). */
class CarModel extends Model
{
    use SoftDeletes;

    protected $table = 'models';

    protected $fillable = [
        'name', 'slug', 'tagline', 'horsepower', 'torque', 'acceleration', 'top_speed', 'base_price', 'description', 'hero_image', 'model_3d', 'status',
    ];

    protected function casts(): array
    {
        return [
            'horsepower' => 'integer',
            'torque' => 'integer',
            'acceleration' => 'float',
            'top_speed' => 'integer',
            'base_price' => 'integer',
        ];
    }

    public const STATUSES = ['active', 'inactive'];

    /** Only active models are public */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    protected static function booted(): void
    {
        // Any change to the range refreshes the cached public catalog
        static::saved(fn () => PublicCatalog::flush());
        static::deleted(fn () => PublicCatalog::flush());
        static::restored(fn () => PublicCatalog::flush());
    }

    public function images(): HasMany
    {
        return $this->hasMany(ModelImage::class, 'model_id')->orderBy('sort_order')->orderBy('id');
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(Configuration::class, 'model_id');
    }
}
