<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Question $question): bool
    {
        if ($question->is_published && $question->is_verified) {
            return true;
        }

        if ($user && $user->id === $question->created_by) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Question $question): bool
    {
        return $user->id === $question->created_by;
    }

    public function delete(User $user, Question $question): bool
    {
        return $user->id === $question->created_by;
    }

    public function restore(User $user, Question $question): bool
    {
        return $user->id === $question->created_by;
    }

    public function forceDelete(User $user, Question $question): bool
    {
        return $user->id === $question->created_by;
    }

    public function publish(User $user, Question $question): bool
    {
        return $user->id === $question->created_by;
    }

    public function verify(User $user, Question $question): bool
    {
        return true;
    }
}
