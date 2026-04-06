<?php

namespace App\Providers;

use App\Models\Question;
use App\Models\Topic;
use App\Observers\QuestionObserver;
use App\Observers\TopicObserver;
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
    }
}
