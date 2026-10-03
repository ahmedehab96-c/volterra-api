<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => 'VC-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            'model' => $this->carModel->slug,
            'exterior_color' => $this->exterior_color,
            'wheels' => $this->wheels,
            'interior' => $this->interior,
            'base_price' => $this->base_price,
            'options_total' => $this->options_total,
            'estimated_price' => $this->estimated_price,
            'created_at' => $this->created_at,
        ];
    }
}
