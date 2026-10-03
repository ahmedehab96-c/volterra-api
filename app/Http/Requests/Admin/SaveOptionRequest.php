<?php

namespace App\Http\Requests\Admin;

use App\Models\ConfigurationOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';
        $option = $this->route('option');
        $type = $this->input('type', $option?->type);

        return [
            'type' => [$required, Rule::in(ConfigurationOption::TYPES)],
            'code' => [$required, 'string', 'max:32', 'alpha_dash:ascii', Rule::unique('configuration_options', 'code')->where('type', $type)->ignore($option?->id)],
            'name' => [$required, 'string', 'max:100'],
            'price' => [$required, 'integer', 'between:0,10000000'],
            'status' => [$required, Rule::in(ConfigurationOption::STATUSES)],
        ];
    }
}
