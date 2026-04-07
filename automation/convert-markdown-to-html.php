<?php
/**
 * Markdown to HTML Converter for Interview Bank Answers
 * 
 * Converts markdown answer files from interview-bank/ subdirectories to HTML format
 * following the existing answer page template structure with syntax highlighting,
 * navigation, and table of contents.
 * 
 * Usage: php automation/convert-markdown-to-html.php [category-name]
 * Example: php automation/convert-markdown-to-html.php php-laravel-api-security
 */

class MarkdownToHtmlConverter
{
    private string $baseDir;
    private string $interviewBankDir;
    private string $answersDir;
    private array $config;
    
    public function __construct()
    {
        $this->baseDir = dirname(__DIR__);
        $this->interviewBankDir = $this->baseDir . '/interview-bank';
        $this->answersDir = $this->baseDir . '/answers';
        
        $this->config = [
            'highlight_js_version' => '11.9.0',
            'highlight_theme' => 'github-dark',
            'code_languages' => ['php', 'javascript', 'python', 'bash', 'sql', 'yaml', 'json', 'typescript'],
        ];
    }
    
    /**
     * Convert all markdown files in a category to HTML
     */
    public function convertCategory(string $categoryName): void
    {
        $categoryPath = $this->interviewBankDir . '/' . $categoryName;
        
        if (!is_dir($categoryPath)) {
            echo "Error: Category '$categoryName' not found in interview-bank/\n";
            return;
        }
        
        // Find all markdown answer files
        $markdownFiles = $this->findAnswerFiles($categoryPath);
        
        if (empty($markdownFiles)) {
            echo "No markdown answer files found in '$categoryName'\n";
            return;
        }
        
        echo "Found " . count($markdownFiles) . " markdown file(s) in '$categoryName'\n";
        
        foreach ($markdownFiles as $file) {
            $this->convertFile($file, $categoryName);
        }
    }
    
    /**
     * Convert all categories
     */
    public function convertAll(): void
    {
        $categories = $this->getCategories();
        
        echo "Converting " . count($categories) . " categories...\n\n";
        
        foreach ($categories as $category) {
            echo "Processing category: $category\n";
            $this->convertCategory($category);
            echo "\n";
        }
        
        echo "Conversion complete!\n";
    }
    
    /**
     * Find all answer markdown files in a directory
     */
    private function findAnswerFiles(string $path): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $filename = $file->getFilename();
                // Look for files with "answer" or "answers" in the name
                if (preg_match('/answer[s]?.*\.md$/i', $filename)) {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Get all category directories
     */
    public function getCategories(): array
    {
        $categories = [];
        $dirs = scandir($this->interviewBankDir);
        
        foreach ($dirs as $dir) {
            if ($dir[0] !== '.' && is_dir($this->interviewBankDir . '/' . $dir)) {
                $categories[] = $dir;
            }
        }
        
        return $categories;
    }
    
    /**
     * Convert a single markdown file to HTML
     */
    private function convertFile(string $filePath, string $categoryName): void
    {
        $content = file_get_contents($filePath);
        $filename = basename($filePath, '.md');
        
        echo "  Converting: $filename.md\n";
        
        // Parse markdown content
        $parsed = $this->parseMarkdown($content);
        
        // Generate HTML
        $html = $this->generateHtml(
            $parsed['content'],
            $parsed['title'],
            $parsed['toc'],
            $categoryName,
            $filename
        );
        
        // Write to answers directory
        $outputFile = $this->answersDir . '/' . $this->sanitizeFilename($categoryName) . '-' . $filename . '.html';
        file_put_contents($outputFile, $html);
        
        echo "  Created: " . basename($outputFile) . "\n";
    }
    
    /**
     * Parse markdown content into structured data
     */
    private function parseMarkdown(string $content): array
    {
        $lines = explode("\n", $content);
        $title = $this->extractTitle($lines);
        $toc = $this->buildTableOfContents($lines);
        $htmlContent = $this->convertMarkdownToHtml($content);
        
        return [
            'title' => $title,
            'toc' => $toc,
            'content' => $htmlContent
        ];
    }
    
    /**
     * Extract title from markdown
     */
    private function extractTitle(array $lines): string
    {
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                return $matches[1];
            }
        }
        
        return 'Interview Answers';
    }
    
    /**
     * Build table of contents from markdown headings
     */
    private function buildTableOfContents(array $lines): array
    {
        $toc = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Match h2 headings (##)
            if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                $heading = $matches[1];
                $id = $this->generateId($heading);
                
                $toc[] = [
                    'text' => $heading,
                    'id' => $id,
                    'level' => 2
                ];
            }
        }
        
        return $toc;
    }
    
    /**
     * Convert markdown content to HTML
     */
    private function convertMarkdownToHtml(string $markdown): string
    {
        $html = '';
        $lines = explode("\n", $markdown);
        $inCodeBlock = false;
        $codeLanguage = '';
        $codeContent = '';
        $inList = false;
        $listItems = [];
        $currentSection = null;
        
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmed = trim($line);
            
            // Handle code blocks
            if (preg_match('/^```(\w+)?/', $trimmed, $matches)) {
                if (!$inCodeBlock) {
                    $inCodeBlock = true;
                    $codeLanguage = $matches[1] ?? 'plaintext';
                    $codeContent = '';
                } else {
                    $html .= $this->createCodeBlock($codeContent, $codeLanguage);
                    $inCodeBlock = false;
                    $codeLanguage = '';
                    $codeContent = '';
                }
                continue;
            }
            
            if ($inCodeBlock) {
                $codeContent .= $line . "\n";
                continue;
            }
            
            // Handle headings
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $matches)) {
                // Close any open list
                if ($inList) {
                    $html .= $this->createList($listItems);
                    $inList = false;
                    $listItems = [];
                }
                
                $level = strlen($matches[1]);
                $text = $matches[2];
                $id = $this->generateId($text);
                
                // Close previous section if starting h2
                if ($level === 2 && $currentSection !== null) {
                    $html .= '</section>' . "\n\n";
                }
                
                // Start new section for h2
                if ($level === 2) {
                    $html .= '<section id="' . $id . '" class="topic-section">' . "\n";
                    $currentSection = $id;
                }
                
                $html .= '<h' . $level . ($level === 2 ? '' : ' id="' . $id . '"') . '>' 
                       . htmlspecialchars($text) . '</h' . $level . '>' . "\n";
                continue;
            }
            
            // Handle lists
            if (preg_match('/^[\*\-\+]\s+(.+)$/', $trimmed, $matches) || 
                preg_match('/^\d+\.\s+(.+)$/', $trimmed, $matches)) {
                $inList = true;
                $listItems[] = $matches[1];
                continue;
            } else if ($inList && !empty($trimmed)) {
                // Continue list item on next line
                if (!empty($listItems)) {
                    $listItems[count($listItems) - 1] .= ' ' . $trimmed;
                }
                continue;
            } else if ($inList && empty($trimmed)) {
                // End of list
                $html .= $this->createList($listItems);
                $inList = false;
                $listItems = [];
                continue;
            }
            
            // Handle tables
            if (strpos($trimmed, '|') !== false && $this->isTableRow($trimmed)) {
                $tableHtml = $this->parseTable($lines, $i);
                $html .= $tableHtml['html'];
                $i = $tableHtml['nextIndex'] - 1; // -1 because loop will increment
                continue;
            }
            
            // Handle blockquotes/info boxes
            if (preg_match('/^>\s*\*\*(.+?):\*\*\s*(.+)$/', $trimmed, $matches)) {
                $type = strtolower($matches[1]);
                $content = $matches[2];
                $html .= $this->createInfoBox($type, $content);
                continue;
            }
            
            // Handle horizontal rules
            if (preg_match('/^(---+|\*\*\*+|___+)$/', $trimmed)) {
                $html .= "<hr>\n";
                continue;
            }
            
            // Handle paragraphs
            if (!empty($trimmed)) {
                $formatted = $this->formatInlineMarkdown($line);
                $html .= '<p>' . $formatted . '</p>' . "\n";
            } else {
                $html .= "\n";
            }
        }
        
        // Close any remaining list
        if ($inList) {
            $html .= $this->createList($listItems);
        }
        
        // Close final section if open
        if ($currentSection !== null) {
            $html .= '</section>' . "\n";
        }
        
        return $html;
    }
    
    /**
     * Check if a line is a table row
     */
    private function isTableRow(string $line): bool
    {
        return substr_count($line, '|') >= 2;
    }
    
    /**
     * Parse markdown table
     */
    private function parseTable(array $lines, int $startIndex): array
    {
        $html = '<table>' . "\n";
        $i = $startIndex;
        $isHeader = true;
        
        while ($i < count($lines)) {
            $line = trim($lines[$i]);
            
            if (!$this->isTableRow($line)) {
                break;
            }
            
            // Skip separator row
            if (preg_match('/^\|[\s\-:|]+\|$/', $line)) {
                $i++;
                continue;
            }
            
            // Parse row
            $cells = array_map('trim', explode('|', trim($line, '|')));
            
            if ($isHeader) {
                $html .= '<tr>';
                foreach ($cells as $cell) {
                    $html .= '<th>' . $this->formatInlineMarkdown($cell) . '</th>';
                }
                $html .= '</tr>' . "\n";
                $isHeader = false;
            } else {
                $html .= '<tr>';
                foreach ($cells as $cell) {
                    $html .= '<td>' . $this->formatInlineMarkdown($cell) . '</td>';
                }
                $html .= '</tr>' . "\n";
            }
            
            $i++;
        }
        
        $html .= '</table>' . "\n";
        
        return [
            'html' => $html,
            'nextIndex' => $i
        ];
    }
    
    /**
     * Create code block HTML
     */
    private function createCodeBlock(string $code, string $language): string
    {
        $code = rtrim($code);
        $escapedCode = htmlspecialchars($code);
        
        return '<div class="code-example">' . "\n"
             . '<pre><code class="language-' . htmlspecialchars($language) . '">'
             . $escapedCode
             . '</code></pre>' . "\n"
             . '</div>' . "\n";
    }
    
    /**
     * Create list HTML
     */
    private function createList(array $items): string
    {
        $html = '<ul>' . "\n";
        foreach ($items as $item) {
            $html .= '<li>' . $this->formatInlineMarkdown($item) . '</li>' . "\n";
        }
        $html .= '</ul>' . "\n";
        return $html;
    }
    
    /**
     * Create info/warning/success box
     */
    private function createInfoBox(string $type, string $content): string
    {
        $boxClass = 'info-box';
        $icon = '💡';
        $label = 'Info';
        
        if (strpos($type, 'warn') !== false || strpos($type, 'caution') !== false) {
            $boxClass = 'warning-box';
            $icon = '⚠️';
            $label = 'Warning';
        } else if (strpos($type, 'success') !== false || strpos($type, 'tip') !== false) {
            $boxClass = 'success-box';
            $icon = '✅';
            $label = 'Success';
        } else if (strpos($type, 'best') !== false) {
            $boxClass = 'info-box';
            $icon = '💡';
            $label = 'Best Practice';
        }
        
        return '<div class="' . $boxClass . '">' . "\n"
             . '<strong>' . $icon . ' ' . htmlspecialchars($label) . ':</strong> '
             . $this->formatInlineMarkdown($content)
             . "\n" . '</div>' . "\n";
    }
    
    /**
     * Format inline markdown (bold, italic, code, links)
     */
    private function formatInlineMarkdown(string $text): string
    {
        // Code spans
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        
        // Bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
        
        // Italic
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);
        
        // Links
        $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $text);
        
        return $text;
    }
    
    /**
     * Generate ID from heading text
     */
    private function generateId(string $text): string
    {
        // Remove question numbers like "Q1:", "Q2:", etc.
        $text = preg_replace('/^Q\d+:\s*/', '', $text);
        
        // Convert to lowercase and replace spaces/special chars with hyphens
        $id = strtolower($text);
        $id = preg_replace('/[^a-z0-9\s-]/', '', $id);
        $id = preg_replace('/\s+/', '-', $id);
        $id = preg_replace('/-+/', '-', $id);
        $id = trim($id, '-');
        
        return $id;
    }
    
    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^a-z0-9-]/', '-', strtolower($name));
    }
    
    /**
     * Generate category icon based on name
     */
    private function getCategoryIcon(string $category): string
    {
        $icons = [
            'php' => '🐘',
            'laravel' => '⚡',
            'database' => '🗄️',
            'devops' => '🚀',
            'cloud' => '☁️',
            'kubernetes' => '☸️',
            'frontend' => '⚛️',
            'react' => '⚛️',
            'nextjs' => '▲',
            'api' => '🔌',
            'security' => '🔒',
            'ai' => '🤖',
            'llm' => '🤖',
            'serverless' => '⚡',
            'realtime' => '⚡',
            'communication' => '💬',
        ];
        
        foreach ($icons as $keyword => $icon) {
            if (stripos($category, $keyword) !== false) {
                return $icon;
            }
        }
        
        return '📚';
    }
    
    /**
     * Format category name for display
     */
    private function formatCategoryName(string $category): string
    {
        // Remove hyphens and capitalize words
        $words = explode('-', $category);
        $formatted = array_map('ucfirst', $words);
        return implode(' ', $formatted);
    }
    
    /**
     * Generate complete HTML page
     */
    private function generateHtml(
        string $content,
        string $title,
        array $toc,
        string $category,
        string $filename
    ): string {
        $icon = $this->getCategoryIcon($category);
        $categoryDisplay = $this->formatCategoryName($category);
        $tocHtml = $this->generateTocHtml($toc);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - Comprehensive Answers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/styles/{$this->config['highlight_theme']}.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 2rem;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }
        .back-link:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .toc {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .toc h2 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        .toc ul {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 0.5rem;
        }
        .toc a {
            color: #764ba2;
            text-decoration: none;
            padding: 0.5rem;
            display: block;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .toc a:hover {
            background: #f0f0f0;
            padding-left: 1rem;
        }
        .topic-section {
            background: white;
            border-radius: 8px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .topic-section h2 {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #667eea;
        }
        .topic-section h3 {
            color: #764ba2;
            font-size: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .topic-section h4 {
            color: #555;
            font-size: 1.2rem;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .topic-section h5 {
            color: #666;
            font-size: 1.1rem;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .topic-section h6 {
            color: #777;
            font-size: 1rem;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .topic-section p {
            margin-bottom: 1rem;
            color: #555;
        }
        .topic-section ul, .topic-section ol {
            margin-left: 2rem;
            margin-bottom: 1rem;
        }
        .topic-section li {
            margin-bottom: 0.5rem;
            color: #555;
        }
        .code-example {
            margin: 1.5rem 0;
            border-radius: 8px;
            overflow: hidden;
        }
        .code-example pre {
            margin: 0;
            border-radius: 8px;
        }
        .code-example code {
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 0.9rem;
        }
        p code, li code {
            background: #f4f4f4;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 0.9em;
            color: #e83e8c;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f5f5f5;
        }
        hr {
            border: none;
            border-top: 2px solid #eee;
            margin: 2rem 0;
        }
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>{$icon} {$title}</h1>
        <p>Comprehensive answers with syntax-highlighted examples</p>
    </header>

    <div class="container">
        <a href="../index.html" class="back-link">← Back to Interview Bank</a>

        {$tocHtml}

        {$content}
    </div>

    <footer>
        <p>&copy; 2024 Laravel Interview Bank - {$categoryDisplay}</p>
        <p style="margin-top: 1rem; opacity: 0.8;">Comprehensive guide with practical examples and best practices</p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/languages/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/languages/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/languages/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/languages/bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/languages/sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/languages/yaml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/languages/json.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/{$this->config['highlight_js_version']}/languages/typescript.min.js"></script>
    <script>
        hljs.highlightAll();
    </script>
</body>
</html>
HTML;
    }
    
    /**
     * Generate table of contents HTML
     */
    private function generateTocHtml(array $toc): string
    {
        if (empty($toc)) {
            return '';
        }
        
        $html = '<div class="toc">' . "\n";
        $html .= '<h2>📑 Table of Contents</h2>' . "\n";
        $html .= '<ul>' . "\n";
        
        foreach ($toc as $index => $item) {
            $number = $index + 1;
            $html .= '<li><a href="#' . htmlspecialchars($item['id']) . '">' 
                   . $number . '. ' . htmlspecialchars($item['text']) 
                   . '</a></li>' . "\n";
        }
        
        $html .= '</ul>' . "\n";
        $html .= '</div>' . "\n";
        
        return $html;
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $converter = new MarkdownToHtmlConverter();
    
    if (isset($argv[1])) {
        $categoryName = $argv[1];
        
        if ($categoryName === '--all' || $categoryName === '-a') {
            $converter->convertAll();
        } else {
            $converter->convertCategory($categoryName);
        }
    } else {
        echo "Markdown to HTML Converter for Interview Bank Answers\n\n";
        echo "Usage:\n";
        echo "  php automation/convert-markdown-to-html.php <category-name>\n";
        echo "  php automation/convert-markdown-to-html.php --all\n\n";
        echo "Examples:\n";
        echo "  php automation/convert-markdown-to-html.php php-laravel-api-security\n";
        echo "  php automation/convert-markdown-to-html.php database-optimization\n";
        echo "  php automation/convert-markdown-to-html.php --all\n\n";
        echo "Available categories:\n";
        
        $categories = $converter->getCategories();
        foreach ($categories as $cat) {
            echo "  - $cat\n";
        }
    }
}
