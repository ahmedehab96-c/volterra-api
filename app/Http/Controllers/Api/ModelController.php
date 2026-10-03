<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PublicCatalog;
use Illuminate\Http\JsonResponse;

/** Public range, served from the cached catalog. */
class ModelController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => array_values(PublicCatalog::models())]);
    }

    public function show(string $slug): JsonResponse
    {
        $model = PublicCatalog::models()[$slug] ?? abort(404);

        return response()->json(['success' => true, 'data' => $model]);
    }

    public function options(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => PublicCatalog::options()]);
    }
}
