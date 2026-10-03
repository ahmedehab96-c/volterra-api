<?php

namespace App\Http\Requests;

use App\Support\Pricing;
use Illuminate\Foundation\Http\FormRequest;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:2000'],
            ...Pricing::codeRules(),
        ];
    }
}
