<?php

namespace App\Http\Requests\CodeSnippet;

use Illuminate\Foundation\Http\FormRequest;

class ReorderCodeSnippetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'snippet_orders' => ['required', 'array'],
            'snippet_orders.*' => ['required', 'integer', 'exists:code_snippets,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'The question ID is required.',
            'question_id.exists' => 'The selected question does not exist.',
            'snippet_orders.required' => 'The snippet orders are required.',
            'snippet_orders.*.exists' => 'One or more snippets do not exist.',
        ];
    }
}
