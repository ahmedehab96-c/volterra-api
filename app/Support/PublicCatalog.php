<?php

namespace App\Support;

use App\Http\Resources\CarModelResource;
use App\Http\Resources\PublicOptionResource;
use App\Models\CarModel;
use App\Models\ConfigurationOption;
use Illuminate\Support\Facades\Cache;

/** Cached public read models (active range and options). Cleared by model/image/option events. */
class PublicCatalog
{
    private const MODELS = 'catalog.models';

    private const OPTIONS = 'catalog.options';

    /** @return array<string, array> active models keyed by slug */
    public static function models(): array
    {
        return Cache::remember(self::MODELS, config('volterra.catalog_ttl'), fn () => CarModel::active()
            ->with('images')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (CarModel $m) => [$m->slug => (new CarModelResource($m))->resolve()])
            ->all());
    }

    public static function options(): array
    {
        return Cache::remember(self::OPTIONS, config('volterra.catalog_ttl'), fn () => PublicOptionResource::collection(
            ConfigurationOption::where('status', 'active')->orderBy('type')->orderBy('price')->orderBy('id')->get()
        )->resolve());
    }

    public static function flush(): void
    {
        Cache::forget(self::MODELS);
        Cache::forget(self::OPTIONS);
    }
}
