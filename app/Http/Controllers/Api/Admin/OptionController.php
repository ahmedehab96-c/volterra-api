<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveOptionRequest;
use App\Http\Resources\Admin\OptionResource;
use App\Models\ConfigurationOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['type' => ['nullable', Rule::in(ConfigurationOption::TYPES)]]);

        $options = ConfigurationOption::query()
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('type')->orderBy('price')->orderBy('id')
            ->get();

        return OptionResource::collection($options)->additional(['success' => true]);
    }

    public function store(SaveOptionRequest $request): JsonResponse
    {
        $option = ConfigurationOption::create($request->validated());

        return (new OptionResource($option))->additional(['success' => true])->response()->setStatusCode(201);
    }

    public function update(SaveOptionRequest $request, ConfigurationOption $option): OptionResource
    {
        $option->update($request->validated());

        return (new OptionResource($option))->additional(['success' => true]);
    }

    public function destroy(ConfigurationOption $option): JsonResponse
    {
        $option->delete();

        return response()->json(['success' => true]);
    }
}
