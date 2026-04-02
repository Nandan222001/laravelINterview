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

## 🚀 Quick Start

### Requirements
- PHP 8.0 or higher
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

### Running Individual Components

**Validation Only:**
```bash
php automation/run-validation.php
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
