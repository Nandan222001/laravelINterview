# Install Python dependencies for Interview Bank
# Run this script to complete Python package installation

Write-Host "Installing Python dependencies..." -ForegroundColor Cyan

& .\.venv\Scripts\python.exe -m pip install --upgrade pip
& .\.venv\Scripts\python.exe -m pip install -r automation\requirements.txt

Write-Host ""
Write-Host "Python dependencies installed successfully!" -ForegroundColor Green
Write-Host "Virtual environment is located at: .venv" -ForegroundColor Yellow
