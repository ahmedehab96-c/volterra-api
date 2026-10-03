<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateConfigurationRequest;
use App\Http\Requests\StoreConfigurationRequest;
use App\Http\Resources\ConfigurationResource;
use App\Models\CarModel;
use App\Models\ConfigurationOption;
use App\Support\Pricing;
use Illuminate\Http\JsonResponse;

class ConfigurationController extends Controller
{
    /** Price a configuration without saving it */
    public function calculate(CalculateConfigurationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $options = ConfigurationOption::findMany([$data['exterior_color_id'], $data['wheel_id'], $data['interior_id']])->all();

        return response()->json([
            'success' => true,
            'data' => Pricing::quote(CarModel::findOrFail($data['model_id']), ...$options),
        ]);
    }

    /** Validate, price and save; the response carries the final server-side price */
    public function store(StoreConfigurationRequest $request): JsonResponse
    {
        $data = $request->validated();
        [$model, $quote] = Pricing::fromCodes($data);

        $configuration = $model->configurations()->create([
            'exterior_color' => $data['exterior_color'],
            'wheels' => $data['wheels'],
            'interior' => $data['interior'],
            ...$quote,
        ]);

        return (new ConfigurationResource($configuration->load('carModel')))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }
}
