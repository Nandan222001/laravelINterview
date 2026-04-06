<?php

// Simple syntax test for all automation components

echo "Testing automation components...\n\n";

$files = [
    'QuestionValidator.php',
    'MetadataExtractor.php',
    'StatisticsDashboard.php',
    'CrossReferenceMapper.php',
    'SearchIndexGenerator.php',
    'run-all.php',
    'run-validation.php',
    'index.php',
    'example-usage.php',
    'config.php',
];

$allGood = true;

foreach ($files as $file) {
    if (file_exists($file)) {
        $result = exec('php -l '.escapeshellarg($file).' 2>&1', $output, $returnCode);
        if ($returnCode === 0) {
            echo "✓ {$file}: OK\n";
        } else {
            echo "✗ {$file}: FAILED\n";
            echo '  '.implode("\n  ", $output)."\n";
            $allGood = false;
        }
        $output = [];
    } else {
        echo "✗ {$file}: FILE NOT FOUND\n";
        $allGood = false;
    }
}

echo "\n";

if ($allGood) {
    echo "All syntax checks passed!\n";
    exit(0);
} else {
    echo "Some syntax checks failed!\n";
    exit(1);
}
