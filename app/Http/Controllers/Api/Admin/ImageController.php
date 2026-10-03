<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadImageRequest;
use App\Http\Resources\Admin\ModelImageResource;
use App\Models\CarModel;
use App\Models\ModelImage;
use App\Support\PublicCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ImageController extends Controller
{
    public function index(CarModel $carModel): AnonymousResourceCollection
    {
        return ModelImageResource::collection($carModel->images)->additional(['success' => true]);
    }

    /** Stored with a random name under storage/app/public/vehicles/{model_id}/ */
    public function store(UploadImageRequest $request, CarModel $carModel): JsonResponse
    {
        $type = $request->validated('type');
        $image = $carModel->images()->create([
            'image_path' => $request->file('image')->store("vehicles/{$carModel->id}", 'public'),
            'type' => $type,
            'sort_order' => (int) $carModel->images()->where('type', $type)->max('sort_order') + 1,
        ]);

        return (new ModelImageResource($image))->additional(['success' => true])->response()->setStatusCode(201);
    }

    /** Body: { ids: [...] } in the new order (ids must belong to this model) */
    public function reorder(Request $request, CarModel $carModel): AnonymousResourceCollection
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', Rule::exists('model_images', 'id')->where('model_id', $carModel->id)],
        ])['ids'];

        DB::transaction(function () use ($ids) {
            foreach ($ids as $position => $id) {
                ModelImage::whereKey($id)->update(['sort_order' => $position]);
            }
        });
        PublicCatalog::flush(); // bulk updates skip model events

        return ModelImageResource::collection($carModel->images()->get())->additional(['success' => true]);
    }

    public function destroy(ModelImage $image): JsonResponse
    {
        $image->delete();

        return response()->json(['success' => true]);
    }
}
