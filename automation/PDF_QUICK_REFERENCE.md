# PDF Generator - Quick Reference

## Installation

```bash
# Clone repository
git clone <repository-url>
cd interview-bank

# Install dependencies
composer install
```

## Basic Usage

```bash
# Generate all PDFs
php automation/generate-pdf-answers.php

# Generate specific category
php automation/generate-pdf-answers.php php-core-answers

# Show help
php automation/generate-pdf-answers.php --help
```

## Shell Scripts

```bash
# Unix/Linux/Mac
./automation/generate-pdfs.sh

# Windows
automation\generate-pdfs.bat
```

## Alternative Library (mPDF)

```bash
# Install mPDF
composer require mpdf/mpdf

# Generate with mPDF
php automation/generate-pdf-answers-mpdf.php
```

## Available Categories

- `php-core-answers`
- `laravel-framework-answers`
- `advanced-laravel-answers`
- `javascript-frontend-answers`
- `frontend-react-nextjs-advanced-answers`
- `database-mysql-answers`
- `database-advanced-optimization-answers`
- `devops-git-answers`
- `devops-kubernetes-aws-answers`
- `api-security-answers`
- `testing-debugging-answers`
- `oop-design-patterns-answers`
- `coding-challenges-answers`
- `cms-wordpress-answers`
- `cms-magento-drupal-answers`
- `codeigniter-answers`
- `cloud-infrastructure-answers`
- `realtime-communication-answers`
- `ai-llm-serverless-answers`

## Output Location

```
pdf-exports/
├── php-core-answers.pdf
├── laravel-framework-answers.pdf
├── database-mysql-answers.pdf
└── ...
```

## Programmatic Usage

```php
<?php
require_once 'vendor/autoload.php';
require_once 'automation/generate-pdf-answers.php';

$exporter = new AnswerPDFExporter();

// Generate all
$exporter->generateAll();

// Generate one
$exporter->generatePDF('php-core-answers');
```

## Common Commands

```bash
# Generate all PDFs
php automation/generate-pdf-answers.php

# Generate PHP category
php automation/generate-pdf-answers.php php-core-answers

# Generate Laravel category
php automation/generate-pdf-answers.php laravel-framework-answers

# List all HTML files (categories)
ls -la answers/*.html

# Check generated PDFs
ls -lh pdf-exports/*.pdf

# View PDF count
ls pdf-exports/*.pdf | wc -l

# Remove all PDFs (regenerate from scratch)
rm pdf-exports/*.pdf
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Dependencies not installed | `composer install` |
| File not found | Check category name in `answers/` |
| Permission denied | `chmod 755 pdf-exports/` |
| Memory limit | `php -d memory_limit=512M ...` |
| TCPDF errors | Check vendor installation |

## Features in PDFs

- ✅ Cover page with category title
- ✅ Table of contents
- ✅ Syntax-highlighted code blocks
- ✅ Formatted tables
- ✅ Bullet and numbered lists
- ✅ Info/warning/success boxes
- ✅ Headers with category name
- ✅ Page numbers in footer
- ✅ Category-specific colors

## File Sizes

- Typical size: 500KB - 2MB per PDF
- Total for all categories: ~15-30MB
- Varies by content length and code examples

## Performance

- Processing time: 2-5 seconds per PDF
- Memory usage: 50-100MB per PDF
- Batch processing: ~1-2 minutes for all PDFs

## Customization

### Change margins
Edit `generate-pdf-answers.php`:
```php
$pdf->SetMargins(15, 20, 15); // left, top, right
```

### Change colors
Edit category colors array:
```php
private $categoryColors = [
    'php-core' => '#667eea',
    'laravel' => '#e3342f',
    // ... add your colors
];
```

### Change fonts
```php
$pdf->SetFont('helvetica', 'B', 18); // family, style, size
```

## Best Practices

1. ✅ Run after updating HTML answers
2. ✅ Check output quality periodically
3. ✅ Backup PDFs before regenerating
4. ✅ Monitor disk space usage
5. ✅ Version PDFs if distributing

## Integration Examples

### Batch Script
```bash
#!/bin/bash
cd /path/to/interview-bank
php automation/generate-pdf-answers.php
echo "PDFs generated at $(date)"
```

### Cron Job
```cron
# Daily at 2 AM
0 2 * * * cd /path/to/interview-bank && php automation/generate-pdf-answers.php
```

### CI/CD Pipeline
```yaml
- name: Generate PDFs
  run: |
    composer install
    php automation/generate-pdf-answers.php
- name: Upload PDFs
  uses: actions/upload-artifact@v2
  with:
    name: pdfs
    path: pdf-exports/
```

## Documentation

- **Full Guide**: `automation/PDF_GENERATOR_README.md`
- **Library Comparison**: `automation/PDF_LIBRARY_COMPARISON.md`
- **Examples**: `automation/example-pdf-usage.php`
- **Main README**: `automation/README.md`

## Quick Links

- TCPDF: https://tcpdf.org/
- mPDF: https://mpdf.github.io/
- Composer: https://getcomposer.org/

## Support

For issues:
1. Verify HTML files exist in `answers/`
2. Check Composer dependencies installed
3. Review error messages
4. Check file permissions
5. Ensure sufficient disk space

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**License**: Part of Interview Bank project
