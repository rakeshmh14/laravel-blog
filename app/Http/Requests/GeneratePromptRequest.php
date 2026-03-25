<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePromptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required', 
                'file',
                'image',
                'mimes:jpeg,png,git,svg', 
                'max:10240',
                'min:1', 
                'dimensions:min_width=100,min_height=100, max_width:1000,max_height:1000'
            ],
        ];
    }
}
