<?php

namespace App\Models;

use App\Support\PublicCatalog;
use Illuminate\Database\Eloquent\Model;

/** A configurator choice (paint, wheels or interior) with its price. */
class ConfigurationOption extends Model
{
    public const TYPES = ['exterior_color', 'wheels', 'interior'];

    public const STATUSES = ['active', 'inactive'];

    protected $fillable = ['type', 'code', 'name', 'price', 'status'];

    protected function casts(): array
    {
        return ['price' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => PublicCatalog::flush());
        static::deleted(fn () => PublicCatalog::flush());
    }
}
