<?php

namespace App\Http\Requests\CodeSnippet;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCodeSnippetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['sometimes', 'integer', 'exists:questions,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'nullable'],
            'code' => ['sometimes', 'string'],
            'language' => ['sometimes', 'string', 'max:50'],
            'type' => ['sometimes', 'string', 'in:example,solution,test,template,starter'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_executable' => ['sometimes', 'boolean'],
            'expected_output' => ['sometimes', 'string', 'nullable'],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.exists' => 'The selected question does not exist.',
            'type.in' => 'The type must be one of: example, solution, test, template, starter.',
        ];
    }
}
