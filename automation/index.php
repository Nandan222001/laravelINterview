<?php

use InterviewBank\Automation\CrossReferenceMapper;
use InterviewBank\Automation\MetadataExtractor;
use InterviewBank\Automation\SearchIndexGenerator;
use InterviewBank\Automation\StatisticsDashboard;

/**
 * Interview Bank Automation Suite - Main Index
 *
 * This file provides a simple CLI interface to all automation tools
 */
function showBanner(): void
{
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║        Interview Bank - Automation Suite v1.0               ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
}

function showMenu(): void
{
    echo "Available Commands:\n\n";
    echo "  1. run-all          - Run complete automation suite\n";
    echo "  2. validate         - Validate markdown files only\n";
    echo "  3. extract          - Extract metadata only\n";
    echo "  4. dashboard        - Generate statistics dashboard\n";
    echo "  5. cross-ref        - Map cross-references\n";
    echo "  6. search-index     - Generate search indices\n";
    echo "  7. examples         - Show usage examples\n";
    echo "  8. help             - Show detailed help\n";
    echo "  9. clean            - Clean output directory\n";
    echo "  0. exit             - Exit\n";
    echo "\n";
}

function showHelp(): void
{
    echo "\n";
    echo "=== HELP ===\n\n";
    echo "Quick Start:\n";
    echo "  php index.php run-all\n\n";

    echo "Individual Components:\n";
    echo "  php run-validation.php       - Validate files\n";
    echo "  php run-all.php              - Run everything\n";
    echo "  php example-usage.php        - See code examples\n\n";

    echo "Documentation:\n";
    echo "  README.md           - Full documentation\n";
    echo "  QUICK_START.md      - Quick start guide\n";
    echo "  IMPLEMENTATION.md   - Technical details\n\n";

    echo "Output Location:\n";
    echo "  automation/output/  - All generated files\n\n";

    echo "Key Files:\n";
    echo "  statistics-dashboard.html  - Visual statistics\n";
    echo "  search.html                - Search interface\n";
    echo "  validation-report.md       - Validation results\n\n";
}

function runCommand(string $command): int
{
    $commands = [
        'run-all' => 'run-all.php',
        'validate' => 'run-validation.php',
        'examples' => 'example-usage.php',
    ];

    if (isset($commands[$command])) {
        echo "\nExecuting: {$commands[$command]}\n";
        echo str_repeat('=', 60)."\n\n";
        require $commands[$command];

        return 0;
    }

    switch ($command) {
        case 'extract':
            return runExtract();
        case 'dashboard':
            return runDashboard();
        case 'cross-ref':
            return runCrossRef();
        case 'search-index':
            return runSearchIndex();
        case 'clean':
            return cleanOutput();
        case 'help':
            showHelp();

            return 0;
        default:
            echo "Unknown command: {$command}\n";

            return 1;
    }
}

function runExtract(): int
{
    require_once 'MetadataExtractor.php';

    echo "\n=== Extracting Metadata ===\n\n";

    $extractor = new MetadataExtractor;
    $metadata = $extractor->extractFromDirectory('../interview-bank');

    $outputDir = __DIR__.'/output';
    if (! is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    file_put_contents($outputDir.'/metadata-full.json',
        json_encode($metadata, JSON_PRETTY_PRINT));

    $summary = $extractor->generateSummary($metadata);
    file_put_contents($outputDir.'/metadata-summary.json',
        json_encode($summary, JSON_PRETTY_PRINT));

    echo '✓ Extracted metadata from '.count($metadata)." files\n";
    echo "✓ Saved to output/metadata-full.json\n";
    echo "✓ Summary saved to output/metadata-summary.json\n\n";

    return 0;
}

function runDashboard(): int
{
    require_once 'MetadataExtractor.php';
    require_once 'StatisticsDashboard.php';

    echo "\n=== Generating Dashboard ===\n\n";

    $extractor = new MetadataExtractor;
    $metadata = $extractor->extractFromDirectory('../interview-bank');

    $dashboard = new StatisticsDashboard;

    $outputDir = __DIR__.'/output';
    if (! is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    file_put_contents($outputDir.'/statistics-dashboard.md',
        $dashboard->generateDashboard($metadata));
    file_put_contents($outputDir.'/statistics-dashboard.html',
        $dashboard->generateHtmlDashboard($metadata));
    file_put_contents($outputDir.'/statistics-report.json',
        $dashboard->generateJsonReport($metadata));

    echo "✓ Markdown dashboard created\n";
    echo "✓ HTML dashboard created\n";
    echo "✓ JSON report created\n";
    echo "\nOpen: output/statistics-dashboard.html\n\n";

    return 0;
}

function runCrossRef(): int
{
    require_once 'MetadataExtractor.php';
    require_once 'CrossReferenceMapper.php';

    echo "\n=== Mapping Cross-References ===\n\n";

    $extractor = new MetadataExtractor;
    $metadata = $extractor->extractFromDirectory('../interview-bank');

    $mapper = new CrossReferenceMapper;
    $refs = $mapper->mapCrossReferences($metadata);

    $outputDir = __DIR__.'/output';
    if (! is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    file_put_contents($outputDir.'/cross-reference-report.md',
        $mapper->generateCrossReferenceReport($refs));
    file_put_contents($outputDir.'/cross-references.json',
        $mapper->generateJsonMapping($refs));
    file_put_contents($outputDir.'/domain-network-graph.md',
        $mapper->generateNetworkGraph($refs));

    echo '✓ Found '.count($refs)." cross-references\n";
    echo "✓ Report saved to output/cross-reference-report.md\n";
    echo "✓ JSON mapping saved\n";
    echo "✓ Network graph created\n\n";

    return 0;
}

function runSearchIndex(): int
{
    require_once 'MetadataExtractor.php';
    require_once 'SearchIndexGenerator.php';

    echo "\n=== Generating Search Indices ===\n\n";

    $extractor = new MetadataExtractor;
    $metadata = $extractor->extractFromDirectory('../interview-bank');

    $generator = new SearchIndexGenerator;

    $outputDir = __DIR__.'/output';
    if (! is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    file_put_contents($outputDir.'/search-index.json',
        $generator->generateJsonIndex($metadata));
    file_put_contents($outputDir.'/search-index-compact.json',
        $generator->generateCompactJsonIndex($metadata));
    file_put_contents($outputDir.'/lunr-index.json',
        $generator->generateLunrIndex($metadata));
    file_put_contents($outputDir.'/search.html',
        $generator->generateSearchInterface());

    echo "✓ Full search index created\n";
    echo "✓ Compact index created\n";
    echo "✓ Lunr.js index created\n";
    echo "✓ Search interface created\n";
    echo "\nOpen: output/search.html\n\n";

    return 0;
}

function cleanOutput(): int
{
    $outputDir = __DIR__.'/output';

    if (! is_dir($outputDir)) {
        echo "Output directory doesn't exist.\n";

        return 0;
    }

    echo "\n=== Cleaning Output Directory ===\n\n";

    $files = glob($outputDir.'/*');
    $count = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }

    echo "✓ Removed {$count} files from output/\n\n";

    return 0;
}

function interactiveMode(): void
{
    while (true) {
        showMenu();
        echo 'Enter command (or number): ';
        $input = trim(fgets(STDIN));

        if ($input === '0' || $input === 'exit') {
            echo "\nGoodbye!\n\n";
            break;
        }

        $commandMap = [
            '1' => 'run-all',
            '2' => 'validate',
            '3' => 'extract',
            '4' => 'dashboard',
            '5' => 'cross-ref',
            '6' => 'search-index',
            '7' => 'examples',
            '8' => 'help',
            '9' => 'clean',
        ];

        $command = $commandMap[$input] ?? $input;

        runCommand($command);

        echo "\nPress Enter to continue...";
        fgets(STDIN);
        echo "\n".str_repeat('=', 60)."\n\n";
    }
}

// Main execution
showBanner();

if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// Check if command provided as argument
if ($argc > 1) {
    $command = $argv[1];
    $exitCode = runCommand($command);
    exit($exitCode);
}

// No arguments - show interactive mode
echo "No command specified. Starting interactive mode...\n";
echo "(Run 'php index.php run-all' to execute everything directly)\n\n";

interactiveMode();
