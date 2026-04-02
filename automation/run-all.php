<?php

require_once __DIR__ . '/QuestionValidator.php';
require_once __DIR__ . '/MetadataExtractor.php';
require_once __DIR__ . '/StatisticsDashboard.php';
require_once __DIR__ . '/CrossReferenceMapper.php';
require_once __DIR__ . '/SearchIndexGenerator.php';

use InterviewBank\Automation\QuestionValidator;
use InterviewBank\Automation\MetadataExtractor;
use InterviewBank\Automation\StatisticsDashboard;
use InterviewBank\Automation\CrossReferenceMapper;
use InterviewBank\Automation\SearchIndexGenerator;

function printBanner(string $title): void
{
    $length = strlen($title) + 4;
    echo "\n" . str_repeat('=', $length) . "\n";
    echo "  {$title}\n";
    echo str_repeat('=', $length) . "\n\n";
}

function printStep(string $message, bool $success = true): void
{
    $icon = $success ? '✓' : '✗';
    echo "  {$icon} {$message}\n";
}

printBanner('Interview Bank - Complete Automation Suite');

$startTime = microtime(true);

$baseDir = dirname(__DIR__) . '/interview-bank';
$outputDir = __DIR__ . '/output';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

printBanner('Step 1: Code Block & Mermaid Validation');

$validator = new QuestionValidator();
$validationResults = $validator->validateDirectory($baseDir);

$validationReport = $validator->generateReport($validationResults);
file_put_contents($outputDir . '/validation-report.md', $validationReport);

$totalFiles = count($validationResults);
$validFiles = count(array_filter($validationResults, fn($r) => $r['valid']));
$totalErrors = array_sum(array_map(fn($r) => count($r['errors']), $validationResults));
$totalWarnings = array_sum(array_map(fn($r) => count($r['warnings']), $validationResults));

printStep("Validated {$totalFiles} markdown files");
printStep("Valid files: {$validFiles}");
printStep("Errors found: {$totalErrors}", $totalErrors === 0);
printStep("Warnings found: {$totalWarnings}");
printStep("Report saved: output/validation-report.md");

printBanner('Step 2: Metadata & Difficulty Extraction');

$extractor = new MetadataExtractor();
$metadataCollection = $extractor->extractFromDirectory($baseDir);
$metadataSummary = $extractor->generateSummary($metadataCollection);

file_put_contents($outputDir . '/metadata-summary.json', json_encode($metadataSummary, JSON_PRETTY_PRINT));
file_put_contents($outputDir . '/metadata-full.json', json_encode($metadataCollection, JSON_PRETTY_PRINT));

printStep("Extracted metadata from " . count($metadataCollection) . " files");
printStep("Total questions identified: " . $metadataSummary['total_questions']);
printStep("Total code examples: " . $metadataSummary['total_code_examples']);
printStep("Total diagrams: " . $metadataSummary['total_mermaid_diagrams']);
printStep("Unique technologies: " . count($metadataSummary['by_technology']));
printStep("Summary saved: output/metadata-summary.json");

printBanner('Step 3: Statistics Dashboard Generation');

$dashboard = new StatisticsDashboard();

$markdownDashboard = $dashboard->generateDashboard($metadataCollection);
file_put_contents($outputDir . '/statistics-dashboard.md', $markdownDashboard);
printStep("Markdown dashboard created");

$jsonReport = $dashboard->generateJsonReport($metadataCollection);
file_put_contents($outputDir . '/statistics-report.json', $jsonReport);
printStep("JSON report created");

$htmlDashboard = $dashboard->generateHtmlDashboard($metadataCollection);
file_put_contents($outputDir . '/statistics-dashboard.html', $htmlDashboard);
printStep("Interactive HTML dashboard created");
printStep("Open output/statistics-dashboard.html in browser");

printBanner('Step 4: Cross-Reference Mapping');

$crossRefMapper = new CrossReferenceMapper();
$crossReferences = $crossRefMapper->mapCrossReferences($metadataCollection);

$crossRefReport = $crossRefMapper->generateCrossReferenceReport($crossReferences);
file_put_contents($outputDir . '/cross-reference-report.md', $crossRefReport);
printStep("Identified " . count($crossReferences) . " cross-references");

$crossRefJson = $crossRefMapper->generateJsonMapping($crossReferences);
file_put_contents($outputDir . '/cross-references.json', $crossRefJson);
printStep("JSON mapping created");

$networkGraph = $crossRefMapper->generateNetworkGraph($crossReferences);
file_put_contents($outputDir . '/domain-network-graph.md', $networkGraph);
printStep("Domain network graph created");

$strongRefs = count(array_filter($crossReferences, fn($r) => $r['relationship_type'] === 'strong'));
printStep("Strong relationships: {$strongRefs}");

printBanner('Step 5: Search Index Generation');

$searchGenerator = new SearchIndexGenerator();

$fullIndex = $searchGenerator->generateJsonIndex($metadataCollection);
file_put_contents($outputDir . '/search-index.json', $fullIndex);
$fullSize = strlen($fullIndex);
printStep("Full search index: " . number_format($fullSize) . " bytes");

$compactIndex = $searchGenerator->generateCompactJsonIndex($metadataCollection);
file_put_contents($outputDir . '/search-index-compact.json', $compactIndex);
$compactSize = strlen($compactIndex);
printStep("Compact index: " . number_format($compactSize) . " bytes");

$lunrIndex = $searchGenerator->generateLunrIndex($metadataCollection);
file_put_contents($outputDir . '/lunr-index.json', $lunrIndex);
printStep("Lunr.js index created");

$esMapping = $searchGenerator->generateElasticsearchMapping();
file_put_contents($outputDir . '/elasticsearch-mapping.json', $esMapping);
printStep("Elasticsearch mapping created");

$searchInterface = $searchGenerator->generateSearchInterface();
file_put_contents($outputDir . '/search.html', $searchInterface);
printStep("Search web interface created");
printStep("Open output/search.html in browser");

printBanner('Final Summary');

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "Files Processed:      " . count($metadataCollection) . "\n";
echo "Total Questions:      " . array_sum(array_map(fn($m) => count($m['questions']), $metadataCollection)) . "\n";
echo "Code Examples:        " . array_sum(array_map(fn($m) => count($m['code_examples']), $metadataCollection)) . "\n";
echo "Mermaid Diagrams:     " . array_sum(array_map(fn($m) => count($m['mermaid_diagrams']), $metadataCollection)) . "\n";
echo "Validation Errors:    {$totalErrors}\n";
echo "Validation Warnings:  {$totalWarnings}\n";
echo "Cross-References:     " . count($crossReferences) . "\n";
echo "Technologies Tracked: " . count($metadataSummary['by_technology']) . "\n";
echo "Execution Time:       {$executionTime} seconds\n";
echo "\nOutput Directory:     {$outputDir}/\n";

printBanner('Output Files Generated');

$files = [
    'validation-report.md' => 'Validation errors and warnings report',
    'metadata-summary.json' => 'Summary of all extracted metadata',
    'metadata-full.json' => 'Complete metadata for all files',
    'statistics-dashboard.md' => 'Markdown statistics dashboard',
    'statistics-dashboard.html' => '📊 Interactive HTML dashboard (OPEN IN BROWSER)',
    'statistics-report.json' => 'JSON statistics report',
    'cross-reference-report.md' => 'Cross-reference mapping report',
    'cross-references.json' => 'JSON cross-reference mapping',
    'domain-network-graph.md' => 'Mermaid network graph visualization',
    'search-index.json' => 'Full search index for web integration',
    'search-index-compact.json' => 'Compact search index',
    'lunr-index.json' => 'Lunr.js search index',
    'elasticsearch-mapping.json' => 'Elasticsearch index mapping',
    'search.html' => '🔍 Search web interface (OPEN IN BROWSER)'
];

foreach ($files as $file => $description) {
    echo "  • {$file}\n    → {$description}\n";
}

if ($totalErrors > 0) {
    echo "\n⚠️  WARNING: Found {$totalErrors} validation errors!\n";
    echo "Review validation-report.md for details.\n";
    exit(1);
}

echo "\n✅ All automation tasks completed successfully!\n";
echo "\nQuick Start:\n";
echo "  1. Open output/statistics-dashboard.html for visual statistics\n";
echo "  2. Open output/search.html for searchable interface\n";
echo "  3. Review output/cross-reference-report.md for related topics\n\n";
