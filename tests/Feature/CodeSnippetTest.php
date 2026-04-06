<?php

namespace Tests\Feature;

use App\Services\CodeSnippetService;
use App\Services\SyntaxHighlightingService;
use Highlight\Highlighter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CodeSnippetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    public function test_code_snippet_service_exists(): void
    {
        $this->assertTrue(class_exists(CodeSnippetService::class));
    }

    public function test_syntax_highlighting_service_exists(): void
    {
        $this->assertTrue(class_exists(SyntaxHighlightingService::class));
    }

    public function test_highlighter_library_exists(): void
    {
        $this->assertTrue(class_exists(Highlighter::class));
    }

    public function test_code_snippet_routes_are_registered(): void
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return $route->uri();
        });

        $this->assertTrue($routes->contains('api/code-snippets'));
        $this->assertTrue($routes->contains('api/code-snippets/detect-language'));
        $this->assertTrue($routes->contains('api/code-snippets/highlight'));
        $this->assertTrue($routes->contains('api/code-snippets/supported-languages'));
    }

    public function test_syntax_highlighting_routes_are_registered(): void
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return $route->uri();
        });

        $this->assertTrue($routes->contains('api/syntax-highlighting/themes'));
        $this->assertTrue($routes->contains('api/syntax-highlighting/client-instructions'));
    }
}
