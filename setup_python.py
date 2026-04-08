#!/usr/bin/env python3
"""
Setup script to install Python dependencies in the virtual environment
"""
import subprocess
import sys
import os
from pathlib import Path

def main():
    venv_python = Path(".venv") / "Scripts" / "python.exe"
    
    if not venv_python.exists():
        print(f"Error: Virtual environment not found at {venv_python}")
        sys.exit(1)
    
    print("Upgrading pip...")
    subprocess.check_call([str(venv_python), "-m", "pip", "install", "--upgrade", "pip"])
    
    print("\nInstalling dependencies from requirements.txt...")
    subprocess.check_call([str(venv_python), "-m", "pip", "install", "-r", "automation/requirements.txt"])
    
    print("\n✅ Python dependencies installed successfully!")
    print(f"Virtual environment: {venv_python.parent.parent}")

if __name__ == "__main__":
    main()
