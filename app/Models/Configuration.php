<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Configuration extends Model
{
    protected $fillable = ['model_id', 'exterior_color', 'wheels', 'interior', 'base_price', 'options_total', 'estimated_price'];

    protected function casts(): array
    {
        return ['base_price' => 'integer', 'options_total' => 'integer', 'estimated_price' => 'integer'];
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'model_id');
    }
}
