<?php
/**
 * Example Usage of PDF Answer Generator
 * 
 * This file demonstrates various ways to use the PDF generation functionality
 * programmatically in your own scripts.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/generate-pdf-answers.php';

// Example 1: Generate all PDFs
function generateAllPDFsExample()
{
    echo "Example 1: Generating all PDFs\n";
    echo "================================\n\n";
    
    $exporter = new AnswerPDFExporter();
    $exporter->generateAll();
    
    echo "\n✓ All PDFs generated!\n\n";
}

// Example 2: Generate specific category PDFs
function generateSpecificCategoriesExample()
{
    echo "Example 2: Generating specific categories\n";
    echo "==========================================\n\n";
    
    $exporter = new AnswerPDFExporter();
    
    $categories = [
        'php-core-answers',
        'laravel-framework-answers',
        'database-mysql-answers'
    ];
    
    foreach ($categories as $category) {
        echo "Generating: $category\n";
        $result = $exporter->generatePDF($category);
        
        if ($result) {
            echo "  ✓ Success\n";
        } else {
            echo "  ✗ Failed\n";
        }
    }
    
    echo "\n✓ Selected PDFs generated!\n\n";
}

// Example 3: Generate PDF with error handling
function generateWithErrorHandlingExample()
{
    echo "Example 3: Generate with error handling\n";
    echo "========================================\n\n";
    
    $exporter = new AnswerPDFExporter();
    $category = 'php-core-answers';
    
    try {
        echo "Attempting to generate: $category\n";
        
        $result = $exporter->generatePDF($category);
        
        if ($result) {
            echo "✓ PDF generated successfully!\n";
            
            $pdfFile = __DIR__ . '/../pdf-exports/' . $category . '.pdf';
            
            if (file_exists($pdfFile)) {
                $size = filesize($pdfFile);
                $sizeKB = round($size / 1024, 2);
                echo "  File size: {$sizeKB} KB\n";
                echo "  Location: $pdfFile\n";
            }
        } else {
            echo "✗ PDF generation failed\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Example 4: Check available categories before generating
function checkAndGenerateExample()
{
    echo "Example 4: Check available categories first\n";
    echo "============================================\n\n";
    
    $answersDir = __DIR__ . '/../answers';
    $htmlFiles = glob($answersDir . '/*.html');
    
    if (empty($htmlFiles)) {
        echo "No answer files found!\n";
        return;
    }
    
    echo "Found " . count($htmlFiles) . " answer files:\n";
    
    $categories = [];
    foreach ($htmlFiles as $file) {
        $category = basename($file, '.html');
        $categories[] = $category;
        echo "  - $category\n";
    }
    
    echo "\nGenerating PDFs for these categories...\n\n";
    
    $exporter = new AnswerPDFExporter();
    
    foreach ($categories as $category) {
        $exporter->generatePDF($category);
    }
    
    echo "\n✓ All available PDFs generated!\n\n";
}

// Example 5: Generate PDFs and create index
function generateWithIndexExample()
{
    echo "Example 5: Generate PDFs and create index file\n";
    echo "===============================================\n\n";
    
    $exporter = new AnswerPDFExporter();
    $exporter->generateAll();
    
    // Create an index of generated PDFs
    $pdfDir = __DIR__ . '/../pdf-exports';
    $pdfFiles = glob($pdfDir . '/*.pdf');
    
    $index = [
        'generated_at' => date('Y-m-d H:i:s'),
        'total_pdfs' => count($pdfFiles),
        'pdfs' => []
    ];
    
    foreach ($pdfFiles as $pdfFile) {
        $category = basename($pdfFile, '.pdf');
        $size = filesize($pdfFile);
        
        $index['pdfs'][] = [
            'category' => $category,
            'filename' => basename($pdfFile),
            'size_bytes' => $size,
            'size_kb' => round($size / 1024, 2),
            'path' => 'pdf-exports/' . basename($pdfFile)
        ];
    }
    
    // Save index
    $indexFile = $pdfDir . '/index.json';
    file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
    
    echo "✓ PDFs generated and indexed!\n";
    echo "  Index saved to: $indexFile\n\n";
    
    // Display summary
    echo "Summary:\n";
    echo "  Total PDFs: " . $index['total_pdfs'] . "\n";
    echo "  Total size: " . round(array_sum(array_column($index['pdfs'], 'size_kb')), 2) . " KB\n";
    echo "\n";
}

// Example 6: Conditional generation (only if HTML is newer than PDF)
function generateIfNewerExample()
{
    echo "Example 6: Generate only if HTML is newer than PDF\n";
    echo "===================================================\n\n";
    
    $answersDir = __DIR__ . '/../answers';
    $pdfDir = __DIR__ . '/../pdf-exports';
    $exporter = new AnswerPDFExporter();
    
    $htmlFiles = glob($answersDir . '/*.html');
    $generated = 0;
    $skipped = 0;
    
    foreach ($htmlFiles as $htmlFile) {
        $category = basename($htmlFile, '.html');
        $pdfFile = $pdfDir . '/' . $category . '.pdf';
        
        $htmlTime = filemtime($htmlFile);
        $pdfTime = file_exists($pdfFile) ? filemtime($pdfFile) : 0;
        
        if ($htmlTime > $pdfTime) {
            echo "Generating (HTML newer): $category\n";
            $exporter->generatePDF($category);
            $generated++;
        } else {
            echo "Skipping (PDF up-to-date): $category\n";
            $skipped++;
        }
    }
    
    echo "\n✓ Complete!\n";
    echo "  Generated: $generated\n";
    echo "  Skipped: $skipped\n\n";
}

// Main execution - uncomment the example you want to run
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

echo "PDF Answer Generator - Usage Examples\n";
echo "======================================\n\n";

// Uncomment the example you want to run:

// generateAllPDFsExample();
// generateSpecificCategoriesExample();
// generateWithErrorHandlingExample();
// checkAndGenerateExample();
// generateWithIndexExample();
// generateIfNewerExample();

// Or run interactively
echo "Available examples:\n";
echo "1. Generate all PDFs\n";
echo "2. Generate specific categories\n";
echo "3. Generate with error handling\n";
echo "4. Check and generate\n";
echo "5. Generate with index\n";
echo "6. Generate only if newer\n";
echo "\n";
echo "To run an example, edit this file and uncomment the desired function call.\n";
echo "\n";
