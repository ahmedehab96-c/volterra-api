<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveModelRequest;
use App\Http\Resources\Admin\AdminModelResource;
use App\Models\CarModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModelController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AdminModelResource::collection(CarModel::query()->orderBy('id')->get())->additional(['success' => true]);
    }

    public function store(SaveModelRequest $request): JsonResponse
    {
        $model = CarModel::create($request->validated());

        return (new AdminModelResource($model))->additional(['success' => true])->response()->setStatusCode(201);
    }

    public function show(CarModel $carModel): AdminModelResource
    {
        return (new AdminModelResource($carModel))->additional(['success' => true]);
    }

    public function update(SaveModelRequest $request, CarModel $carModel): AdminModelResource
    {
        $carModel->update($request->validated());

        return (new AdminModelResource($carModel))->additional(['success' => true]);
    }

    public function destroy(CarModel $carModel): JsonResponse
    {
        $carModel->delete();

        return response()->json(['success' => true]);
    }
}
