# PDF Answer Generator

## Overview

The PDF Answer Generator is a powerful automation tool that exports interview answer pages from HTML format into professionally formatted PDF documents. These PDFs are optimized for offline study with proper formatting, syntax highlighting, and organized structure.

## Features

- ✅ **Professional Layout**: Clean, readable PDF format optimized for printing and offline study
- ✅ **Syntax Highlighting**: Code examples with dark-themed syntax highlighting
- ✅ **Table of Contents**: Automatic TOC generation for easy navigation
- ✅ **Category Colors**: Color-coded headers matching each category's theme
- ✅ **Code Examples**: Properly formatted code blocks with monospace fonts
- ✅ **Tables**: Well-structured tables with alternating row colors
- ✅ **Info Boxes**: Visual highlighting for important notes, warnings, and tips
- ✅ **Page Numbers**: Footer with page numbering
- ✅ **Cover Page**: Professional cover with category title and description
- ✅ **Organized Output**: PDFs saved to dedicated `pdf-exports/` directory

## Prerequisites

### Required Software

- **PHP 7.4 or higher**
- **Composer** (PHP dependency manager)

### Installation

1. Install Composer dependencies:
   ```bash
   composer install
   ```

This will install the TCPDF library, which is used for PDF generation.

## Usage

### Generate All PDFs

To generate PDFs for all available answer categories:

```bash
php automation/generate-pdf-answers.php
```

### Generate Specific Category

To generate a PDF for a specific category:

```bash
php automation/generate-pdf-answers.php php-core-answers
php automation/generate-pdf-answers.php laravel-framework-answers
php automation/generate-pdf-answers.php database-mysql-answers
```

### Show Help

```bash
php automation/generate-pdf-answers.php --help
```

## Available Categories

The script automatically detects all HTML files in the `answers/` directory. Current categories include:

- `php-core-answers` - PHP Core Fundamentals
- `laravel-framework-answers` - Laravel Framework
- `advanced-laravel-answers` - Advanced Laravel
- `javascript-frontend-answers` - JavaScript & Frontend
- `frontend-react-nextjs-advanced-answers` - React & Next.js
- `database-mysql-answers` - MySQL Database
- `database-advanced-optimization-answers` - Database Optimization
- `devops-git-answers` - DevOps & Git
- `devops-kubernetes-aws-answers` - Kubernetes & AWS
- `api-security-answers` - API Security
- `testing-debugging-answers` - Testing & Debugging
- `oop-design-patterns-answers` - OOP & Design Patterns
- `coding-challenges-answers` - Coding Challenges
- `cms-wordpress-answers` - WordPress CMS
- `cms-magento-drupal-answers` - Magento & Drupal
- `codeigniter-answers` - CodeIgniter
- `cloud-infrastructure-answers` - Cloud Infrastructure
- `realtime-communication-answers` - Real-time Communication
- `ai-llm-serverless-answers` - AI, LLM & Serverless

## Output

PDFs are saved to the `pdf-exports/` directory in the project root. Each PDF is named after its category:

```
pdf-exports/
├── php-core-answers.pdf
├── laravel-framework-answers.pdf
├── database-mysql-answers.pdf
└── ...
```

## PDF Structure

Each generated PDF contains:

1. **Cover Page**
   - Category title
   - Description
   - Generation date
   - Professional gradient header

2. **Table of Contents**
   - Numbered list of all sections
   - Quick reference for navigation

3. **Content Sections**
   - Hierarchical headings (H2, H3, H4)
   - Formatted paragraphs
   - Bullet and numbered lists
   - Code blocks with syntax highlighting
   - Tables with headers and alternating rows
   - Info boxes (info, warning, success)

4. **Footer**
   - Page numbers on every page

## Customization

### Category Colors

Colors are automatically assigned based on category keywords. You can customize them in the `$categoryColors` array:

```php
private $categoryColors = [
    'php-core' => '#667eea',
    'laravel' => '#e3342f',
    'javascript' => '#f7df1e',
    // ... add more
];
```

### PDF Settings

Modify PDF settings in the `generatePDF()` method:

```php
$pdf->SetMargins(15, 20, 15);        // Left, Top, Right margins
$pdf->SetHeaderMargin(5);             // Header margin
$pdf->SetFooterMargin(10);            // Footer margin
$pdf->SetAutoPageBreak(true, 20);     // Auto page break
```

### Fonts

Change fonts in various methods:

```php
$pdf->SetFont('helvetica', 'B', 18);  // Font family, style, size
```

Available fonts in TCPDF:
- `helvetica` (default, sans-serif)
- `courier` (monospace, for code)
- `times` (serif)

## Technical Details

### Libraries Used

- **TCPDF 6.6+**: PHP library for generating PDF documents
  - Pure PHP implementation, no external dependencies
  - Full Unicode support
  - Advanced typography features

### Architecture

The script consists of two main classes:

1. **PDFAnswerGenerator** (extends TCPDF)
   - Custom PDF class with header/footer handling
   - Category-specific styling
   - Utility methods for color conversion

2. **AnswerPDFExporter**
   - Main export logic
   - HTML parsing and content extraction
   - PDF content generation
   - File management

### HTML Parsing

The script uses PHP's DOMDocument and DOMXPath to:
- Extract title and description from HTML header
- Parse table of contents links
- Extract sections and their content
- Handle various content types (code, tables, lists, etc.)

### Content Types Supported

- Headings (H2, H3, H4)
- Paragraphs
- Unordered lists (bullet points)
- Ordered lists (numbered)
- Code blocks with language detection
- Tables with headers
- Info boxes (info, warning, success)

## Troubleshooting

### Common Issues

**Issue**: "Dependencies not installed" error
```bash
# Solution: Install Composer dependencies
composer install
```

**Issue**: "File not found" error
```bash
# Solution: Verify the HTML file exists in answers/ directory
ls -la answers/
```

**Issue**: Permission denied on pdf-exports/
```bash
# Solution: Create directory with proper permissions
mkdir -p pdf-exports
chmod 755 pdf-exports
```

**Issue**: Memory limit exceeded for large files
```php
// Solution: Increase PHP memory limit in php.ini or runtime
ini_set('memory_limit', '512M');
```

### Debug Mode

To enable TCPDF debug mode, modify the PDFAnswerGenerator constructor:

```php
$pdf = new PDFAnswerGenerator('P', 'mm', 'A4', true, 'UTF-8', true); // Last param = debug
```

## Performance

- **Processing Time**: ~2-5 seconds per PDF (depending on content size)
- **Memory Usage**: ~50-100MB per PDF
- **Output Size**: ~500KB - 2MB per PDF (varies by content)

## Batch Processing

For batch processing all PDFs, use the default command:

```bash
# Generate all PDFs at once
php automation/generate-pdf-answers.php

# With output redirection
php automation/generate-pdf-answers.php > pdf-generation.log 2>&1
```

## Integration

### Shell Scripts

Create a wrapper script (`generate-pdfs.sh`):

```bash
#!/bin/bash
cd "$(dirname "$0")"
php automation/generate-pdf-answers.php
echo "PDFs generated in pdf-exports/"
```

### Windows Batch

Create a batch file (`generate-pdfs.bat`):

```batch
@echo off
cd /d %~dp0
php automation\generate-pdf-answers.php
echo PDFs generated in pdf-exports\
pause
```

### Cron Job

Automate PDF generation:

```cron
# Generate PDFs daily at 2 AM
0 2 * * * cd /path/to/project && php automation/generate-pdf-answers.php
```

## Best Practices

1. **Run after HTML updates**: Always regenerate PDFs when HTML answers are updated
2. **Check output quality**: Review generated PDFs periodically
3. **Disk space**: Monitor `pdf-exports/` directory size
4. **Version control**: Consider versioning PDFs if needed for distribution
5. **Backup**: Keep backups of generated PDFs before regenerating

## Future Enhancements

Potential improvements:

- [ ] Add bookmarks for better navigation
- [ ] Support for hyperlinks within PDF
- [ ] Include Mermaid diagrams as images
- [ ] Add search/index functionality
- [ ] Generate combined "all-in-one" PDF
- [ ] Support for custom themes/templates
- [ ] Add watermarking options
- [ ] Generate PDF metadata/tags
- [ ] Multi-language support
- [ ] Interactive forms/annotations

## License

This tool is part of the Interview Bank project and follows the same license.

## Support

For issues or questions:
1. Check existing HTML source files in `answers/`
2. Verify Composer dependencies are installed
3. Review error messages carefully
4. Check file permissions

## Version History

- **v1.0.0** (2024)
  - Initial release
  - Support for all major content types
  - Professional formatting and styling
  - Batch generation capability
