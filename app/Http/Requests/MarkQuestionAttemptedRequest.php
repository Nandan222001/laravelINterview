<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkQuestionAttemptedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => 'required|integer|exists:questions,id',
            'user_answer' => 'nullable|array',
            'time_spent' => 'nullable|integer|min:0',
        ];
    }
}
