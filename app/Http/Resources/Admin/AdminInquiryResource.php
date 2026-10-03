<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Full inquiry for staff, including contact details. */
class AdminInquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => 'VT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'model' => $this->model,
            'model_id' => $this->model_id,
            'model_name' => $this->whenLoaded('carModel', fn () => $this->carModel?->name),
            'exterior_color' => $this->exterior_color,
            'wheels' => $this->wheels,
            'interior' => $this->interior,
            'estimated_price' => $this->estimated_price,
            'message' => $this->message,
            'status' => $this->status,
            'internal_notes' => $this->internal_notes,
            // Option display names on the single-inquiry endpoints only
            'configuration' => $this->when((bool) $request->route('inquiry'), fn () => $this->resource->optionNames()),
            'created_at' => $this->created_at,
        ];
    }
}
