<?php

namespace App\Http\Requests\CodeSnippet;

use Illuminate\Foundation\Http\FormRequest;

class StoreCodeSnippetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string'],
            'language' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'in:example,solution,test,template,starter'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_executable' => ['nullable', 'boolean'],
            'expected_output' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'The question ID is required.',
            'question_id.exists' => 'The selected question does not exist.',
            'code.required' => 'The code is required.',
            'type.in' => 'The type must be one of: example, solution, test, template, starter.',
        ];
    }
}
