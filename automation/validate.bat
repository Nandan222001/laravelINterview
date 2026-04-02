@echo off
echo ============================================
echo  Interview Bank - Validation Only
echo ============================================
echo.

php "%~dp0run-validation.php"

if %errorlevel% neq 0 (
    echo.
    echo Validation found errors!
    pause
    exit /b 1
)

echo.
echo Validation complete! Check output folder.
pause
