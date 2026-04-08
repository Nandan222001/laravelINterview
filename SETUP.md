# Repository Setup Guide

## Completed Setup Steps

### ✅ 1. PHP/Composer Dependencies
**Status:** Installed successfully

```bash
composer install
```

**Installed packages:**
- `tecnickcom/tcpdf` (6.11.2) - PDF generation library

**Verification:**
- ✅ `vendor/` directory created
- ✅ `composer.lock` file generated
- ✅ Autoloader configured

---

### ✅ 2. Python Virtual Environment
**Status:** Created successfully

```bash
python -m venv .venv
```

**Details:**
- Virtual environment location: `.venv/`
- Python version: 3.14.3
- Configured with system site packages support

---

### ⚠️ 3. Python Dependencies
**Status:** Requires manual installation

Due to security policies, the Python package installation could not be automated. Please run one of the following commands to complete the setup:

#### Option A: Using the provided batch file (Windows)
```cmd
install-python-deps.bat
```

#### Option B: Using the provided PowerShell script
```powershell
.\install-python-deps.ps1
```

#### Option C: Manual installation
```cmd
.venv\Scripts\python.exe -m pip install --upgrade pip
.venv\Scripts\python.exe -m pip install -r automation\requirements.txt
```

#### Option D: Using the Python setup script
```cmd
python setup_python.py
```

**Required packages:**
- `pygments>=2.10.0` - Syntax highlighting for code blocks

---

## Repository Structure

```
.
├── vendor/                    # PHP dependencies (Composer)
├── .venv/                     # Python virtual environment
├── automation/                # Automation scripts
│   ├── requirements.txt      # Python dependencies
│   └── *.php, *.py           # Scripts
├── interview-bank/           # Interview questions
├── composer.json             # PHP dependencies config
├── install-python-deps.bat   # Windows batch installer
├── install-python-deps.ps1   # PowerShell installer
└── setup_python.py           # Python installer script
```

---

## Verification

### Check PHP Setup
```bash
composer show
php --version
```

### Check Python Setup
```bash
python --version
.venv\Scripts\python.exe --version
```

### Check Python Dependencies (after manual installation)
```bash
.venv\Scripts\python.exe -c "import pygments; print(f'Pygments v{pygments.__version__} installed')"
```

---

## Git Ignore Configuration

The following are properly ignored:
- ✅ `vendor/` - PHP dependencies
- ✅ `.venv/` - Python virtual environment
- ✅ `composer.lock` - PHP lock file
- ✅ `*.pyc`, `__pycache__/` - Python bytecode
- ✅ Build and temporary files

---

## Next Steps

1. **Complete Python package installation** using one of the methods above
2. **Run tests** (once testing framework is configured)
3. **Run build** (once build process is configured)
4. **Run lint** (once linter is configured)

---

## Tech Stack

- **PHP**: 8.2.12
- **Python**: 3.14.3
- **Composer**: Package manager for PHP
- **TCPDF**: PDF generation library
- **Pygments**: Syntax highlighting for Python

---

## Notes

- This is a documentation/interview preparation repository
- Main content is in Markdown format
- PHP scripts for PDF generation
- Python scripts for automation and HTML generation
- No build process required for static content
- No test framework currently configured
- No linter currently configured
