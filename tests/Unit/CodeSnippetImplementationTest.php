<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\CodeSnippetController;
use App\Http\Controllers\Api\SyntaxHighlightingController;
use App\Http\Requests\CodeSnippet\DetectLanguageRequest;
use App\Http\Requests\CodeSnippet\HighlightCodeRequest;
use App\Http\Requests\CodeSnippet\StoreCodeSnippetRequest;
use App\Http\Requests\CodeSnippet\UpdateCodeSnippetRequest;
use App\Http\Resources\CodeSnippetCollection;
use App\Http\Resources\CodeSnippetResource;
use App\Policies\CodeSnippetPolicy;
use App\Services\CodeSnippetService;
use App\Services\SyntaxHighlightingService;
use Database\Factories\CodeSnippetFactory;
use Database\Seeders\CodeSnippetSeeder;
use Highlight\Highlighter;
use PHPUnit\Framework\TestCase;

class CodeSnippetImplementationTest extends TestCase
{
    public function test_code_snippet_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CodeSnippetService::class));
    }

    public function test_syntax_highlighting_service_class_exists(): void
    {
        $this->assertTrue(class_exists(SyntaxHighlightingService::class));
    }

    public function test_code_snippet_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(CodeSnippetController::class));
    }

    public function test_syntax_highlighting_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(SyntaxHighlightingController::class));
    }

    public function test_highlighter_library_is_installed(): void
    {
        $this->assertTrue(class_exists(Highlighter::class));
    }

    public function test_code_snippet_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(CodeSnippetResource::class));
    }

    public function test_code_snippet_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(CodeSnippetCollection::class));
    }

    public function test_code_snippet_policy_class_exists(): void
    {
        $this->assertTrue(class_exists(CodeSnippetPolicy::class));
    }

    public function test_store_code_snippet_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreCodeSnippetRequest::class));
    }

    public function test_update_code_snippet_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateCodeSnippetRequest::class));
    }

    public function test_detect_language_request_class_exists(): void
    {
        $this->assertTrue(class_exists(DetectLanguageRequest::class));
    }

    public function test_highlight_code_request_class_exists(): void
    {
        $this->assertTrue(class_exists(HighlightCodeRequest::class));
    }

    public function test_syntax_highlighting_config_file_exists(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/syntax-highlighting.php';
        $this->assertFileExists($configPath);
    }

    public function test_code_snippet_factory_class_exists(): void
    {
        $this->assertTrue(class_exists(CodeSnippetFactory::class));
    }

    public function test_code_snippet_seeder_class_exists(): void
    {
        $this->assertTrue(class_exists(CodeSnippetSeeder::class));
    }
}
