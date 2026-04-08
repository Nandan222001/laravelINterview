<?php
/**
 * PDF Generator for Interview Bank Answers (mPDF version)
 * 
 * Alternative implementation using mPDF library
 * Better HTML/CSS support, easier to use but slightly larger file sizes
 * 
 * Usage: php automation/generate-pdf-answers-mpdf.php [category]
 * Example: php automation/generate-pdf-answers-mpdf.php php-core-answers
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class AnswerPDFExporterMPDF
{
    private $answersDir;
    private $outputDir;
    private $categoryColors = [
        'php-core' => '#667eea',
        'laravel' => '#e3342f',
        'javascript' => '#f7df1e',
        'database' => '#336791',
        'devops' => '#326ce5',
        'api' => '#009688',
        'frontend' => '#61dafb',
        'testing' => '#94c748',
        'oop' => '#764ba2',
        'cms' => '#21759b',
        'codeigniter' => '#dd4814',
        'cloud' => '#ff9900',
        'realtime' => '#010101',
        'ai' => '#412991',
        'advanced' => '#9333ea',
        'coding' => '#f97316',
    ];
    
    public function __construct()
    {
        $this->answersDir = __DIR__ . '/../answers';
        $this->outputDir = __DIR__ . '/../pdf-exports';
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function generateAll()
    {
        $htmlFiles = glob($this->answersDir . '/*.html');
        
        if (empty($htmlFiles)) {
            echo "No HTML answer files found in {$this->answersDir}\n";
            return;
        }
        
        echo "Found " . count($htmlFiles) . " answer files\n";
        echo "Generating PDFs with mPDF...\n\n";
        
        foreach ($htmlFiles as $htmlFile) {
            $category = basename($htmlFile, '.html');
            $this->generatePDF($category);
        }
        
        echo "\nAll PDFs generated successfully!\n";
        echo "Output directory: {$this->outputDir}\n";
    }
    
    public function generatePDF($category)
    {
        $htmlFile = $this->answersDir . '/' . $category . '.html';
        
        if (!file_exists($htmlFile)) {
            echo "Error: File not found: $htmlFile\n";
            return false;
        }
        
        echo "Processing: $category... ";
        
        $html = file_get_contents($htmlFile);
        $color = $this->getCategoryColor($category);
        
        $html = $this->prepareHTMLForPDF($html, $color);
        
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_header' => 5,
                'margin_footer' => 10,
                'default_font' => 'helvetica'
            ]);
            
            $title = $this->extractTitle($html);
            
            $mpdf->SetTitle($title);
            $mpdf->SetAuthor('Interview Bank');
            $mpdf->SetCreator('Interview Bank PDF Generator');
            $mpdf->SetSubject('Interview Preparation Materials');
            
            $mpdf->WriteHTML($html);
            
            $outputFile = $this->outputDir . '/' . $category . '.pdf';
            $mpdf->Output($outputFile, 'F');
            
            echo "✓ Generated: " . basename($outputFile) . "\n";
            
            return true;
            
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function prepareHTMLForPDF($html, $color)
    {
        $pdfStyles = $this->getPDFStyles($color);
        
        $html = preg_replace(
            '/<link[^>]+href=["\']https:\/\/cdnjs\.cloudflare\.com[^>]+>/i',
            '',
            $html
        );
        
        $html = preg_replace(
            '/<script[^>]*>.*?<\/script>/is',
            '',
            $html
        );
        
        $html = str_replace(
            '<a href="../index.html" class="back-link">← Back to Interview Bank</a>',
            '',
            $html
        );
        
        $html = preg_replace(
            '/<style[^>]*>.*?<\/style>/is',
            '<style>' . $pdfStyles . '</style>',
            $html,
            1
        );
        
        $html = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $html);
        
        return $html;
    }
    
    private function getPDFStyles($primaryColor)
    {
        return <<<CSS
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: helvetica, sans-serif;
    line-height: 1.6;
    color: #333;
    font-size: 10pt;
}

header {
    background: linear-gradient(135deg, {$primaryColor} 0%, {$primaryColor} 100%);
    color: white;
    padding: 30px 20px;
    text-align: center;
    margin-bottom: 20px;
    page-break-after: always;
}

header h1 {
    font-size: 24pt;
    margin-bottom: 10px;
}

header p {
    font-size: 12pt;
    opacity: 0.9;
}

.container {
    padding: 0 10px;
}

.back-link {
    display: none;
}

.toc {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 20px;
    page-break-after: always;
}

.toc h2 {
    color: {$primaryColor};
    margin-bottom: 10px;
    font-size: 16pt;
}

.toc ul {
    list-style: none;
    padding-left: 0;
}

.toc li {
    margin-bottom: 5px;
    padding: 5px;
}

.toc a {
    color: #333;
    text-decoration: none;
}

.topic-section {
    background: white;
    padding: 15px 0;
    margin-bottom: 15px;
    page-break-inside: avoid;
}

.topic-section h2 {
    color: {$primaryColor};
    font-size: 16pt;
    margin-bottom: 12px;
    padding-bottom: 5px;
    border-bottom: 2px solid {$primaryColor};
    page-break-after: avoid;
}

.topic-section h3 {
    color: {$primaryColor};
    font-size: 13pt;
    margin-top: 15px;
    margin-bottom: 8px;
    page-break-after: avoid;
}

.topic-section h4 {
    color: #555;
    font-size: 11pt;
    margin-top: 12px;
    margin-bottom: 6px;
    page-break-after: avoid;
}

.topic-section p {
    margin-bottom: 8px;
    color: #555;
    text-align: justify;
}

.topic-section ul,
.topic-section ol {
    margin-left: 20px;
    margin-bottom: 10px;
}

.topic-section li {
    margin-bottom: 4px;
    color: #555;
}

.code-example {
    margin: 10px 0;
    page-break-inside: avoid;
}

.code-example pre {
    background: #282c34;
    color: #abb2bf;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 8pt;
    line-height: 1.4;
}

.code-example code {
    font-family: courier, monospace;
    font-size: 8pt;
}

.info-box {
    background: #e3f2fd;
    border-left: 3px solid #2196f3;
    padding: 10px 12px;
    margin: 10px 0;
    page-break-inside: avoid;
}

.warning-box {
    background: #fff3e0;
    border-left: 3px solid #ff9800;
    padding: 10px 12px;
    margin: 10px 0;
    page-break-inside: avoid;
}

.success-box {
    background: #e8f5e9;
    border-left: 3px solid #4caf50;
    padding: 10px 12px;
    margin: 10px 0;
    page-break-inside: avoid;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
    font-size: 9pt;
    page-break-inside: avoid;
}

th, td {
    padding: 6px 8px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background: {$primaryColor};
    color: white;
    font-weight: bold;
}

tr:nth-child(even) {
    background: #f5f5f5;
}

footer {
    display: none;
}
CSS;
    }
    
    private function extractTitle($html)
    {
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches)) {
            return strip_tags($matches[1]);
        }
        return 'Interview Answers';
    }
    
    private function getCategoryColor($category)
    {
        foreach ($this->categoryColors as $key => $color) {
            if (strpos($category, $key) !== false) {
                return $color;
            }
        }
        return '#667eea';
    }
}

function showUsage()
{
    echo "PDF Answer Generator for Interview Bank (mPDF)\n";
    echo "==============================================\n\n";
    echo "Usage:\n";
    echo "  php automation/generate-pdf-answers-mpdf.php [category]\n\n";
    echo "Examples:\n";
    echo "  php automation/generate-pdf-answers-mpdf.php                    # Generate all PDFs\n";
    echo "  php automation/generate-pdf-answers-mpdf.php php-core-answers   # Generate specific PDF\n";
    echo "  php automation/generate-pdf-answers-mpdf.php laravel-framework-answers\n\n";
    echo "Available categories:\n";
    $answersDir = __DIR__ . '/../answers';
    if (is_dir($answersDir)) {
        $files = glob($answersDir . '/*.html');
        foreach ($files as $file) {
            $category = basename($file, '.html');
            echo "  - $category\n";
        }
    }
    echo "\n";
}

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "Error: Dependencies not installed.\n";
    echo "Please run: composer require mpdf/mpdf\n";
    exit(1);
}

$category = $argv[1] ?? null;

if (isset($argv[1]) && ($argv[1] === '--help' || $argv[1] === '-h')) {
    showUsage();
    exit(0);
}

try {
    $exporter = new AnswerPDFExporterMPDF();
    
    if ($category) {
        echo "Generating PDF for category: $category (using mPDF)\n\n";
        $exporter->generatePDF($category);
    } else {
        echo "Generating all PDFs (using mPDF)...\n\n";
        $exporter->generateAll();
    }
    
    echo "\n✓ PDF generation completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
