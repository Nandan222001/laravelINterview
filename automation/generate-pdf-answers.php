<?php
/**
 * PDF Generator for Interview Bank Answers
 * 
 * This script exports answer pages as PDF documents with:
 * - Proper formatting and syntax highlighting
 * - Organization by category
 * - Table of contents
 * - Professional layout
 * 
 * Usage: php automation/generate-pdf-answers.php [category]
 * Example: php automation/generate-pdf-answers.php php-core-answers
 * 
 * Leave category empty to generate all PDFs
 */

require_once __DIR__ . '/../vendor/autoload.php';

use TCPDF;

class PDFAnswerGenerator extends TCPDF
{
    private $categoryTitle = '';
    private $categoryColor = '#667eea';
    
    public function setCategoryInfo($title, $color = '#667eea')
    {
        $this->categoryTitle = $title;
        $this->categoryColor = $color;
    }
    
    public function Header()
    {
        if ($this->categoryTitle) {
            $this->SetFont('helvetica', 'B', 10);
            list($r, $g, $b) = $this->hexToRgb($this->categoryColor);
            $this->SetTextColor($r, $g, $b);
            $this->Cell(0, 10, $this->categoryTitle, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->SetTextColor(0, 0, 0);
            $this->Ln(5);
        }
    }
    
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
    
    private function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return [$r, $g, $b];
    }
}

class AnswerPDFExporter
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
        echo "Generating PDFs...\n\n";
        
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
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        $title = $this->extractTitle($xpath);
        $description = $this->extractDescription($xpath);
        $tocItems = $this->extractTOC($xpath);
        $sections = $this->extractSections($xpath);
        $color = $this->getCategoryColor($category);
        
        $pdf = new PDFAnswerGenerator('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setCategoryInfo($title, $color);
        
        $pdf->SetCreator('Interview Bank PDF Generator');
        $pdf->SetAuthor('Interview Bank');
        $pdf->SetTitle($title);
        $pdf->SetSubject($description);
        
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 20);
        
        $pdf->AddPage();
        
        $this->addCoverPage($pdf, $title, $description, $color);
        
        $pdf->AddPage();
        $this->addTableOfContents($pdf, $tocItems, $color);
        
        foreach ($sections as $section) {
            $pdf->AddPage();
            $this->addSection($pdf, $section, $color);
        }
        
        $outputFile = $this->outputDir . '/' . $category . '.pdf';
        $pdf->Output($outputFile, 'F');
        
        echo "✓ Generated: " . basename($outputFile) . "\n";
        
        return true;
    }
    
    private function extractTitle($xpath)
    {
        $nodes = $xpath->query('//header/h1');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        return 'Interview Answers';
    }
    
    private function extractDescription($xpath)
    {
        $nodes = $xpath->query('//header/p');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        return '';
    }
    
    private function extractTOC($xpath)
    {
        $items = [];
        $nodes = $xpath->query('//div[@class="toc"]//a');
        
        foreach ($nodes as $node) {
            $items[] = [
                'text' => trim($node->textContent),
                'id' => ltrim($node->getAttribute('href'), '#')
            ];
        }
        
        return $items;
    }
    
    private function extractSections($xpath)
    {
        $sections = [];
        $sectionNodes = $xpath->query('//section[@class="topic-section"]');
        
        foreach ($sectionNodes as $sectionNode) {
            $section = [
                'id' => $sectionNode->getAttribute('id'),
                'content' => $this->extractSectionContent($sectionNode, $xpath)
            ];
            $sections[] = $section;
        }
        
        return $sections;
    }
    
    private function extractSectionContent($sectionNode, $xpath)
    {
        $content = [];
        
        foreach ($sectionNode->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            
            $item = [
                'type' => strtolower($child->nodeName),
                'content' => '',
                'class' => $child->getAttribute('class')
            ];
            
            switch ($child->nodeName) {
                case 'h2':
                case 'h3':
                case 'h4':
                case 'p':
                    $item['content'] = trim($child->textContent);
                    break;
                    
                case 'ul':
                case 'ol':
                    $items = [];
                    foreach ($child->getElementsByTagName('li') as $li) {
                        $items[] = trim($li->textContent);
                    }
                    $item['content'] = $items;
                    break;
                    
                case 'table':
                    $item['content'] = $this->extractTable($child);
                    break;
                    
                case 'div':
                    if (strpos($child->getAttribute('class'), 'code-example') !== false) {
                        $codeNode = $child->getElementsByTagName('code')->item(0);
                        if ($codeNode) {
                            $item['type'] = 'code';
                            $item['content'] = trim($codeNode->textContent);
                            $item['language'] = $this->extractLanguage($codeNode->getAttribute('class'));
                        }
                    } elseif (preg_match('/(info|warning|success)-box/', $child->getAttribute('class'), $matches)) {
                        $item['type'] = $matches[1] . '-box';
                        $item['content'] = trim($child->textContent);
                    }
                    break;
            }
            
            if (!empty($item['content'])) {
                $content[] = $item;
            }
        }
        
        return $content;
    }
    
    private function extractTable($tableNode)
    {
        $table = [
            'headers' => [],
            'rows' => []
        ];
        
        $headers = $tableNode->getElementsByTagName('th');
        foreach ($headers as $header) {
            $table['headers'][] = trim($header->textContent);
        }
        
        $trs = $tableNode->getElementsByTagName('tr');
        foreach ($trs as $tr) {
            $row = [];
            $tds = $tr->getElementsByTagName('td');
            if ($tds->length > 0) {
                foreach ($tds as $td) {
                    $row[] = trim($td->textContent);
                }
                $table['rows'][] = $row;
            }
        }
        
        return $table;
    }
    
    private function extractLanguage($classString)
    {
        if (preg_match('/language-(\w+)/', $classString, $matches)) {
            return $matches[1];
        }
        return 'text';
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
    
    private function addCoverPage($pdf, $title, $description, $color)
    {
        list($r, $g, $b) = $this->hexToRgb($color);
        
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Rect(0, 0, 210, 100, 'F');
        
        $pdf->SetY(40);
        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->MultiCell(0, 10, $title, 0, 'C', 0, 1, '', '', true, 0, false, true, 0);
        
        if ($description) {
            $pdf->SetY(60);
            $pdf->SetFont('helvetica', '', 14);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->MultiCell(0, 8, $description, 0, 'C', 0, 1, '', '', true, 0, false, true, 0);
        }
        
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->SetY(120);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Comprehensive Answers for Interview Preparation', 0, true, 'C');
        
        $pdf->SetY(140);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 10, 'Generated on ' . date('F j, Y'), 0, true, 'C');
        
        $pdf->SetY(160);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 6, "This document contains detailed answers to common interview questions with code examples and best practices. Perfect for offline study and review.", 0, 'C');
    }
    
    private function addTableOfContents($pdf, $items, $color)
    {
        list($r, $g, $b) = $this->hexToRgb($color);
        
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->Cell(0, 10, 'Table of Contents', 0, true, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        
        foreach ($items as $index => $item) {
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(10, 8, ($index + 1) . '.', 0, 0, 'L');
            $pdf->Cell(0, 8, $item['text'], 0, true, 'L');
        }
    }
    
    private function addSection($pdf, $section, $color)
    {
        list($r, $g, $b) = $this->hexToRgb($color);
        
        foreach ($section['content'] as $item) {
            switch ($item['type']) {
                case 'h2':
                    $pdf->SetFont('helvetica', 'B', 18);
                    $pdf->SetTextColor($r, $g, $b);
                    $pdf->MultiCell(0, 10, $item['content'], 0, 'L', 0, 1, '', '', true);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->Ln(3);
                    break;
                    
                case 'h3':
                    $pdf->SetFont('helvetica', 'B', 14);
                    $pdf->SetTextColor($r, $g, $b);
                    $pdf->MultiCell(0, 8, $item['content'], 0, 'L', 0, 1, '', '', true);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->Ln(2);
                    break;
                    
                case 'h4':
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->MultiCell(0, 7, $item['content'], 0, 'L', 0, 1, '', '', true);
                    $pdf->Ln(1);
                    break;
                    
                case 'p':
                    $pdf->SetFont('helvetica', '', 10);
                    $pdf->MultiCell(0, 6, $item['content'], 0, 'L', 0, 1, '', '', true);
                    $pdf->Ln(2);
                    break;
                    
                case 'ul':
                case 'ol':
                    $pdf->SetFont('helvetica', '', 10);
                    foreach ($item['content'] as $idx => $listItem) {
                        $bullet = $item['type'] === 'ul' ? '•' : ($idx + 1) . '.';
                        $pdf->Cell(10, 6, $bullet, 0, 0, 'L');
                        $pdf->MultiCell(0, 6, $listItem, 0, 'L', 0, 1, '', '', true);
                    }
                    $pdf->Ln(2);
                    break;
                    
                case 'code':
                    $this->addCodeBlock($pdf, $item['content'], $item['language']);
                    break;
                    
                case 'table':
                    $this->addTable($pdf, $item['content'], $color);
                    break;
                    
                case 'info-box':
                    $this->addInfoBox($pdf, $item['content'], 'info');
                    break;
                    
                case 'warning-box':
                    $this->addInfoBox($pdf, $item['content'], 'warning');
                    break;
                    
                case 'success-box':
                    $this->addInfoBox($pdf, $item['content'], 'success');
                    break;
            }
        }
    }
    
    private function addCodeBlock($pdf, $code, $language = 'text')
    {
        $pdf->SetFillColor(40, 44, 52);
        $pdf->SetTextColor(171, 178, 191);
        $pdf->SetFont('courier', '', 8);
        
        $lines = explode("\n", $code);
        $lineHeight = 4;
        
        $pdf->Ln(2);
        
        foreach ($lines as $line) {
            if (strlen($line) > 100) {
                $chunks = str_split($line, 100);
                foreach ($chunks as $chunk) {
                    $pdf->Cell(0, $lineHeight, $chunk, 0, true, 'L', 1);
                }
            } else {
                $pdf->Cell(0, $lineHeight, $line, 0, true, 'L', 1);
            }
        }
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Ln(3);
    }
    
    private function addTable($pdf, $tableData, $color)
    {
        if (empty($tableData['headers']) || empty($tableData['rows'])) {
            return;
        }
        
        list($r, $g, $b) = $this->hexToRgb($color);
        
        $pdf->Ln(2);
        
        $numCols = count($tableData['headers']);
        $colWidth = 180 / $numCols;
        
        $pdf->SetFillColor($r, $g, $b);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        
        foreach ($tableData['headers'] as $header) {
            $pdf->Cell($colWidth, 7, $header, 1, 0, 'L', 1);
        }
        $pdf->Ln();
        
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
        
        $fill = false;
        foreach ($tableData['rows'] as $row) {
            foreach ($row as $cell) {
                $pdf->Cell($colWidth, 6, $cell, 1, 0, 'L', $fill);
            }
            $pdf->Ln();
            $fill = !$fill;
        }
        
        $pdf->Ln(3);
    }
    
    private function addInfoBox($pdf, $content, $type = 'info')
    {
        $colors = [
            'info' => ['bg' => [227, 242, 253], 'border' => [33, 150, 243]],
            'warning' => ['bg' => [255, 243, 224], 'border' => [255, 152, 0]],
            'success' => ['bg' => [232, 245, 233], 'border' => [76, 175, 80]]
        ];
        
        $color = $colors[$type] ?? $colors['info'];
        
        $pdf->Ln(2);
        
        $pdf->SetFillColor($color['bg'][0], $color['bg'][1], $color['bg'][2]);
        $pdf->SetDrawColor($color['border'][0], $color['border'][1], $color['border'][2]);
        $pdf->SetLineWidth(0.5);
        
        $pdf->SetFont('helvetica', '', 9);
        
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        
        $pdf->MultiCell(0, 6, $content, 0, 'L', 1, 1, '', '', true, 0, false, true, 0, 'T');
        
        $height = $pdf->GetY() - $y;
        $pdf->Line($x, $y, $x, $y + $height);
        
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(2);
    }
    
    private function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return [$r, $g, $b];
    }
}

function showUsage()
{
    echo "PDF Answer Generator for Interview Bank\n";
    echo "========================================\n\n";
    echo "Usage:\n";
    echo "  php automation/generate-pdf-answers.php [category]\n\n";
    echo "Examples:\n";
    echo "  php automation/generate-pdf-answers.php                    # Generate all PDFs\n";
    echo "  php automation/generate-pdf-answers.php php-core-answers   # Generate specific PDF\n";
    echo "  php automation/generate-pdf-answers.php laravel-framework-answers\n\n";
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
    echo "Please run: composer install\n";
    exit(1);
}

$category = $argv[1] ?? null;

if (isset($argv[1]) && ($argv[1] === '--help' || $argv[1] === '-h')) {
    showUsage();
    exit(0);
}

try {
    $exporter = new AnswerPDFExporter();
    
    if ($category) {
        echo "Generating PDF for category: $category\n\n";
        $exporter->generatePDF($category);
    } else {
        echo "Generating all PDFs...\n\n";
        $exporter->generateAll();
    }
    
    echo "\n✓ PDF generation completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
