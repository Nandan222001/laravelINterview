<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'difficulty_level_id' => ['sometimes', 'integer', 'exists:difficulty_levels,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'question_text' => ['sometimes', 'string'],
            'explanation' => ['nullable', 'string'],
            'options' => ['sometimes', 'array', 'min:2'],
            'options.*' => ['required', 'string'],
            'correct_answer' => ['sometimes', 'array', 'min:1'],
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
            'difficulty_level_id.exists' => 'The selected difficulty level is invalid.',
            'options.min' => 'At least 2 options are required.',
            'correct_answer.min' => 'At least one correct answer must be specified.',
        ];
    }
}
