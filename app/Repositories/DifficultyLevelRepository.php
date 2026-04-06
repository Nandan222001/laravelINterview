<?php

namespace App\Repositories;

use App\Models\DifficultyLevel;
use Illuminate\Database\Eloquent\Collection;

class DifficultyLevelRepository extends BaseRepository
{
    public function __construct(DifficultyLevel $model)
    {
        parent::__construct($model);
    }

    public function getAllOrdered(): Collection
    {
        return $this->model->ordered()->get();
    }

    public function getDifficultyBySlug(string $slug): ?DifficultyLevel
    {
        return $this->model->bySlug($slug)->first();
    }

    public function getDifficultyByLevel(int $level): ?DifficultyLevel
    {
        return $this->model->byLevel($level)->first();
    }

    public function getDifficultiesWithQuestionCount(): Collection
    {
        return $this->model->withQuestionsCount()->ordered()->get();
    }

    public function getDifficultiesBetween(int $minLevel, int $maxLevel): Collection
    {
        return $this->model->betweenLevels($minLevel, $maxLevel)->ordered()->get();
    }

    public function getMinDifficulty(int $level): Collection
    {
        return $this->model->minLevel($level)->ordered()->get();
    }

    public function getMaxDifficulty(int $level): Collection
    {
        return $this->model->maxLevel($level)->ordered()->get();
    }
}
