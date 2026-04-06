<?php

namespace App\Http\Requests\CodeSnippet;

use Illuminate\Foundation\Http\FormRequest;

class DetectLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The code is required for language detection.',
        ];
    }
}
