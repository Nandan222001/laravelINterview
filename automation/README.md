# Automation Scripts

This directory contains automation scripts for managing the interview questions and answers.

## generate-missing-answers.php

Compares questions across all interview-bank subdirectories with existing answer files, identifies unanswered questions, and generates comprehensive answers following the templates/comprehensive-answer-template.md structure.

### Features

- **Automatic Question Detection**: Scans all question files in interview-bank subdirectories
- **Answer Comparison**: Identifies which questions already have answers
- **Comprehensive Answer Generation**: Creates answers with the following sections:
  - 📚 Overview
  - 🔑 Core Concepts
  - 💻 Code Examples (Basic, Advanced, Production-Ready)
  - ✅ Best Practices
  - ⚠️ Common Pitfalls
- **Flexible Options**: Category filtering, dry-run mode, output customization
- **Statistics Reporting**: Detailed summary of processing results

### Usage

```bash
# Generate answers for all categories
php automation/generate-missing-answers.php

# Process only a specific category
php automation/generate-missing-answers.php --category=web-technologies

# Dry run to see what would be generated
php automation/generate-missing-answers.php --dry-run

# Limit the number of answers generated
php automation/generate-missing-answers.php --limit=10

# Specify custom output directory
php automation/generate-missing-answers.php --output=/path/to/output

# Combine options
php automation/generate-missing-answers.php --category=database-general --limit=5 --dry-run

# Show help
php automation/generate-missing-answers.php --help
```

### Options

| Option | Description | Example |
|--------|-------------|---------|
| `--category=<name>` | Process only specific category | `--category=cms-platforms` |
| `--dry-run` | Show what would be generated without writing files | `--dry-run` |
| `--limit=<number>` | Limit number of answers to generate | `--limit=20` |
| `--output=<dir>` | Output directory (default: interview-bank/<category>/) | `--output=./output` |
| `--help` | Show help message | `--help` |

### How It Works

1. **Scan Categories**: Finds all subdirectories in `interview-bank/`
2. **Find Questions**: Locates all files with "question" in the name
3. **Parse Questions**: Extracts questions using multiple patterns:
   - Numbered format: `1. Question text`
   - Heading format: `### Question 1: Question text`
   - Q-prefix format: `**Q1:** Question text` or `Q1: Question text`
4. **Find Answers**: Locates all files with "answer" in the name
5. **Compare**: Identifies questions that don't have corresponding answers
6. **Generate**: Creates comprehensive answers following the template structure
7. **Output**: Writes to `answers-generated.md` in the category directory

### Question Detection Patterns

The script recognizes questions in the following formats:

```markdown
# Pattern 1: Numbered questions
1. What is PHP and what does it stand for?
2. Explain the difference between echo and print in PHP.

# Pattern 2: Heading questions
### Question 1: What is semantic HTML?
### ⭐⭐ Question 2: Explain closures in JavaScript

# Pattern 3: Q-prefix questions
**Q1:** What is a primary key?
Q2: How do foreign keys enforce referential integrity?
```

### Output Structure

Generated answers are saved to `interview-bank/<category>/answers-generated.md` with the following structure:

```markdown
# Category Name - Comprehensive Answers

**Auto-generated answers following comprehensive template structure**

Generated on: 2024-01-15 14:30:00

---

## TABLE OF CONTENTS

1. [Question 1 text](#question-1)
2. [Question 2 text](#question-2)
...

---

## Question 1: [Question text]

**Difficulty**: ⭐⭐ Intermediate
**Category**: General
**Source**: questions.md (Line 42)

---

### 📚 Overview

[Overview content]

### 🔑 Core Concepts

[Core concepts with 3 detailed subsections]

### 💻 Code Examples

[Basic, Advanced, and Production-Ready examples]

### ✅ Best Practices

[7 best practices with detailed explanations]

### ⚠️ Common Pitfalls

[6 common pitfalls with code examples]

---
```

### Example Output

When you run the script, you'll see output like:

```
🚀 Starting Answer Generation Process...

📂 Processing category: web-technologies
   📊 Found 35 questions
   ✅ 0 already answered
   ❓ 35 need answers
   ✨ Generated 35 answers to: interview-bank/web-technologies/answers-generated.md

📂 Processing category: cms-platforms
   📊 Found 130 questions
   ✅ 0 already answered
   ❓ 130 need answers
   ✨ Generated 130 answers to: interview-bank/cms-platforms/answers-generated.md

============================================================
📊 SUMMARY
============================================================
Total Questions Found:     735
Already Answered:          200
Missing Answers:           535
Answers Generated:         535
============================================================

✅ Successfully generated 535 comprehensive answers!
```

### Integration with Existing Workflow

1. **Review Generated Answers**: Check `answers-generated.md` files
2. **Customize**: Edit generated answers to add specific technical details
3. **Merge**: Integrate with existing answer files if needed
4. **Version Control**: Commit the generated answers to git

### Requirements

- PHP 8.0 or higher (uses typed properties and mixed type)
- Read/write access to interview-bank directories

### Notes

- The script uses intelligent question matching (both by text and number)
- Duplicate questions are automatically detected and skipped
- Generated answers are comprehensive but generic - review and customize as needed
- The script is safe to run multiple times (won't duplicate existing answers)

### Troubleshooting

**No questions found:**
- Ensure question files contain "question" in the filename
- Check that questions follow one of the supported patterns

**Answers not detected:**
- Ensure answer files contain "answer" in the filename
- Check that answered questions use similar numbering/text

**Permission errors:**
- Ensure write permissions on interview-bank directories
- Check that the automation directory is writable
