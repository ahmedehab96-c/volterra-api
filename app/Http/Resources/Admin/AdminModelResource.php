<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'horsepower' => $this->horsepower,
            'torque' => $this->torque,
            'acceleration' => $this->acceleration,
            'top_speed' => $this->top_speed,
            'base_price' => $this->base_price,
            'hero_image' => $this->hero_image,
            'model_3d' => $this->model_3d,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
