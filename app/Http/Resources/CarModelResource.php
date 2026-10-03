<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Public model: specs, price, imagery and optional 3D asset. No internal fields. */
class CarModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = $this->images->groupBy('type');
        $urls = fn (string $type) => $images->get($type, collect())->map->url()->values()->all();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'horsepower' => $this->horsepower,
            'torque' => $this->torque,
            'acceleration' => $this->acceleration,
            'top_speed' => $this->top_speed,
            'price' => $this->base_price,
            'description' => $this->description,
            // An uploaded hero image wins over the static path
            'hero_image' => $images->get('hero')?->first()?->url() ?? $this->hero_image,
            'model_3d' => $this->model_3d,
            'gallery' => ['exterior' => $urls('exterior'), 'interior' => $urls('interior'), 'detail' => $urls('detail')],
        ];
    }
}
