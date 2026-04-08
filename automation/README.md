# Interview Bank Automation Suite

Complete automation system for validating, analyzing, and indexing interview questions with code blocks and Mermaid diagrams.

## 🎯 Features

### 1. **Question Validator** (`QuestionValidator.php`)
- ✅ Validates markdown file structure
- ✅ Checks code block syntax for multiple languages (PHP, JavaScript, JSON, YAML, SQL)
- ✅ Validates Mermaid diagram syntax and structure
- ✅ Detects broken internal links
- ✅ Verifies header hierarchy
- ✅ Ensures Q&A format consistency
- ✅ Identifies question numbering gaps

### 2. **Metadata Extractor** (`MetadataExtractor.php`)
- 📊 Extracts difficulty levels (basic, intermediate, advanced, expert)
- 🏷️ Identifies technology tags (50+ patterns)
- 📝 Extracts topics from headers
- ❓ Counts and catalogs questions
- 💻 Analyzes code examples by language
- 📈 Tracks Mermaid diagrams by type
- 📅 Records file metadata and timestamps

### 3. **Statistics Dashboard** (`StatisticsDashboard.php`)
- 📊 Generates comprehensive statistics reports
- 📈 Shows difficulty distribution across domains
- 💻 Technology coverage matrix
- 🗂️ Domain breakdown with metrics
- 📄 Outputs in Markdown, JSON, and HTML formats
- 🎨 Interactive HTML dashboard with charts

### 4. **Cross-Reference Mapper** (`CrossReferenceMapper.php`)
- 🔗 Identifies related questions across folders
- 🎯 Maps technology overlaps
- 📚 Links common topics
- 🌐 Generates domain network graphs
- 💡 Suggests relevant cross-references
- 📊 Creates Mermaid visualization diagrams

### 5. **Search Index Generator** (`SearchIndexGenerator.php`)
- 🔍 Creates full-text search index
- 📦 Generates compact JSON for web integration
- 🌐 Creates Lunr.js compatible index
- 🔎 Elasticsearch mapping configuration
- 🖥️ Interactive search web interface
- ⚡ Optimized for fast client-side search

### 6. **HTML Answer Generator** (`generate-html-answers.py`) ⭐ NEW!
- 📖 Reads questions.md and answers.md from all topic directories
- 🎨 Converts markdown to fully-formatted HTML with syntax highlighting
- 💡 Uses Pygments for professional code highlighting (Monokai theme)
- 📊 Generates inline SVG architecture flow diagrams automatically:
  - Laravel Request Lifecycle (middleware, routing, controllers)
  - Payment Processing Flow (validation, gateway, webhooks)
  - Database Query Execution Plans (parser, optimizer, execution)
  - React Component Lifecycle (mounting, updating, hooks)
  - Kubernetes Deployment Flow (API server, scheduler, pods)
  - WebSocket Real-time Architecture (pub/sub, load balancing)
- 📝 Augments short answers with additional context
- 🏗️ Outputs structured HTML with semantic `<article>` elements
- 📱 Responsive design with modern CSS styling
- 🎯 Automatic diagram detection based on question/answer content

### 7. **Answer Quality Validator** (`validate-answers.php`) ⭐ NEW!
- ✅ Validates HTML answer files for required sections
- 📋 Checks for: Overview, Core Concepts, Code Examples, Best Practices, Common Pitfalls
- 🎨 Ensures all code blocks have language tags for syntax highlighting
- 🔗 Verifies internal links point to existing sections
- 📊 Generates comprehensive statistics for each file
- 📈 Creates quality score and summary reports
- ⚡ Fast validation of entire answer directory
- 🎯 Identifies broken links and missing sections

## 🚀 Quick Start

### Requirements
- PHP 8.0 or higher (for PHP automation scripts)
- Python 3.7+ (for HTML answer generation)
- Pygments library (`pip install pygments`)
- Command line access

### Running the Complete Suite

```bash
php automation/run-all.php
```

This will:
1. Validate all markdown files
2. Extract metadata and tags
3. Generate statistics dashboard
4. Map cross-references
5. Create search indices

### Running HTML Answer Generation

**Generate comprehensive HTML answers with syntax highlighting and diagrams:**
```bash
python automation/generate-html-answers.py
```

Or on Windows:
```bash
python3 automation/generate-html-answers.py
```

This will:
- Read questions.md and answers.md from all 6 topic directories
- Augment existing answers with detailed explanations
- Convert markdown code blocks to syntax-highlighted HTML using Pygments
- Generate architecture flow diagrams as inline SVG for:
  - Laravel request lifecycle
  - Payment processing flow
  - Database query execution plans
  - React component lifecycle
  - Kubernetes deployment flow
  - WebSocket real-time architecture
- Output fully-formatted HTML with `<article>` elements
- Save to `automation/output/comprehensive-answers.html`

### Running Individual Components

**Validation Only:**
```bash
php automation/run-validation.php
```

**Answer Quality Validation:**
```bash
# Validate all answer files
php automation/validate-answers.php --dir=answers

# Validate a single answer file
php automation/validate-answers.php --file=answers/laravel-framework-answers.html

# Show help
php automation/validate-answers.php --help
```

**Custom Analysis:**
```php
<?php
require_once 'automation/MetadataExtractor.php';

$extractor = new InterviewBank\Automation\MetadataExtractor();
$metadata = $extractor->extractFromDirectory('interview-bank');
$summary = $extractor->generateSummary($metadata);
print_r($summary);
```

## 📁 Output Files

All output files are saved to `automation/output/`:

### Validation
- `validation-report.md` - Errors and warnings report
- Lists all syntax errors, broken links, and formatting issues

### Metadata
- `metadata-summary.json` - High-level statistics
- `metadata-full.json` - Complete metadata for all files

### Statistics
- `statistics-dashboard.md` - Markdown report
- `statistics-dashboard.html` - **Interactive HTML dashboard** 📊
- `statistics-report.json` - JSON format for API integration

### Cross-References
- `cross-reference-report.md` - Related questions mapping
- `cross-references.json` - JSON mapping data
- `domain-network-graph.md` - Mermaid network visualization

### Search
- `search-index.json` - Full search index
- `search-index-compact.json` - Optimized version
- `lunr-index.json` - Lunr.js compatible
- `elasticsearch-mapping.json` - ES configuration
- `search.html` - **Interactive search interface** 🔍

### HTML Answers (NEW!)
- `comprehensive-answers.html` - **Fully-formatted HTML answers** 🎨
  - Syntax-highlighted code blocks (Pygments with Monokai theme)
  - Inline SVG architecture diagrams
  - Responsive design with modern CSS
  - Organized by topic sections
  - Article-based layout with question/answer pairs

### Answer Quality Validation (NEW!)
- Terminal output with validation results
- Per-file statistics and quality metrics
- Summary report with quality score
- Identifies missing sections and broken links
- Code block syntax validation

## 📊 Example: Answer Quality Validation

```
Validating: laravel-framework-answers.html
  ⚠️  WARNINGS:
     - Missing recommended section: 'Common Pitfalls'
  📊 STATISTICS:
     - Main sections: 15
     - Subsections: 89
     - Code blocks: 145 (145 with language tags)
     - Internal links: 15 (15 valid, 0 broken)
     - Info boxes: 12
     - Tables: 8
     - File size: 245.67 KB
     - Has TOC: Yes

======================================================================
VALIDATION SUMMARY
======================================================================

Total files validated: 19
✅ Valid files: 18
❌ Files with errors: 1
⚠️  Files with warnings: 5

Total errors: 3
Total warnings: 8

📈 Quality Score: 94.74%

======================================================================
```

## 📊 Example: Statistics Dashboard

```json
{
  "total_files": 87,
  "total_questions": 3542,
  "total_code_blocks": 892,
  "total_mermaid_diagrams": 156,
  "by_difficulty": {
    "basic": 15,
    "intermediate": 28,
    "advanced": 32,
    "expert": 12
  },
  "by_technology": {
    "PHP": { "files": 24, "mentions": 487 },
    "Laravel": { "files": 22, "mentions": 356 },
    "AWS": { "files": 18, "mentions": 412 }
  }
}
```

## 🔍 Example: Cross-Reference Mapping

The system identifies related questions across domains:

**Example 1: Razorpay Webhooks → AWS Lambda**
- Both cover webhook processing patterns
- Common topics: signature verification, idempotency
- Technology overlap: serverless, event-driven architecture

**Example 2: Kubernetes → Database Optimization**
- Infrastructure and database performance overlap
- Common topics: resource management, scaling
- Technology overlap: Redis, PostgreSQL

## 🌐 Web Integration

### Using the Search Index

```javascript
// Load the search index
fetch('automation/output/search-index.json')
  .then(res => res.json())
  .then(index => {
    // Search documents
    const results = index.documents.filter(doc => 
      doc.searchable_content.includes('webhook')
    );
    
    // Filter by technology
    const phpDocs = index.documents.filter(doc =>
      doc.technologies.includes('PHP')
    );
  });
```

### Using the Lunr.js Index

```javascript
const lunr = require('lunr');
const indexData = require('./output/lunr-index.json');

const idx = lunr(function() {
  this.field('title', { boost: 10 });
  this.field('technologies', { boost: 7 });
  this.field('content');
  
  indexData.documents.forEach(doc => {
    this.add(doc);
  });
});

const results = idx.search('webhook payment');
```

## 🛠️ Architecture

### Class Structure

```
InterviewBank\Automation\
├── QuestionValidator
│   ├── validateMarkdownFile()
│   ├── validateCodeBlocks()
│   ├── validateMermaidDiagrams()
│   └── generateReport()
├── MetadataExtractor
│   ├── extractFromFile()
│   ├── extractDifficulty()
│   ├── extractTechnologies()
│   └── generateSummary()
├── StatisticsDashboard
│   ├── generateDashboard()
│   ├── generateJsonReport()
│   └── generateHtmlDashboard()
├── CrossReferenceMapper
│   ├── mapCrossReferences()
│   ├── calculateSimilarity()
│   └── generateNetworkGraph()
└── SearchIndexGenerator
    ├── generateSearchIndex()
    ├── generateJsonIndex()
    └── generateSearchInterface()
```

## 📋 Validation Rules

### Code Block Validation
- **PHP**: Uses `php -l` for syntax checking
- **JSON**: Validates with `json_decode()`
- **YAML**: Uses `yaml_parse()` if extension available
- **JavaScript/TypeScript**: Pattern matching for common errors
- **SQL**: Keyword detection

### Mermaid Diagram Validation
- Checks for valid diagram types
- Verifies bracket/parenthesis matching
- Ensures nodes have connections
- Validates syntax structure

### Markdown Structure
- Header hierarchy (h1 → h2 → h3)
- Link validation (internal and relative)
- Q&A format consistency
- Question numbering sequence

## 🎯 Technology Detection Patterns

The system detects 50+ technologies:

- **Languages**: PHP, JavaScript, TypeScript, Python, Go
- **Frameworks**: Laravel, React, Next.js, Vue.js
- **Cloud**: AWS, Lambda, Terraform, Docker, Kubernetes
- **Databases**: PostgreSQL, MySQL, MongoDB, Redis
- **APIs**: REST, GraphQL, WebSocket
- **DevOps**: Jenkins, GitHub Actions, Istio, Helm
- **AI/ML**: OpenAI, Claude, LangChain, RAG
- **Payment**: Razorpay, Stripe
- **Security**: OAuth, JWT, PCI DSS, OWASP

## 📈 Difficulty Level Detection

Difficulty is determined by:
- **Explicit markers**: Stars (⭐), keywords in filenames
- **Content analysis**: Complexity indicators
- **Pattern matching**: "basic", "advanced", "expert" keywords
- **Context**: File location and naming

### Difficulty Levels
- ⭐ **Basic**: 15-30 min, fundamentals
- ⭐⭐ **Intermediate**: 30-45 min, practical application
- ⭐⭐⭐ **Advanced**: 45-60 min, complex scenarios
- ⭐⭐⭐⭐ **Expert**: 60-90 min, system design

## 🔧 Customization

### Adding New Technology Patterns

Edit `MetadataExtractor.php`:

```php
private array $technologyPatterns = [
    'Your Tech' => '/\byour[-\s]?tech\b/i',
    // ... more patterns
];
```

### Adding Custom Validators

Extend `QuestionValidator.php`:

```php
private function validateCustomSyntax(string $code, int $lineNumber, string $filePath): void
{
    // Your validation logic
}
```

### Custom Cross-Reference Keywords

Edit `CrossReferenceMapper.php`:

```php
private array $keywordPatterns = [
    'your_category' => ['keyword1', 'keyword2'],
    // ... more patterns
];
```

## 🐛 Troubleshooting

### Common Issues

**PHP Syntax Validation Fails:**
- Ensure PHP CLI is in PATH
- Check PHP version (8.0+ required)

**YAML Validation Skipped:**
- Install YAML extension: `pecl install yaml`

**Memory Issues:**
- Increase PHP memory: `php -d memory_limit=512M run-all.php`

**Permission Errors:**
- Ensure output directory is writable
- Check file permissions

## 📝 Output Format Examples

### Validation Report
```markdown
# Validation Report

## Summary
- Total files: 87
- Valid files: 85
- Files with errors: 2
- Total errors: 3
- Total warnings: 12

## Detailed Results

### ❌ questions.md
**Errors (1):**
- PHP syntax error at line 245: unexpected ';'
```

### Cross-Reference Report
```markdown
## Cross-References by Domain Pair

### PHP Laravel ↔ DevOps Cloud K8s
**8 cross-references found**

Strong connections:
- webhook-processing.md ↔ lambda-examples.md
  - Both cover Webhook Processing
  - Both use AWS Lambda
```

## 🚀 Performance

- Processes 87 files in ~2-3 seconds
- Validates 3500+ questions
- Analyzes 800+ code examples
- Generates 14 output files
- Memory usage: ~50MB

## 📜 License

Part of the Interview Bank project. For educational purposes.

## 🤝 Contributing

To extend the automation:
1. Create new validator/extractor class
2. Implement interface methods
3. Add to `run-all.php`
4. Update this README

## 📞 Support

For issues or questions about the automation suite, review the generated reports or check the validation output for specific errors.
