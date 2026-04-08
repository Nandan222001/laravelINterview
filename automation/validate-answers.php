<?php
/**
 * Answer Quality Validator
 * 
 * Validates answer files to ensure they meet quality standards:
 * - Required sections: Overview, Core Concepts, Code Examples, Best Practices, Common Pitfalls
 * - Code blocks have proper language tags for syntax highlighting
 * - Internal links are valid and point to existing sections
 */

class AnswerValidator
{
    private array $errors = [];
    private array $warnings = [];
    private array $stats = [];
    
    // Required sections that each answer should contain
    private array $requiredSections = [
        'Overview',
        'Core Concepts',
        'Code Examples',
        'Best Practices',
        'Common Pitfalls'
    ];
    
    /**
     * Validate a single answer file
     */
    public function validateFile(string $filePath): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->stats = [];
        
        if (!file_exists($filePath)) {
            $this->errors[] = "File does not exist: {$filePath}";
            return $this->getResults();
        }
        
        $content = file_get_contents($filePath);
        $fileName = basename($filePath);
        
        echo "Validating: {$fileName}\n";
        
        // Validate required sections
        $this->validateRequiredSections($content, $fileName);
        
        // Validate code blocks have language tags
        $this->validateCodeBlocks($content, $fileName);
        
        // Validate internal links
        $this->validateInternalLinks($content, $fileName);
        
        // Collect statistics
        $this->collectStatistics($content, $fileName);
        
        return $this->getResults();
    }
    
    /**
     * Validate all answer files in a directory
     */
    public function validateDirectory(string $dirPath): array
    {
        $allResults = [];
        
        if (!is_dir($dirPath)) {
            return [
                'error' => "Directory does not exist: {$dirPath}",
                'results' => []
            ];
        }
        
        $files = glob($dirPath . '/*.html');
        
        if (empty($files)) {
            return [
                'error' => "No HTML files found in: {$dirPath}",
                'results' => []
            ];
        }
        
        echo "Found " . count($files) . " answer files to validate\n";
        echo str_repeat('=', 70) . "\n\n";
        
        foreach ($files as $file) {
            $result = $this->validateFile($file);
            $allResults[basename($file)] = $result;
            echo "\n";
        }
        
        return $allResults;
    }
    
    /**
     * Check if all required sections are present
     */
    private function validateRequiredSections(string $content, string $fileName): void
    {
        $foundSections = [];
        $missingSections = [];
        
        foreach ($this->requiredSections as $section) {
            // Look for section as h3 heading
            $pattern = '/<h3[^>]*>(?:.*?)?(?:\s*' . preg_quote($section, '/') . ')(?:\s*)?(?:.*?)?<\/h3>/is';
            
            if (preg_match($pattern, $content)) {
                $foundSections[] = $section;
            } else {
                $missingSections[] = $section;
            }
        }
        
        if (!empty($missingSections)) {
            foreach ($missingSections as $section) {
                $this->warnings[] = "Missing recommended section: '{$section}'";
            }
        }
        
        $this->stats['required_sections'] = [
            'total' => count($this->requiredSections),
            'found' => count($foundSections),
            'missing' => $missingSections
        ];
    }
    
    /**
     * Validate that code blocks have proper language tags
     */
    private function validateCodeBlocks(string $content, string $fileName): void
    {
        // Match all code blocks
        preg_match_all('/<pre[^>]*>.*?<code(?:\s+class="([^"]*)")?[^>]*>/is', $content, $matches, PREG_OFFSET_CAPTURE);
        
        $totalCodeBlocks = count($matches[0]);
        $blocksWithLanguage = 0;
        $blocksWithoutLanguage = [];
        
        foreach ($matches[0] as $index => $match) {
            $fullMatch = $match[0];
            $classAttr = $matches[1][$index][0] ?? '';
            
            if (!empty($classAttr) && preg_match('/language-\w+/', $classAttr)) {
                $blocksWithLanguage++;
            } else {
                // Find line number for better error reporting
                $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $blocksWithoutLanguage[] = "Line ~{$lineNumber}";
            }
        }
        
        if (!empty($blocksWithoutLanguage)) {
            $this->errors[] = "Found " . count($blocksWithoutLanguage) . " code block(s) without language tags at: " . 
                             implode(', ', array_slice($blocksWithoutLanguage, 0, 5)) . 
                             (count($blocksWithoutLanguage) > 5 ? '...' : '');
        }
        
        $this->stats['code_blocks'] = [
            'total' => $totalCodeBlocks,
            'with_language' => $blocksWithLanguage,
            'without_language' => count($blocksWithoutLanguage)
        ];
    }
    
    /**
     * Validate internal links point to existing sections
     */
    private function validateInternalLinks(string $content, string $fileName): void
    {
        // Extract all internal links (href="#...")
        preg_match_all('/<a\s+[^>]*href="#([^"]+)"[^>]*>/i', $content, $linkMatches);
        $internalLinks = $linkMatches[1];
        
        // Extract all IDs from elements
        preg_match_all('/\s+id="([^"]+)"/i', $content, $idMatches);
        $availableIds = $idMatches[1];
        
        $brokenLinks = [];
        
        foreach ($internalLinks as $link) {
            if (!in_array($link, $availableIds)) {
                $brokenLinks[] = $link;
            }
        }
        
        if (!empty($brokenLinks)) {
            $uniqueBrokenLinks = array_unique($brokenLinks);
            $this->errors[] = "Found " . count($uniqueBrokenLinks) . " broken internal link(s): #" . 
                             implode(', #', array_slice($uniqueBrokenLinks, 0, 5)) . 
                             (count($uniqueBrokenLinks) > 5 ? '...' : '');
        }
        
        $this->stats['internal_links'] = [
            'total' => count($internalLinks),
            'valid' => count($internalLinks) - count(array_unique($brokenLinks)),
            'broken' => count(array_unique($brokenLinks))
        ];
    }
    
    /**
     * Collect general statistics about the answer file
     */
    private function collectStatistics(string $content, string $fileName): void
    {
        // Count main sections (h2)
        preg_match_all('/<h2[^>]*>.*?<\/h2>/is', $content, $h2Matches);
        $this->stats['main_sections'] = count($h2Matches[0]);
        
        // Count subsections (h3)
        preg_match_all('/<h3[^>]*>.*?<\/h3>/is', $content, $h3Matches);
        $this->stats['subsections'] = count($h3Matches[0]);
        
        // Count info boxes
        preg_match_all('/<div\s+class="[^"]*(?:info-box|warning-box|success-box)[^"]*"[^>]*>/i', $content, $boxMatches);
        $this->stats['info_boxes'] = count($boxMatches[0]);
        
        // Count tables
        preg_match_all('/<table[^>]*>/i', $content, $tableMatches);
        $this->stats['tables'] = count($tableMatches[0]);
        
        // Estimate content size
        $this->stats['file_size'] = $this->formatBytes(strlen($content));
        
        // Check for table of contents
        $this->stats['has_toc'] = preg_match('/<div\s+class="toc"[^>]*>/i', $content) ? 'Yes' : 'No';
    }
    
    /**
     * Get validation results
     */
    private function getResults(): array
    {
        $hasErrors = !empty($this->errors);
        $hasWarnings = !empty($this->warnings);
        
        // Print results
        if ($hasErrors) {
            echo "  ❌ ERRORS:\n";
            foreach ($this->errors as $error) {
                echo "     - {$error}\n";
            }
        }
        
        if ($hasWarnings) {
            echo "  ⚠️  WARNINGS:\n";
            foreach ($this->warnings as $warning) {
                echo "     - {$warning}\n";
            }
        }
        
        if (!$hasErrors && !$hasWarnings) {
            echo "  ✅ All validations passed!\n";
        }
        
        // Print statistics
        echo "  📊 STATISTICS:\n";
        echo "     - Main sections: {$this->stats['main_sections']}\n";
        echo "     - Subsections: {$this->stats['subsections']}\n";
        echo "     - Code blocks: {$this->stats['code_blocks']['total']} " .
             "({$this->stats['code_blocks']['with_language']} with language tags)\n";
        echo "     - Internal links: {$this->stats['internal_links']['total']} " .
             "({$this->stats['internal_links']['valid']} valid, {$this->stats['internal_links']['broken']} broken)\n";
        echo "     - Info boxes: {$this->stats['info_boxes']}\n";
        echo "     - Tables: {$this->stats['tables']}\n";
        echo "     - File size: {$this->stats['file_size']}\n";
        echo "     - Has TOC: {$this->stats['has_toc']}\n";
        
        return [
            'valid' => !$hasErrors,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'stats' => $this->stats
        ];
    }
    
    /**
     * Generate summary report for all validations
     */
    public function generateSummaryReport(array $allResults): void
    {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "VALIDATION SUMMARY\n";
        echo str_repeat('=', 70) . "\n\n";
        
        $totalFiles = count($allResults);
        $validFiles = 0;
        $filesWithErrors = 0;
        $filesWithWarnings = 0;
        $totalErrors = 0;
        $totalWarnings = 0;
        
        foreach ($allResults as $fileName => $result) {
            if ($result['valid']) {
                $validFiles++;
            } else {
                $filesWithErrors++;
            }
            
            $totalErrors += count($result['errors']);
            $totalWarnings += count($result['warnings']);
            
            if (!empty($result['warnings'])) {
                $filesWithWarnings++;
            }
        }
        
        echo "Total files validated: {$totalFiles}\n";
        echo "✅ Valid files: {$validFiles}\n";
        echo "❌ Files with errors: {$filesWithErrors}\n";
        echo "⚠️  Files with warnings: {$filesWithWarnings}\n";
        echo "\nTotal errors: {$totalErrors}\n";
        echo "Total warnings: {$totalWarnings}\n";
        
        // Quality score
        $qualityScore = $totalFiles > 0 ? round(($validFiles / $totalFiles) * 100, 2) : 0;
        echo "\n📈 Quality Score: {$qualityScore}%\n";
        
        // List files with issues
        if ($filesWithErrors > 0) {
            echo "\n❌ Files with errors:\n";
            foreach ($allResults as $fileName => $result) {
                if (!$result['valid']) {
                    echo "   - {$fileName} (" . count($result['errors']) . " error(s))\n";
                }
            }
        }
        
        echo "\n" . str_repeat('=', 70) . "\n";
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}

// CLI Usage
if (php_sapi_name() === 'cli') {
    $validator = new AnswerValidator();
    
    // Get command line arguments
    $options = getopt('', ['file:', 'dir:', 'help']);
    
    if (isset($options['help']) || (empty($options['file']) && empty($options['dir']))) {
        echo "Answer Quality Validator\n";
        echo "========================\n\n";
        echo "Usage:\n";
        echo "  php validate-answers.php --file=<path>   Validate a single file\n";
        echo "  php validate-answers.php --dir=<path>    Validate all files in directory\n";
        echo "  php validate-answers.php --help          Show this help message\n\n";
        echo "Examples:\n";
        echo "  php validate-answers.php --file=../answers/laravel-framework-answers.html\n";
        echo "  php validate-answers.php --dir=../answers\n\n";
        exit(0);
    }
    
    if (isset($options['file'])) {
        // Validate single file
        $filePath = $options['file'];
        $result = $validator->validateFile($filePath);
        
        echo "\n" . str_repeat('=', 70) . "\n";
        exit($result['valid'] ? 0 : 1);
    }
    
    if (isset($options['dir'])) {
        // Validate directory
        $dirPath = $options['dir'];
        $results = $validator->validateDirectory($dirPath);
        
        if (isset($results['error'])) {
            echo "Error: {$results['error']}\n";
            exit(1);
        }
        
        $validator->generateSummaryReport($results);
        
        // Exit with error code if any file has errors
        $hasErrors = false;
        foreach ($results as $result) {
            if (!$result['valid']) {
                $hasErrors = true;
                break;
            }
        }
        
        exit($hasErrors ? 1 : 0);
    }
}
