# Question Deduplication Analyzer

## Overview

This PHP script analyzes all question files in the `interview-bank/php-laravel-api-security/` directory to identify duplicate questions using string similarity algorithms.

## Features

- **Levenshtein Distance Algorithm**: Measures the minimum number of single-character edits required to change one string into another
- **Similar Text Comparison**: Uses PHP's `similar_text()` function to calculate percentage similarity
- **Composite Match Score**: Combines both metrics (70% similarity + 30% distance score) for accurate duplicate detection
- **Multiple Output Formats**: Generates both text and HTML reports
- **Statistical Analysis**: Provides detailed statistics about duplicate patterns

## Requirements

- PHP 8.0 or higher
- No external dependencies required (uses built-in PHP functions)

## Usage

### Basic Usage

Run the script from the command line:

```bash
php analyze-duplicates.php
```

### Output Files

The script generates two report files:

1. **deduplication-report.txt** - Plain text report for easy reading in terminal or text editors
2. **deduplication-report.html** - Interactive HTML report with styling and visual metrics

## Algorithm Details

### Similarity Detection

The script uses two complementary algorithms:

1. **Levenshtein Distance**
   - Threshold: ≤ 20 characters difference
   - Limited to 255 characters for performance
   - Ideal for detecting minor textual variations

2. **Similar Text Percentage**
   - Threshold: ≥ 85% similarity
   - Compares entire question text
   - Better for detecting paraphrased duplicates

### Match Score Calculation

```
Match Score = (Similarity% × 0.7) + (Distance Score × 0.3)
```

Where:
- Similarity% = Result from similar_text() × 100
- Distance Score = max(0, 100 - levenshtein_distance)

### Question Normalization

Before comparison, questions are normalized:
- Converted to lowercase
- Special characters removed
- Multiple spaces collapsed to single space
- Trimmed of leading/trailing whitespace

## Report Contents

### Summary Section
- Total questions analyzed
- Number of duplicate pairs found
- Threshold values used
- Analysis timestamp

### Duplicate Details
For each duplicate pair:
- Match score (composite metric)
- Similarity percentage
- Levenshtein distance
- Question numbers
- Full question text
- Source file and line numbers

### Statistics Section
- Total unique questions with duplicates
- High similarity matches (>95%)
- Medium similarity matches (85-95%)
- Low distance matches (≤10)
- List of all affected question numbers

## Customization

You can adjust detection thresholds in the script:

```php
private const SIMILARITY_THRESHOLD = 0.85;      // 85% similarity
private const LEVENSHTEIN_THRESHOLD = 20;       // 20 characters difference
```

## Example Output

### Console Output
```
Question Deduplication Analysis
================================================================================

Loading questions from interview-bank/php-laravel-api-security...
Loaded 500 questions from 3 files.

Analyzing questions for duplicates...
Using Levenshtein distance threshold: 20
Using similarity threshold: 85%

Found 12 potential duplicate pairs.

Reports generated:
- Text Report: deduplication-report.txt
- HTML Report: deduplication-report.html

Analysis complete!
```

### HTML Report Features
- Color-coded match scores (red for high, orange for medium, blue for low)
- Responsive design for all screen sizes
- Print-friendly styling
- Visual metrics with icons
- Easy-to-scan card layout

## Performance Considerations

- Time Complexity: O(n²) where n is the number of questions
- For 500 questions: ~125,000 comparisons
- Typical execution time: 5-15 seconds depending on system
- Memory usage: Minimal (questions stored in memory)

## Integration

The script can be integrated into CI/CD pipelines:

```bash
# Run as part of quality checks
php analyze-duplicates.php

# Check if duplicates were found
if [ -s deduplication-report.txt ]; then
    echo "Duplicates detected! Review report."
    exit 1
fi
```

## Troubleshooting

### "This script must be run from the command line"
- Ensure you're executing via CLI: `php analyze-duplicates.php`
- Don't access via web browser

### No questions found
- Verify the directory path: `interview-bank/php-laravel-api-security/`
- Ensure question files use the format: `1. Question text here?`

### Memory issues with large files
- The script limits Levenshtein comparison to 255 characters
- Consider splitting very large question files

## License

Same as parent project.
