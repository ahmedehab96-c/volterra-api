<?php

namespace App\Http\Requests;

use App\Support\Pricing;
use Illuminate\Foundation\Http\FormRequest;

class CalculateConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Pricing::idRules();
    }
}
