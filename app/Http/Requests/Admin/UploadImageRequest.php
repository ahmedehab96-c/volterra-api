<?php

namespace App\Http\Requests\Admin;

use App\Models\ModelImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Raster images only (the image rule excludes SVG); 8 MB and 8000 px are generous for photography
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192', 'dimensions:max_width=8000,max_height=8000'],
            'type' => ['required', Rule::in(ModelImage::TYPES)],
        ];
    }
}
