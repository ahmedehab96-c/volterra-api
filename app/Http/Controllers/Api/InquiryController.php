<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInquiryRequest;
use App\Http\Resources\InquiryResource;
use App\Models\Inquiry;
use App\Support\InquiryNotifier;
use App\Support\Pricing;
use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request): JsonResponse
    {
        $data = $request->validated();
        [$model, $quote] = Pricing::fromCodes($data);

        // Model link and price come from the database, not from the client
        $inquiry = Inquiry::create([
            ...$data,
            'model_id' => $model->id,
            'model' => $model->slug,
            'estimated_price' => $quote['estimated_price'],
        ]);

        InquiryNotifier::send($inquiry);

        return (new InquiryResource($inquiry))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }
}
