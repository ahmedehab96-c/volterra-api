<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminInquiryResource;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    /** Newest first, with search (name, email, phone, country) and status/model filters */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Inquiry::STATUSES)],
            'model' => ['nullable', 'string', 'max:64'],
        ]);

        $inquiries = Inquiry::query()
            ->with('carModel')
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('country', 'like', $like));
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['model'] ?? null, fn ($q, $model) => $q->where('model', $model))
            ->latest()->latest('id')
            ->paginate(15)
            ->withQueryString();

        return AdminInquiryResource::collection($inquiries)->additional(['success' => true]);
    }

    public function show(Inquiry $inquiry): AdminInquiryResource
    {
        return (new AdminInquiryResource($inquiry->load('carModel')))->additional(['success' => true]);
    }

    /** Status and/or internal notes */
    public function update(Request $request, Inquiry $inquiry): AdminInquiryResource
    {
        $inquiry->update($request->validate([
            'status' => ['required_without:internal_notes', Rule::in(Inquiry::STATUSES)],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ]));

        return (new AdminInquiryResource($inquiry->load('carModel')))->additional(['success' => true]);
    }
}
