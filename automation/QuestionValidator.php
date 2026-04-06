<?php

namespace InterviewBank\Automation;

class QuestionValidator
{
    private array $errors = [];

    private array $warnings = [];

    private array $stats = [];

    public function validateMarkdownFile(string $filePath): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->stats = [
            'total_lines' => 0,
            'code_blocks' => 0,
            'mermaid_diagrams' => 0,
            'questions' => 0,
            'headers' => 0,
        ];

        if (! file_exists($filePath)) {
            $this->errors[] = "File not found: {$filePath}";

            return $this->getResults();
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $this->stats['total_lines'] = count($lines);

        $this->validateCodeBlocks($content, $filePath);
        $this->validateMermaidDiagrams($content, $filePath);
        $this->validateMarkdownStructure($lines, $filePath);
        $this->validateQuestionFormat($content, $filePath);

        return $this->getResults();
    }

    private function validateCodeBlocks(string $content, string $filePath): void
    {
        preg_match_all('/```(\w+)?\n(.*?)```/s', $content, $matches, PREG_OFFSET_CAPTURE);

        $this->stats['code_blocks'] = count($matches[0]);

        foreach ($matches[0] as $index => $match) {
            $language = $matches[1][$index][0] ?? '';
            $code = $matches[2][$index][0];
            $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;

            if (empty($language)) {
                $this->warnings[] = "Code block without language specification at line {$lineNumber} in {$filePath}";
            }

            if (trim($code) === '') {
                $this->errors[] = "Empty code block at line {$lineNumber} in {$filePath}";
            }

            $this->validateCodeSyntax($language, $code, $lineNumber, $filePath);
        }
    }

    private function validateCodeSyntax(string $language, string $code, int $lineNumber, string $filePath): void
    {
        switch (strtolower($language)) {
            case 'php':
                $this->validatePhpSyntax($code, $lineNumber, $filePath);
                break;
            case 'json':
                $this->validateJsonSyntax($code, $lineNumber, $filePath);
                break;
            case 'yaml':
            case 'yml':
                $this->validateYamlSyntax($code, $lineNumber, $filePath);
                break;
            case 'javascript':
            case 'js':
            case 'typescript':
            case 'ts':
                $this->validateJsSyntax($code, $lineNumber, $filePath);
                break;
            case 'sql':
                $this->validateSqlSyntax($code, $lineNumber, $filePath);
                break;
        }
    }

    private function validatePhpSyntax(string $code, int $lineNumber, string $filePath): void
    {
        if (! str_contains($code, '<?php') && ! str_contains($code, '<?=')) {
            $code = "<?php\n".$code;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'php_validate_');
        file_put_contents($tempFile, $code);

        exec('php -l '.escapeshellarg($tempFile).' 2>&1', $output, $returnCode);
        unlink($tempFile);

        if ($returnCode !== 0) {
            $errorMsg = implode(' ', $output);
            $this->errors[] = "PHP syntax error at line {$lineNumber} in {$filePath}: {$errorMsg}";
        }
    }

    private function validateJsonSyntax(string $code, int $lineNumber, string $filePath): void
    {
        json_decode($code);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            $this->errors[] = "JSON syntax error at line {$lineNumber} in {$filePath}: {$error}";
        }
    }

    private function validateYamlSyntax(string $code, int $lineNumber, string $filePath): void
    {
        if (! function_exists('yaml_parse')) {
            $this->warnings[] = "YAML extension not available, skipping validation at line {$lineNumber}";

            return;
        }

        $result = @yaml_parse($code);

        if ($result === false) {
            $this->errors[] = "YAML syntax error at line {$lineNumber} in {$filePath}";
        }
    }

    private function validateJsSyntax(string $code, int $lineNumber, string $filePath): void
    {
        $commonErrors = [
            '/\bfunction\s+\w+\s*\([^)]*\)\s*{[^}]*$/' => 'Unclosed function block',
            '/\bclass\s+\w+\s*{[^}]*$/' => 'Unclosed class block',
            '/\bif\s*\([^)]*\)\s*{[^}]*$/' => 'Unclosed if block',
        ];

        foreach ($commonErrors as $pattern => $message) {
            if (preg_match($pattern, $code)) {
                $this->warnings[] = "Potential {$message} at line {$lineNumber} in {$filePath}";
            }
        }
    }

    private function validateSqlSyntax(string $code, int $lineNumber, string $filePath): void
    {
        $keywords = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'ALTER', 'DROP'];
        $hasKeyword = false;

        foreach ($keywords as $keyword) {
            if (stripos($code, $keyword) !== false) {
                $hasKeyword = true;
                break;
            }
        }

        if (! $hasKeyword) {
            $this->warnings[] = "SQL code block without recognized keywords at line {$lineNumber} in {$filePath}";
        }
    }

    private function validateMermaidDiagrams(string $content, string $filePath): void
    {
        preg_match_all('/```mermaid\n(.*?)```/s', $content, $matches, PREG_OFFSET_CAPTURE);

        $this->stats['mermaid_diagrams'] = count($matches[0]);

        foreach ($matches[0] as $index => $match) {
            $diagram = $matches[1][$index][0];
            $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;

            $this->validateMermaidSyntax($diagram, $lineNumber, $filePath);
        }
    }

    private function validateMermaidSyntax(string $diagram, int $lineNumber, string $filePath): void
    {
        $diagram = trim($diagram);

        if (empty($diagram)) {
            $this->errors[] = "Empty Mermaid diagram at line {$lineNumber} in {$filePath}";

            return;
        }

        $validTypes = [
            'graph', 'flowchart', 'sequenceDiagram', 'classDiagram',
            'stateDiagram', 'erDiagram', 'gantt', 'pie', 'gitGraph',
            'journey', 'quadrantChart', 'requirementDiagram', 'C4Context',
        ];

        $firstLine = strtok($diagram, "\n");
        $hasValidType = false;

        foreach ($validTypes as $type) {
            if (stripos($firstLine, $type) !== false) {
                $hasValidType = true;
                break;
            }
        }

        if (! $hasValidType) {
            $this->errors[] = "Invalid Mermaid diagram type at line {$lineNumber} in {$filePath}. First line: {$firstLine}";
        }

        if (preg_match('/\[.*\]/', $diagram) && ! preg_match('/-->|---/', $diagram)) {
            $this->warnings[] = "Mermaid diagram may have nodes without connections at line {$lineNumber} in {$filePath}";
        }

        $openBrackets = substr_count($diagram, '[');
        $closeBrackets = substr_count($diagram, ']');
        if ($openBrackets !== $closeBrackets) {
            $this->errors[] = "Mismatched brackets in Mermaid diagram at line {$lineNumber} in {$filePath}";
        }

        $openParens = substr_count($diagram, '(');
        $closeParens = substr_count($diagram, ')');
        if ($openParens !== $closeParens) {
            $this->errors[] = "Mismatched parentheses in Mermaid diagram at line {$lineNumber} in {$filePath}";
        }
    }

    private function validateMarkdownStructure(array $lines, string $filePath): void
    {
        $currentHeader = null;
        $headerHierarchy = [];

        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]);
                $title = trim($matches[2]);
                $this->stats['headers']++;

                if (empty($title)) {
                    $this->errors[] = 'Empty header at line '.($lineNumber + 1)." in {$filePath}";
                }

                if ($level > 1 && empty($headerHierarchy)) {
                    $this->warnings[] = "Document starts with h{$level} instead of h1 at line ".($lineNumber + 1)." in {$filePath}";
                }

                if (! empty($headerHierarchy)) {
                    $lastLevel = end($headerHierarchy);
                    if ($level > $lastLevel + 1) {
                        $this->warnings[] = "Header hierarchy skip from h{$lastLevel} to h{$level} at line ".($lineNumber + 1)." in {$filePath}";
                    }
                }

                $headerHierarchy[$level] = $level;
                $headerHierarchy = array_slice($headerHierarchy, 0, $level, true);
            }

            if (preg_match('/\[([^\]]+)\]\(([^)]+)\)/', $line, $matches)) {
                $linkText = $matches[1];
                $linkUrl = $matches[2];

                if (str_starts_with($linkUrl, '#')) {
                    continue;
                }

                if (str_starts_with($linkUrl, 'http://') || str_starts_with($linkUrl, 'https://')) {
                    continue;
                }

                $basePath = dirname($filePath);
                $linkedPath = realpath($basePath.'/'.$linkUrl);

                if ($linkedPath === false || ! file_exists($linkedPath)) {
                    $this->warnings[] = "Broken link to '{$linkUrl}' at line ".($lineNumber + 1)." in {$filePath}";
                }
            }
        }
    }

    private function validateQuestionFormat(string $content, string $filePath): void
    {
        preg_match_all('/^(\d+)\.\s+(.+)$/m', $content, $matches);
        $this->stats['questions'] = count($matches[0]);

        $questionNumbers = $matches[1];
        for ($i = 0; $i < count($questionNumbers) - 1; $i++) {
            $current = (int) $questionNumbers[$i];
            $next = (int) $questionNumbers[$i + 1];

            if ($next !== $current + 1) {
                $this->warnings[] = "Question numbering gap: {$current} followed by {$next} in {$filePath}";
            }
        }

        preg_match_all('/\*\*Q:\*\*/', $content, $qMatches);
        preg_match_all('/\*\*A:\*\*/', $content, $aMatches);

        if (count($qMatches[0]) !== count($aMatches[0])) {
            $this->errors[] = "Mismatched Q&A pairs in {$filePath}: ".count($qMatches[0]).' questions, '.count($aMatches[0]).' answers';
        }
    }

    private function getResults(): array
    {
        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'stats' => $this->stats,
            'valid' => empty($this->errors),
        ];
    }

    public function validateDirectory(string $directory): array
    {
        $results = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $filePath = $file->getPathname();
                $results[$filePath] = $this->validateMarkdownFile($filePath);
            }
        }

        return $results;
    }

    public function generateReport(array $results): string
    {
        $totalFiles = count($results);
        $validFiles = count(array_filter($results, fn ($r) => $r['valid']));
        $totalErrors = array_sum(array_map(fn ($r) => count($r['errors']), $results));
        $totalWarnings = array_sum(array_map(fn ($r) => count($r['warnings']), $results));

        $report = "# Validation Report\n\n";
        $report .= 'Generated: '.date('Y-m-d H:i:s')."\n\n";
        $report .= "## Summary\n\n";
        $report .= "- Total files: {$totalFiles}\n";
        $report .= "- Valid files: {$validFiles}\n";
        $report .= '- Files with errors: '.($totalFiles - $validFiles)."\n";
        $report .= "- Total errors: {$totalErrors}\n";
        $report .= "- Total warnings: {$totalWarnings}\n\n";

        $report .= "## Statistics\n\n";
        $totalStats = [
            'total_lines' => 0,
            'code_blocks' => 0,
            'mermaid_diagrams' => 0,
            'questions' => 0,
            'headers' => 0,
        ];

        foreach ($results as $result) {
            foreach ($totalStats as $key => $value) {
                $totalStats[$key] += $result['stats'][$key] ?? 0;
            }
        }

        $report .= "- Total lines: {$totalStats['total_lines']}\n";
        $report .= "- Total code blocks: {$totalStats['code_blocks']}\n";
        $report .= "- Total Mermaid diagrams: {$totalStats['mermaid_diagrams']}\n";
        $report .= "- Total questions: {$totalStats['questions']}\n";
        $report .= "- Total headers: {$totalStats['headers']}\n\n";

        $report .= "## Detailed Results\n\n";

        foreach ($results as $filePath => $result) {
            $status = $result['valid'] ? '✅' : '❌';
            $errorCount = count($result['errors']);
            $warningCount = count($result['warnings']);

            $report .= "### {$status} ".basename($filePath)."\n\n";

            if ($errorCount > 0) {
                $report .= "**Errors ({$errorCount}):**\n\n";
                foreach ($result['errors'] as $error) {
                    $report .= "- {$error}\n";
                }
                $report .= "\n";
            }

            if ($warningCount > 0) {
                $report .= "**Warnings ({$warningCount}):**\n\n";
                foreach ($result['warnings'] as $warning) {
                    $report .= "- {$warning}\n";
                }
                $report .= "\n";
            }
        }

        return $report;
    }
}
