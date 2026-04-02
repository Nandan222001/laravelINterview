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

echo "===========================================\n";
echo " Interview Bank Automation Suite\n";
echo "===========================================\n\n";

$baseDir = dirname(__DIR__) . '/interview-bank';
$outputDir = __DIR__ . '/output';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "[1/5] Validating markdown files...\n";
$validator = new QuestionValidator();
$validationResults = $validator->validateDirectory($baseDir);

$validationReport = $validator->generateReport($validationResults);
file_put_contents($outputDir . '/validation-report.md', $validationReport);
echo "  ✓ Validation complete. Report saved to output/validation-report.md\n";

$totalErrors = array_sum(array_map(fn($r) => count($r['errors']), $validationResults));
$totalWarnings = array_sum(array_map(fn($r) => count($r['warnings']), $validationResults));
echo "  - Errors: {$totalErrors}\n";
echo "  - Warnings: {$totalWarnings}\n\n";

echo "[2/5] Extracting metadata...\n";
$extractor = new MetadataExtractor();
$metadataCollection = $extractor->extractFromDirectory($baseDir);

$metadataSummary = $extractor->generateSummary($metadataCollection);
file_put_contents($outputDir . '/metadata-summary.json', json_encode($metadataSummary, JSON_PRETTY_PRINT));
echo "  ✓ Metadata extracted from " . count($metadataCollection) . " files\n";
echo "  ✓ Summary saved to output/metadata-summary.json\n\n";

echo "[3/5] Generating statistics dashboard...\n";
$dashboard = new StatisticsDashboard();

$markdownDashboard = $dashboard->generateDashboard($metadataCollection);
file_put_contents($outputDir . '/statistics-dashboard.md', $markdownDashboard);
echo "  ✓ Markdown dashboard saved to output/statistics-dashboard.md\n";

$jsonReport = $dashboard->generateJsonReport($metadataCollection);
file_put_contents($outputDir . '/statistics-report.json', $jsonReport);
echo "  ✓ JSON report saved to output/statistics-report.json\n";

$htmlDashboard = $dashboard->generateHtmlDashboard($metadataCollection);
file_put_contents($outputDir . '/statistics-dashboard.html', $htmlDashboard);
echo "  ✓ HTML dashboard saved to output/statistics-dashboard.html\n\n";

echo "[4/5] Mapping cross-references...\n";
$crossRefMapper = new CrossReferenceMapper();
$crossReferences = $crossRefMapper->mapCrossReferences($metadataCollection);

$crossRefReport = $crossRefMapper->generateCrossReferenceReport($crossReferences);
file_put_contents($outputDir . '/cross-reference-report.md', $crossRefReport);
echo "  ✓ Found " . count($crossReferences) . " cross-references\n";
echo "  ✓ Report saved to output/cross-reference-report.md\n";

$crossRefJson = $crossRefMapper->generateJsonMapping($crossReferences);
file_put_contents($outputDir . '/cross-references.json', $crossRefJson);
echo "  ✓ JSON mapping saved to output/cross-references.json\n";

$networkGraph = $crossRefMapper->generateNetworkGraph($crossReferences);
file_put_contents($outputDir . '/domain-network-graph.md', $networkGraph);
echo "  ✓ Network graph saved to output/domain-network-graph.md\n\n";

echo "[5/5] Generating search indices...\n";
$searchGenerator = new SearchIndexGenerator();

$fullIndex = $searchGenerator->generateJsonIndex($metadataCollection);
file_put_contents($outputDir . '/search-index.json', $fullIndex);
echo "  ✓ Full search index saved to output/search-index.json\n";

$compactIndex = $searchGenerator->generateCompactJsonIndex($metadataCollection);
file_put_contents($outputDir . '/search-index-compact.json', $compactIndex);
echo "  ✓ Compact index saved to output/search-index-compact.json\n";

$lunrIndex = $searchGenerator->generateLunrIndex($metadataCollection);
file_put_contents($outputDir . '/lunr-index.json', $lunrIndex);
echo "  ✓ Lunr.js index saved to output/lunr-index.json\n";

$esMapping = $searchGenerator->generateElasticsearchMapping();
file_put_contents($outputDir . '/elasticsearch-mapping.json', $esMapping);
echo "  ✓ Elasticsearch mapping saved to output/elasticsearch-mapping.json\n";

$searchInterface = $searchGenerator->generateSearchInterface();
file_put_contents($outputDir . '/search.html', $searchInterface);
echo "  ✓ Search interface saved to output/search.html\n\n";

echo "===========================================\n";
echo " Summary\n";
echo "===========================================\n";
echo "Total files processed: " . count($metadataCollection) . "\n";
echo "Total questions: " . array_sum(array_map(fn($m) => count($m['questions']), $metadataCollection)) . "\n";
echo "Total code examples: " . array_sum(array_map(fn($m) => count($m['code_examples']), $metadataCollection)) . "\n";
echo "Total Mermaid diagrams: " . array_sum(array_map(fn($m) => count($m['mermaid_diagrams']), $metadataCollection)) . "\n";
echo "Validation errors: {$totalErrors}\n";
echo "Validation warnings: {$totalWarnings}\n";
echo "Cross-references found: " . count($crossReferences) . "\n";
echo "\nAll outputs saved to: {$outputDir}/\n";
echo "===========================================\n";

if ($totalErrors > 0) {
    echo "\n⚠️  WARNING: There are validation errors that should be addressed.\n";
    echo "See validation-report.md for details.\n";
    exit(1);
}

echo "\n✅ All automation tasks completed successfully!\n";
