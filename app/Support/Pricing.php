<?php

namespace App\Support;

use App\Models\CarModel;
use App\Models\ConfigurationOption;
use Illuminate\Validation\Rule;

/** The single source of truth for configurator validation and prices. Client-sent prices are never used. */
class Pricing
{
    /** Rules for payloads that name the model by slug and options by code (save configuration, inquiry) */
    public static function codeRules(): array
    {
        $option = fn (string $type) => Rule::exists('configuration_options', 'code')->where('type', $type)->where('status', 'active');

        return [
            'model' => ['required', 'string', Rule::exists('models', 'slug')->where('status', 'active')->whereNull('deleted_at')],
            'exterior_color' => ['required', 'string', $option('exterior_color')],
            'wheels' => ['required', 'string', $option('wheels')],
            'interior' => ['required', 'string', $option('interior')],
        ];
    }

    /** Rules for payloads that use database ids (price calculation) */
    public static function idRules(): array
    {
        $option = fn (string $type) => Rule::exists('configuration_options', 'id')->where('type', $type)->where('status', 'active');

        return [
            'model_id' => ['required', 'integer', Rule::exists('models', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'exterior_color_id' => ['required', 'integer', $option('exterior_color')],
            'wheel_id' => ['required', 'integer', $option('wheels')],
            'interior_id' => ['required', 'integer', $option('interior')],
        ];
    }

    /** Active option by type and code (validated beforehand) */
    public static function option(string $type, string $code): ConfigurationOption
    {
        return ConfigurationOption::where(['type' => $type, 'code' => $code, 'status' => 'active'])->firstOrFail();
    }

    /** @return array{base_price: int, options_total: int, estimated_price: int} */
    public static function quote(CarModel $model, ConfigurationOption ...$options): array
    {
        $optionsTotal = array_sum(array_map(fn (ConfigurationOption $o) => $o->price, $options));

        return [
            'base_price' => $model->base_price,
            'options_total' => $optionsTotal,
            'estimated_price' => $model->base_price + $optionsTotal,
        ];
    }

    /** Resolve and price a slug/code payload */
    public static function fromCodes(array $data): array
    {
        $model = CarModel::active()->where('slug', $data['model'])->firstOrFail();
        $options = [
            self::option('exterior_color', $data['exterior_color']),
            self::option('wheels', $data['wheels']),
            self::option('interior', $data['interior']),
        ];

        return [$model, self::quote($model, ...$options)];
    }
}
