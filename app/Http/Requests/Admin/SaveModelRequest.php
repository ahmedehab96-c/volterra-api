<?php

namespace App\Http\Requests\Admin;

use App\Models\CarModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create (all fields required) and update (partial: only the fields sent are validated). */
class SaveModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';
        $id = $this->route('carModel')?->id;

        return [
            'name' => [$required, 'string', 'max:100'],
            'slug' => [$required, 'string', 'max:64', 'alpha_dash:ascii', Rule::unique('models', 'slug')->ignore($id)],
            'tagline' => ['nullable', 'string', 'max:120'],
            'description' => [$required, 'string', 'max:2000'],
            'horsepower' => [$required, 'integer', 'between:1,3000'],
            'torque' => [$required, 'integer', 'between:1,5000'],
            'acceleration' => [$required, 'numeric', 'between:1,20'],
            'top_speed' => [$required, 'integer', 'between:50,600'],
            'base_price' => [$required, 'integer', 'between:0,100000000'],
            'hero_image' => [$required, 'string', 'max:255'],
            'model_3d' => ['nullable', 'string', 'max:255', 'regex:/\.(glb|gltf)(\?.*)?$/i'],
            'status' => [$required, Rule::in(CarModel::STATUSES)],
        ];
    }
}
