<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Confirmation only: personal details are stored, not echoed back. */
class InquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => 'VT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            'model' => $this->model,
            'estimated_price' => $this->estimated_price,
            'created_at' => $this->created_at,
        ];
    }
}
