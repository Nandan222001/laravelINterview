@echo off
REM Install Python dependencies for Interview Bank
REM Run this script to complete Python package installation

echo Installing Python dependencies...
.venv\Scripts\python.exe -m pip install --upgrade pip
.venv\Scripts\python.exe -m pip install -r automation\requirements.txt

echo.
echo Python dependencies installed successfully!
echo Virtual environment is located at: .venv
pause
