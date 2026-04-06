<?php

namespace App\Http\Requests\CodeSnippet;

use Illuminate\Foundation\Http\FormRequest;

class HighlightCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'language' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The code is required for highlighting.',
        ];
    }
}
