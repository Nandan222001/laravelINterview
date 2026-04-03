#!/usr/bin/env python3
"""
Consolidates and standardizes all interview questions to numbered format.
Converts various markdown formats to uniform "1. Question text" format.
"""

import os
import re
import sys
from pathlib import Path

def extract_numbered_questions(text):
    """Extract questions that are already in '1. Text' format"""
    lines = text.split('\n')
    questions = {}
    current_num = None
    current_text = ''
    
    for line in lines:
        match = re.match(r'^\s*(\d+)\.\s+(.*)', line)
        if match:
            if current_num is not None and current_text.strip():
                questions[current_num] = current_text.strip()
            current_num = match.group(1)
            current_text = match.group(2)
        elif current_num is not None:
            if line.strip():
                current_text += '\n' + line
    
    if current_num is not None and current_text.strip():
        questions[current_num] = current_text.strip()
    
    return questions

def extract_heading_format_questions(text):
    """Extract questions in '### 1. Question' or '#### Question 1:' or '### Question 1:' format"""
    lines = text.split('\n')
    questions = {}
    current_num = None
    current_text = ''
    
    for i, line in enumerate(lines):
        # Pattern: ### 123. Text or #### 123. Text
        match = re.match(r'^\s*#+\s*(\d+)\.\s+(.*)', line)
        if match:
            # Save previous question
            if current_num is not None and current_text.strip():
                questions[current_num] = current_text.strip()
            
            current_num = match.group(1)
            current_text = match.group(2).strip()
            continue
        
        # Pattern: ### Question 123: or #### Question 123: or ### Implement custom metadata
        match = re.match(r'^\s*#+\s*(?:Question\s+)?(\d+)\.\s*(.+?)(?:\s|:|$)', line)
        if match:
            # Save previous question
            if current_num is not None and current_text.strip():
                questions[current_num] = current_text.strip()
            
            current_num = match.group(1)
            current_text = match.group(2).strip()
            continue
        
        # Alternative pattern: ### [SECTION] or ### Question 1: Implement...
        match = re.match(r'^\s*###\s+(\d+)\.\s+(.+)', line)
        if match:
            # Save previous question
            if current_num is not None and current_text.strip():
                questions[current_num] = current_text.strip()
            
            current_num = match.group(1)
            current_text = match.group(2).strip()
            continue
        
        # Continuation of current question
        if current_num is not None and line.strip():
            if not current_text:
                current_text = line.strip()
            else:
                # Stop at next heading or code block start
                if re.match(r'^#+\s', line) or line.strip().startswith('```'):
                    questions[current_num] = current_text.strip()
                    current_num = None
                    current_text = ''
                else:
                    current_text += ' ' + line.strip()
    
    # Save last question
    if current_num is not None and current_text.strip():
        questions[current_num] = current_text.strip()
    
    return questions

def extract_all_questions(content):
    """Extract questions from various formats"""
    # Try numbered format first
    questions = extract_numbered_questions(content)
    if not questions:
        # Try heading format
        questions = extract_heading_format_questions(content)
    return questions

def merge_question_files(topic_dir):
    """Merge multiple question files in a topic directory"""
    all_questions = {}
    
    # List of possible question file patterns (ordered by priority)
    patterns = [
        'questions.md',              # Main questions file
        'questions_part*.md',        # Part-based files
        'questions_realtime*.md',    # Realtime-specific
        '[0-9]*-*.md',              # Numbered files like 01-react-18.md
    ]
    
    files_to_process = []
    
    # Process in order of patterns
    for pattern in patterns:
        if '*' in pattern:
            files_to_process.extend(sorted(Path(topic_dir).glob(pattern)))
        else:
            main_file = Path(topic_dir) / pattern
            if main_file.exists():
                files_to_process.append(main_file)
    
    # Remove duplicates while preserving order, exclude non-question files
    seen = set()
    unique_files = []
    exclude_patterns = ['answers', 'code-examples', 'README', 'INDEX', 'IMPLEMENTATION']
    
    for f in files_to_process:
        if any(exclude in f.name for exclude in exclude_patterns):
            continue
        if str(f) not in seen:
            seen.add(str(f))
            unique_files.append(f)
    
    for file_path in unique_files:
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            questions = extract_all_questions(content)
            for num, text in questions.items():
                if num not in all_questions:  # Don't overwrite existing numbers
                    all_questions[num] = text
            
            print(f"✓ Processed {file_path.name}: {len(questions)} questions")
        except Exception as e:
            print(f"✗ Error processing {file_path.name}: {e}")
    
    return all_questions

def write_consolidated_questions(topic_dir, questions):
    """Write consolidated questions to questions.md"""
    if not questions:
        print(f"⚠ No questions found for {topic_dir}")
        return False
    
    # Sort by question number
    sorted_questions = sorted(questions.items(), key=lambda x: int(x[0]))
    
    output_file = Path(topic_dir) / 'questions.md'
    
    with open(output_file, 'w', encoding='utf-8') as f:
        topic_name = Path(topic_dir).name.replace('-', ' ').title()
        f.write(f"# {topic_name} Interview Questions\n\n")
        
        for num, text in sorted_questions:
            f.write(f"{num}. {text}\n\n")
    
    print(f"✓ Wrote {len(sorted_questions)} questions to {output_file.name}")
    return True

def main():
    base_dir = Path('/home/neosoft/Desktop/nandan/laravelINterview/interview-bank')
    
    topics = [d for d in base_dir.iterdir() if d.is_dir()]
    
    for topic_dir in sorted(topics):
        topic_name = topic_dir.name
        print(f"\n📚 Processing: {topic_name}")
        print("=" * 60)
        
        questions = merge_question_files(topic_dir)
        
        if questions:
            write_consolidated_questions(topic_dir, questions)
            print(f"📊 Total: {len(questions)} questions consolidated")
        else:
            print(f"⚠ No questions found in {topic_name}")

if __name__ == '__main__':
    main()
