@echo off
echo ============================================
echo  Interview Bank - Complete Automation
echo ============================================
echo.

php "%~dp0run-all.php"

if %errorlevel% neq 0 (
    echo.
    echo Automation completed with errors!
    pause
    exit /b 1
)

echo.
echo All automation tasks complete!
echo.
echo Opening dashboards...

start "" "%~dp0output\statistics-dashboard.html"
timeout /t 2 /nobreak >nul
start "" "%~dp0output\search.html"

pause
