<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $filters = $request->only([
            'search',
            'is_active',
            'parent_id',
            'has_children',
            'tag_slugs',
        ]);

        $categories = $this->categoryService->filterCategories($filters, $perPage);

        return $this->successResponse(
            new CategoryCollection($categories),
            'Categories retrieved successfully'
        );
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $includeChildren = $request->boolean('include_children', false);
        $includeTopics = $request->boolean('include_topics', false);
        $includeBreadcrumb = $request->boolean('include_breadcrumb', false);

        $relations = [];
        if ($includeChildren) {
            $relations[] = 'children';
        }
        if ($includeTopics) {
            $relations[] = 'topics';
        }
        if ($includeBreadcrumb) {
            $relations[] = 'parent';
        }

        $category = $this->categoryService->findWithRelations($id, $relations);

        if (! $category) {
            return $this->notFoundResponse('Category not found');
        }

        $data = new CategoryResource($category);

        if ($includeBreadcrumb) {
            return $this->successResponse(
                [
                    'category' => $data,
                    'breadcrumb' => $this->categoryService->getBreadcrumb($id),
                ],
                'Category retrieved successfully'
            );
        }

        return $this->successResponse($data, 'Category retrieved successfully');
    }

    public function tree(Request $request): JsonResponse
    {
        $maxDepth = $request->input('max_depth');
        $activeOnly = $request->boolean('active_only', true);
        $parentId = $request->input('parent_id');

        if ($parentId !== null) {
            $categories = $this->categoryService->getCategoriesByParent($parentId, $activeOnly);
        } else {
            $categories = $this->categoryService->getCategoryTreeWithDepth($maxDepth, $activeOnly);
        }

        return $this->successResponse(
            CategoryResource::collection($categories),
            'Category tree retrieved successfully'
        );
    }

    public function children(int $id, Request $request): JsonResponse
    {
        $activeOnly = $request->boolean('active_only', true);

        $children = $this->categoryService->getCategoriesByParent($id, $activeOnly);

        return $this->successResponse(
            CategoryResource::collection($children),
            'Child categories retrieved successfully'
        );
    }

    public function breadcrumb(int $id): JsonResponse
    {
        $breadcrumb = $this->categoryService->getBreadcrumb($id);

        if (empty($breadcrumb)) {
            return $this->notFoundResponse('Category not found');
        }

        return $this->successResponse(
            $breadcrumb,
            'Breadcrumb retrieved successfully'
        );
    }
}
