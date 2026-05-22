<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListImageGenerationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'sort' => [
                'sometimes',
                'string',
                Rule::in([
                    'created_at',
                    '-created_at',
                    'generated_prompt',
                    '-generated_prompt',
                    'original_filename',
                    '-original_filename',
                    'file_size',
                    '-file_size',
                ]),
            ],
        ];
    }
}
