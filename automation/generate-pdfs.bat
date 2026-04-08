@echo off
REM PDF Generator Script for Interview Bank (Windows)
REM Generates PDF exports of all answer pages

setlocal enabledelayedexpansion

cd /d %~dp0\..

echo ==================================
echo Interview Bank PDF Generator
echo ==================================
echo.

REM Check if vendor directory exists
if not exist "vendor\" (
    echo Warning: Composer dependencies not found!
    echo Installing dependencies...
    call composer install
    echo.
)

REM Check if PHP is available
php --version >nul 2>&1
if errorlevel 1 (
    echo Error: PHP is not installed or not in PATH
    pause
    exit /b 1
)

REM Display PHP version
for /f "tokens=*" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
echo PHP Version: %PHP_VERSION%
echo.

REM Run the generator
if "%~1"=="" (
    echo Generating all PDFs...
    php automation\generate-pdf-answers.php
) else (
    echo Generating PDF for: %~1
    php automation\generate-pdf-answers.php %~1
)

echo.
echo ==================================
echo PDF Generation Complete!
echo ==================================
echo.
echo Output location: pdf-exports\
echo.

REM List generated files
if exist "pdf-exports\" (
    echo Generated PDFs:
    dir /b pdf-exports\*.pdf 2>nul
)

echo.
pause
