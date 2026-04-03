#!/bin/bash

echo "================================================"
echo "Interview Bank - HTML Answer Generator"
echo "================================================"
echo ""
echo "Checking Python installation..."

if ! command -v python3 &> /dev/null
then
    if ! command -v python &> /dev/null
    then
        echo ""
        echo "ERROR: Python not found. Please install Python 3.7 or higher."
        echo "Visit: https://www.python.org/downloads/"
        exit 1
    fi
    PYTHON_CMD="python"
else
    PYTHON_CMD="python3"
fi

$PYTHON_CMD --version

echo ""
echo "Checking Pygments installation..."
$PYTHON_CMD -c "import pygments" 2>/dev/null
if [ $? -ne 0 ]; then
    echo ""
    echo "Pygments not installed. Installing now..."
    $PYTHON_CMD -m pip install pygments
    if [ $? -ne 0 ]; then
        echo ""
        echo "ERROR: Failed to install Pygments. Please install manually:"
        echo "pip install pygments"
        exit 1
    fi
fi

echo ""
echo "================================================"
echo "Starting HTML answer generation..."
echo "================================================"
echo ""

$PYTHON_CMD automation/generate-html-answers.py

if [ $? -ne 0 ]; then
    echo ""
    echo "ERROR: Answer generation failed."
    exit 1
fi

echo ""
echo "================================================"
echo "Success! HTML file generated."
echo "Open: automation/output/comprehensive-answers.html"
echo "================================================"
