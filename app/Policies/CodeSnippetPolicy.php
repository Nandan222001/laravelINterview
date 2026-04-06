<?php

namespace App\Policies;

use App\Models\CodeSnippet;
use App\Models\User;

class CodeSnippetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CodeSnippet $codeSnippet): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CodeSnippet $codeSnippet): bool
    {
        return true;
    }

    public function delete(User $user, CodeSnippet $codeSnippet): bool
    {
        return true;
    }

    public function restore(User $user, CodeSnippet $codeSnippet): bool
    {
        return true;
    }

    public function forceDelete(User $user, CodeSnippet $codeSnippet): bool
    {
        return true;
    }
}
