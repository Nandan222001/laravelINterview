@echo off
echo ================================================
echo Interview Bank - HTML Answer Generator
echo ================================================
echo.
echo Checking Python installation...
python --version
if errorlevel 1 (
    echo.
    echo ERROR: Python not found. Please install Python 3.7 or higher.
    echo Download from: https://www.python.org/downloads/
    pause
    exit /b 1
)

echo.
echo Checking Pygments installation...
python -c "import pygments" 2>nul
if errorlevel 1 (
    echo.
    echo Pygments not installed. Installing now...
    python -m pip install pygments
    if errorlevel 1 (
        echo.
        echo ERROR: Failed to install Pygments. Please install manually:
        echo pip install pygments
        pause
        exit /b 1
    )
)

echo.
echo ================================================
echo Starting HTML answer generation...
echo ================================================
echo.

python automation/generate-html-answers.py

if errorlevel 1 (
    echo.
    echo ERROR: Answer generation failed.
    pause
    exit /b 1
)

echo.
echo ================================================
echo Success! HTML file generated.
echo Open: automation/output/comprehensive-answers.html
echo ================================================
pause
