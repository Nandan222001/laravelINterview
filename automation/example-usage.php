<?php

/**
 * Example Usage of Interview Bank Automation Suite
 *
 * This file demonstrates how to use each component individually
 */

require_once __DIR__.'/QuestionValidator.php';
require_once __DIR__.'/MetadataExtractor.php';
require_once __DIR__.'/StatisticsDashboard.php';
require_once __DIR__.'/CrossReferenceMapper.php';
require_once __DIR__.'/SearchIndexGenerator.php';

use InterviewBank\Automation\CrossReferenceMapper;
use InterviewBank\Automation\MetadataExtractor;
use InterviewBank\Automation\QuestionValidator;
use InterviewBank\Automation\SearchIndexGenerator;
use InterviewBank\Automation\StatisticsDashboard;

echo "=== Interview Bank Automation - Example Usage ===\n\n";

// Example 1: Validate a single file
echo "Example 1: Validate a single markdown file\n";
echo str_repeat('-', 50)."\n";

$validator = new QuestionValidator;
$singleFileResult = $validator->validateMarkdownFile('interview-bank/INDEX.md');

echo 'Valid: '.($singleFileResult['valid'] ? 'Yes' : 'No')."\n";
echo 'Errors: '.count($singleFileResult['errors'])."\n";
echo 'Warnings: '.count($singleFileResult['warnings'])."\n";
echo 'Questions found: '.$singleFileResult['stats']['questions']."\n";
echo 'Code blocks: '.$singleFileResult['stats']['code_blocks']."\n";
echo 'Mermaid diagrams: '.$singleFileResult['stats']['mermaid_diagrams']."\n\n";

// Example 2: Extract metadata from a single file
echo "Example 2: Extract metadata from a file\n";
echo str_repeat('-', 50)."\n";

$extractor = new MetadataExtractor;
$metadata = $extractor->extractFromFile('interview-bank/INDEX.md');

echo 'File: '.$metadata['relative_path']."\n";
echo 'Domain: '.$metadata['domain']."\n";
echo 'Difficulty: '.$metadata['difficulty']."\n";
echo 'Technologies: '.count($metadata['technologies'])."\n";
echo 'Topics: '.count($metadata['topics'])."\n";
echo 'Questions: '.count($metadata['questions'])."\n";
echo 'Word count: '.number_format($metadata['word_count'])."\n\n";

if (! empty($metadata['technologies'])) {
    echo "Top 5 Technologies:\n";
    foreach (array_slice($metadata['technologies'], 0, 5) as $tech) {
        echo "  - {$tech['name']} ({$tech['mentions']} mentions)\n";
    }
    echo "\n";
}

// Example 3: Generate statistics for specific domain
echo "Example 3: Generate statistics for a specific domain\n";
echo str_repeat('-', 50)."\n";

$phpLaravelDir = 'interview-bank/php-laravel-api-security';
if (is_dir($phpLaravelDir)) {
    $domainMetadata = $extractor->extractFromDirectory($phpLaravelDir);
    $summary = $extractor->generateSummary($domainMetadata);

    echo "Domain: PHP Laravel & API Security\n";
    echo 'Files: '.$summary['total_files']."\n";
    echo 'Questions: '.$summary['total_questions']."\n";
    echo 'Code examples: '.$summary['total_code_examples']."\n";
    echo 'Diagrams: '.$summary['total_mermaid_diagrams']."\n\n";

    echo "Difficulty breakdown:\n";
    foreach ($summary['by_difficulty'] as $level => $count) {
        echo '  - '.ucfirst($level).": {$count}\n";
    }
    echo "\n";
}

// Example 4: Find cross-references between two files
echo "Example 4: Find cross-references\n";
echo str_repeat('-', 50)."\n";

$allMetadata = $extractor->extractFromDirectory('interview-bank');
$mapper = new CrossReferenceMapper;
$crossRefs = $mapper->mapCrossReferences(array_slice($allMetadata, 0, 10));

echo 'Cross-references found: '.count($crossRefs)."\n";
if (! empty($crossRefs)) {
    $topRef = $crossRefs[0];
    echo "\nTop cross-reference:\n";
    echo '  Source: '.basename($topRef['source_file'])."\n";
    echo '  Target: '.basename($topRef['target_file'])."\n";
    echo '  Similarity score: '.$topRef['similarity_score']."\n";
    echo '  Relationship: '.$topRef['relationship_type']."\n";

    if (! empty($topRef['common_technologies'])) {
        echo '  Common tech: '.implode(', ', array_slice($topRef['common_technologies'], 0, 3))."\n";
    }
}
echo "\n";

// Example 5: Search functionality
echo "Example 5: Search for specific content\n";
echo str_repeat('-', 50)."\n";

$searchGen = new SearchIndexGenerator;
$searchIndex = $searchGen->generateSearchIndex($allMetadata);

$searchTerm = 'webhook';
$results = array_filter($searchIndex['documents'], function ($doc) use ($searchTerm) {
    return stripos($doc['searchable_content'], $searchTerm) !== false;
});

echo "Searching for: '{$searchTerm}'\n";
echo 'Results found: '.count($results)."\n\n";

if (! empty($results)) {
    echo "Top 3 results:\n";
    foreach (array_slice($results, 0, 3) as $result) {
        echo "  - {$result['title']} ({$result['domain']})\n";
        echo "    Questions: {$result['question_count']}, Difficulty: {$result['difficulty']}\n";
    }
}
echo "\n";

// Example 6: Technology analysis
echo "Example 6: Analyze technology usage\n";
echo str_repeat('-', 50)."\n";

$techStats = $searchIndex['technologies'];
uasort($techStats, fn ($a, $b) => $b['total_mentions'] <=> $a['total_mentions']);

echo "Top 10 technologies:\n";
$count = 0;
foreach ($techStats as $tech => $stats) {
    if ($count++ >= 10) {
        break;
    }
    echo sprintf("  %2d. %-20s Files: %2d, Mentions: %4d\n",
        $count, $tech, $stats['document_count'], $stats['total_mentions']);
}
echo "\n";

// Example 7: Domain statistics
echo "Example 7: Domain statistics\n";
echo str_repeat('-', 50)."\n";

foreach ($searchIndex['domains'] as $domain => $stats) {
    $displayName = ucwords(str_replace(['-', '_'], ' ', $domain));
    echo "{$displayName}:\n";
    echo "  Documents: {$stats['document_count']}\n";
    echo "  Questions: {$stats['question_count']}\n";
    echo '  Technologies: '.count($stats['technologies'])."\n";

    $difficulties = $stats['difficulty_distribution'];
    $total = array_sum($difficulties);
    if ($total > 0) {
        foreach ($difficulties as $level => $count) {
            if ($count > 0) {
                $percentage = round(($count / $total) * 100);
                echo '  - '.ucfirst($level).": {$count} ({$percentage}%)\n";
            }
        }
    }
    echo "\n";
}

// Example 8: Generate custom report
echo "Example 8: Generate custom dashboard\n";
echo str_repeat('-', 50)."\n";

$dashboard = new StatisticsDashboard;
$jsonReport = json_decode($dashboard->generateJsonReport($allMetadata), true);

echo "Overall Statistics:\n";
echo '  Total questions: '.number_format($jsonReport['summary']['total_questions'])."\n";
echo '  Total code blocks: '.number_format($jsonReport['summary']['total_code_blocks'])."\n";
echo '  Total diagrams: '.number_format($jsonReport['summary']['total_mermaid_diagrams'])."\n";
echo '  Domains: '.$jsonReport['summary']['domain_count']."\n";
echo '  Technologies: '.$jsonReport['summary']['technology_count']."\n";
echo '  Topics: '.$jsonReport['summary']['topic_count']."\n";
echo "\n";

echo "=== Examples Complete ===\n\n";
echo "For full automation, run:\n";
echo "  php automation/run-all.php\n\n";
