<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarModel;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_models' => CarModel::count(),
                'active_models' => CarModel::active()->count(),
                'total_inquiries' => Inquiry::count(),
                'new_inquiries' => Inquiry::where('status', 'new')->count(),
            ],
        ]);
    }
}
