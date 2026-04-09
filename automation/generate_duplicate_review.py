#!/usr/bin/env python3
"""
Generate DUPLICATE_REVIEW.md for medium-confidence duplicates (85-95% similarity)
"""

import json
import os
from pathlib import Path

# File paths
json_file = Path(__file__).parent.parent / 'php-laravel-api-security-deduplication-report.json'
output_file = Path(__file__).parent.parent / 'interview-bank' / 'php-laravel-api-security' / 'DUPLICATE_REVIEW.md'

# Load JSON data
with open(json_file, 'r', encoding='utf-8') as f:
    data = json.load(f)

# Filter duplicates with 85-95% similarity
medium_confidence_duplicates = [
    dup for dup in data['duplicates']
    if 85 <= dup['similarity']['average_score'] < 95
]

# Sort by similarity score (descending)
medium_confidence_duplicates.sort(
    key=lambda x: x['similarity']['average_score'],
    reverse=True
)

def format_question(question):
    """Format a question for display"""
    md = f"**ID:** {question['id']}\n\n"
    md += f"**Question:**\n\n"
    md += f"> {question['text']}\n\n"
    md += f"**Normalized:**\n\n"
    md += f"`{question['normalized']}`\n\n"
    return md

def generate_duplicate_pair(num, dup):
    """Generate markdown for a single duplicate pair"""
    q1 = dup['question_1']
    q2 = dup['question_2']
    sim = dup['similarity']
    dup_type = dup['type']
    
    md = f"### Pair #{num}\n\n"
    
    # Similarity scores
    md += f"**Similarity Score:** {sim['average_score']:.2f}%\n\n"
    md += "| Metric | Score |\n"
    md += "|--------|-------|\n"
    md += f"| Similar Text | {sim['similar_text_percent']:.2f}% |\n"
    md += f"| Levenshtein | {sim['levenshtein_percent']:.2f}% |\n"
    md += f"| Average | **{sim['average_score']:.2f}%** |\n\n"
    
    # Question comparison
    md += "#### Side-by-Side Comparison\n\n"
    md += "<table>\n"
    md += "<tr>\n"
    md += "<th>Question A</th>\n"
    md += "<th>Question B</th>\n"
    md += "</tr>\n"
    md += "<tr>\n"
    md += "<td>\n\n"
    md += format_question(q1)
    md += "\n</td>\n"
    md += "<td>\n\n"
    md += format_question(q2)
    md += "\n</td>\n"
    md += "</tr>\n"
    md += "</table>\n\n"
    
    # Metadata
    md += "#### Metadata\n\n"
    md += f"- **Type:** {dup_type.replace('_', ' ').title()}\n"
    md += f"- **Question A Source:** {q1['source']}"
    if 'source_file' in q1:
        md += f" (Line {q1['line_number']})\n"
    else:
        md += f" (New - Line {q1['line_number']})\n"
    md += f"- **Question B Source:** {q2['source']}"
    if 'source_file' in q2:
        md += f" (Line {q2['line_number']})\n"
    else:
        md += f" (New - Line {q2['line_number']})\n"
    md += f"- **Recommendation:** {dup['recommendation']}\n\n"
    
    # Decision checkboxes
    md += "#### Decision\n\n"
    md += "- [ ] **Merge** - These are duplicates, merge into one question\n"
    md += "- [ ] **Keep Both** - These are distinct questions, keep both\n"
    md += "- [ ] **Refine** - These overlap, but need rewording to clarify differences\n\n"
    
    md += "**Notes:**\n\n"
    md += "```\n"
    md += "(Add your review notes here)\n"
    md += "```\n\n"
    
    return md

# Generate markdown content
count = len(medium_confidence_duplicates)
metadata = data['metadata']
statistics = data['statistics']

markdown = f"# Duplicate Review Report\n\n"
markdown += f"## Overview\n\n"
markdown += f"This document contains all **medium-confidence duplicates** (85-95% similarity) that require manual review.\n\n"
markdown += f"- **Generated:** {metadata['generated_at']}\n"
markdown += f"- **Similarity Threshold:** {metadata['similarity_threshold']}%\n"
markdown += f"- **Medium-Confidence Duplicates:** {count}\n"
markdown += f"- **Similarity Range:** 85% - 94.99%\n\n"

markdown += f"### Similarity Distribution\n\n"
markdown += f"| Range | Count |\n"
markdown += f"|-------|-------|\n"
for range_name, range_count in statistics['similarity_distribution'].items():
    markdown += f"| {range_name} | {range_count} |\n"
markdown += f"\n"

markdown += f"## Instructions\n\n"
markdown += f"For each duplicate pair below:\n\n"
markdown += f"1. **Review** both questions side-by-side\n"
markdown += f"2. **Check** the similarity scores and recommendation\n"
markdown += f"3. **Decide** on the appropriate action:\n"
markdown += f"   - ✅ **Merge**: Questions are duplicates and should be merged\n"
markdown += f"   - ✅ **Keep Both**: Questions are different enough to keep separately\n"
markdown += f"   - ✅ **Refine**: Questions overlap but need rewording to distinguish them\n\n"

markdown += f"---\n\n"
markdown += f"## Duplicate Pairs\n\n"

for index, dup in enumerate(medium_confidence_duplicates):
    num = index + 1
    markdown += generate_duplicate_pair(num, dup)
    markdown += "\n---\n\n"

markdown += f"## Summary\n\n"
markdown += f"- Total medium-confidence duplicates reviewed: **{count}**\n"
markdown += f"- Review completion: [ ] Not started [ ] In progress [ ] Complete\n\n"
markdown += f"### Decision Summary\n\n"
markdown += f"Track your decisions here:\n\n"
markdown += f"- Merge decisions: _____ / {count}\n"
markdown += f"- Keep both decisions: _____ / {count}\n"
markdown += f"- Refine decisions: _____ / {count}\n\n"

# Ensure directory exists
output_file.parent.mkdir(parents=True, exist_ok=True)

# Write to file
with open(output_file, 'w', encoding='utf-8') as f:
    f.write(markdown)

print(f"Successfully generated DUPLICATE_REVIEW.md")
print(f"Location: {output_file}")
print(f"Medium-confidence duplicates found: {count}")
