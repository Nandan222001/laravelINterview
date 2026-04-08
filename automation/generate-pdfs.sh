#!/bin/bash
# PDF Generator Script for Interview Bank
# Generates PDF exports of all answer pages

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

echo "=================================="
echo "Interview Bank PDF Generator"
echo "=================================="
echo ""

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "⚠️  Composer dependencies not found!"
    echo "Installing dependencies..."
    composer install
    echo ""
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ Error: PHP is not installed or not in PATH"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "✓ PHP Version: $PHP_VERSION"
echo ""

# Run the generator
if [ -z "$1" ]; then
    echo "Generating all PDFs..."
    php automation/generate-pdf-answers.php
else
    echo "Generating PDF for: $1"
    php automation/generate-pdf-answers.php "$1"
fi

echo ""
echo "=================================="
echo "✓ PDF Generation Complete!"
echo "=================================="
echo ""
echo "Output location: pdf-exports/"
echo ""

# List generated files
if [ -d "pdf-exports" ] && [ "$(ls -A pdf-exports 2>/dev/null)" ]; then
    echo "Generated PDFs:"
    ls -lh pdf-exports/*.pdf | awk '{print "  - " $9 " (" $5 ")"}'
fi

echo ""
