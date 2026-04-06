<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionCollection;
use App\Http\Resources\TopicCollection;
use App\Http\Resources\TopicResource;
use App\Services\TopicService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TopicService $topicService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $filters = $request->only([
            'search',
            'category_id',
            'is_active',
            'parent_id',
            'depth',
            'has_children',
            'has_questions',
            'tag_slugs',
        ]);
        $filters['per_page'] = $perPage;

        $topics = $this->topicService->filterTopics($filters, $perPage);

        return $this->successResponse(
            new TopicCollection($topics),
            'Topics retrieved successfully'
        );
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $includeChildren = $request->boolean('include_children', false);
        $includeQuestions = $request->boolean('include_questions', false);
        $includeBreadcrumb = $request->boolean('include_breadcrumb', false);

        $relations = ['category', 'parent', 'tags'];
        if ($includeChildren) {
            $relations[] = 'children';
        }
        if ($includeQuestions) {
            $relations[] = 'questions';
            $relations[] = 'questions.difficultyLevel';
            $relations[] = 'questions.tags';
        }

        $topic = $this->topicService->findWithRelations($id, $relations);

        if (! $topic) {
            return $this->notFoundResponse('Topic not found');
        }

        $data = new TopicResource($topic);

        if ($includeBreadcrumb) {
            return $this->successResponse(
                [
                    'topic' => $data,
                    'breadcrumb' => $this->topicService->getBreadcrumb($id),
                ],
                'Topic retrieved successfully'
            );
        }

        return $this->successResponse($data, 'Topic retrieved successfully');
    }

    public function questions(int $id, Request $request): JsonResponse
    {
        $topic = $this->topicService->find($id);

        if (! $topic) {
            return $this->notFoundResponse('Topic not found');
        }

        $perPage = $request->input('per_page', 15);
        $filters = $request->only([
            'difficulty',
            'published',
            'verified',
            'tags',
            'min_difficulty_level',
            'max_difficulty_level',
        ]);

        $filters['topic'] = $id;

        $includeDescendants = $request->boolean('include_descendants', false);
        if ($includeDescendants) {
            $descendantIds = $this->topicService->getDescendantIds($id);
            $topicIds = array_merge([$id], $descendantIds);
            unset($filters['topic']);
            $filters['topic_ids'] = $topicIds;
        }

        $questions = $this->topicService->getQuestionsByTopicWithFilters($id, $filters, $perPage);

        return $this->successResponse(
            new QuestionCollection($questions),
            'Questions retrieved successfully'
        );
    }

    public function tree(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');
        $maxDepth = $request->input('max_depth');
        $activeOnly = $request->boolean('active_only', true);
        $parentId = $request->input('parent_id');

        if ($parentId !== null) {
            $topics = $this->topicService->getTopicsByParent($parentId, $categoryId, $activeOnly);
        } else {
            $topics = $this->topicService->getTopicTreeWithDepth($categoryId, $maxDepth, $activeOnly);
        }

        return $this->successResponse(
            TopicResource::collection($topics),
            'Topic tree retrieved successfully'
        );
    }

    public function children(int $id, Request $request): JsonResponse
    {
        $activeOnly = $request->boolean('active_only', true);

        $children = $this->topicService->getChildren($id, $activeOnly);

        return $this->successResponse(
            TopicResource::collection($children),
            'Child topics retrieved successfully'
        );
    }

    public function breadcrumb(int $id): JsonResponse
    {
        $breadcrumb = $this->topicService->getBreadcrumb($id);

        if (empty($breadcrumb)) {
            return $this->notFoundResponse('Topic not found');
        }

        return $this->successResponse(
            $breadcrumb,
            'Breadcrumb retrieved successfully'
        );
    }

    public function descendants(int $id, Request $request): JsonResponse
    {
        $maxDepth = $request->input('max_depth');

        $descendants = $this->topicService->getDescendants($id, $maxDepth);

        if ($descendants === null) {
            return $this->notFoundResponse('Topic not found');
        }

        return $this->successResponse(
            TopicResource::collection($descendants),
            'Descendant topics retrieved successfully'
        );
    }

    public function ancestors(int $id): JsonResponse
    {
        $ancestors = $this->topicService->getAncestors($id);

        if ($ancestors === null) {
            return $this->notFoundResponse('Topic not found');
        }

        return $this->successResponse(
            TopicResource::collection($ancestors),
            'Ancestor topics retrieved successfully'
        );
    }
}
