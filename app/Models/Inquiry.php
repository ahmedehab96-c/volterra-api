<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'country', 'model_id', 'model', 'exterior_color', 'wheels', 'interior', 'estimated_price', 'message', 'status', 'internal_notes',
    ];

    public const STATUSES = ['new', 'contacted', 'closed'];

    /** The inquired model, even if it was later removed from the range */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'model_id')->withTrashed();
    }

    /** Display names of the chosen options, falling back to their codes */
    public function optionNames(): array
    {
        $names = ConfigurationOption::query()
            ->where(fn ($q) => $q
                ->where(['type' => 'exterior_color', 'code' => $this->exterior_color])
                ->orWhere(fn ($q) => $q->where(['type' => 'wheels', 'code' => $this->wheels]))
                ->orWhere(fn ($q) => $q->where(['type' => 'interior', 'code' => $this->interior])))
            ->pluck('name', 'type');

        return [
            'exterior_color' => $names['exterior_color'] ?? $this->exterior_color,
            'wheels' => $names['wheels'] ?? $this->wheels,
            'interior' => $names['interior'] ?? $this->interior,
        ];
    }

    protected function casts(): array
    {
        return ['estimated_price' => 'integer'];
    }
}
