<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'difficulty_level_id' => ['required', 'integer', 'exists:difficulty_levels,id'],
            'title' => ['required', 'string', 'max:255'],
            'question_text' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string'],
            'correct_answer' => ['required', 'array', 'min:1'],
            'correct_answer.*' => ['required', 'integer', 'min:0'],
            'hints' => ['nullable', 'array'],
            'hints.*' => ['string'],
            'points' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'time_limit' => ['nullable', 'integer', 'min:1', 'max:3600'],
            'is_published' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:topics,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'difficulty_level_id.required' => 'The difficulty level is required.',
            'difficulty_level_id.exists' => 'The selected difficulty level is invalid.',
            'title.required' => 'The question title is required.',
            'question_text.required' => 'The question text is required.',
            'options.required' => 'At least 2 options are required.',
            'options.min' => 'At least 2 options are required.',
            'correct_answer.required' => 'At least one correct answer must be specified.',
            'correct_answer.min' => 'At least one correct answer must be specified.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('created_by')) {
            $this->merge([
                'created_by' => auth()->id(),
            ]);
        }

        if (! $this->has('is_published')) {
            $this->merge([
                'is_published' => false,
            ]);
        }

        if (! $this->has('is_verified')) {
            $this->merge([
                'is_verified' => false,
            ]);
        }
    }
}
