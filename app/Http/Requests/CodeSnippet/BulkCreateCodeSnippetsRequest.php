<?php

namespace App\Http\Requests\CodeSnippet;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreateCodeSnippetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'snippets' => ['required', 'array', 'min:1'],
            'snippets.*.title' => ['nullable', 'string', 'max:255'],
            'snippets.*.description' => ['nullable', 'string'],
            'snippets.*.code' => ['required', 'string'],
            'snippets.*.language' => ['nullable', 'string', 'max:50'],
            'snippets.*.type' => ['nullable', 'string', 'in:example,solution,test,template,starter'],
            'snippets.*.order' => ['nullable', 'integer', 'min:0'],
            'snippets.*.is_executable' => ['nullable', 'boolean'],
            'snippets.*.expected_output' => ['nullable', 'string'],
            'snippets.*.metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'The question ID is required.',
            'question_id.exists' => 'The selected question does not exist.',
            'snippets.required' => 'At least one snippet is required.',
            'snippets.*.code.required' => 'The code is required for each snippet.',
        ];
    }
}
