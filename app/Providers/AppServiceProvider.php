<?php

namespace App\Providers;

use App\Models\CodeSnippet;
use App\Models\Question;
use App\Models\Topic;
use App\Observers\QuestionObserver;
use App\Observers\TopicObserver;
use App\Policies\CodeSnippetPolicy;
use App\Policies\QuestionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Topic::observe(TopicObserver::class);
        Question::observe(QuestionObserver::class);

        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(CodeSnippet::class, CodeSnippetPolicy::class);
    }
}
