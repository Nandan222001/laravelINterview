<?php

/**
 * Generate DUPLICATE_REVIEW.md for medium-confidence duplicates (85-95% similarity)
 */

$jsonFile = __DIR__ . '/../php-laravel-api-security-deduplication-report.json';
$outputFile = __DIR__ . '/../interview-bank/php-laravel-api-security/DUPLICATE_REVIEW.md';

if (!file_exists($jsonFile)) {
    die("Error: Deduplication report not found at: $jsonFile\n");
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!$data) {
    die("Error: Could not parse JSON file\n");
}

// Filter duplicates with 85-95% similarity
$mediumConfidenceDuplicates = array_filter($data['duplicates'], function($duplicate) {
    $score = $duplicate['similarity']['average_score'];
    return $score >= 85 && $score < 95;
});

// Sort by similarity score (descending)
usort($mediumConfidenceDuplicates, function($a, $b) {
    return $b['similarity']['average_score'] <=> $a['similarity']['average_score'];
});

// Generate markdown content
$markdown = generateMarkdown($mediumConfidenceDuplicates, $data['metadata'], $data['statistics']);

// Ensure directory exists
$dir = dirname($outputFile);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

file_put_contents($outputFile, $markdown);

echo "Successfully generated DUPLICATE_REVIEW.md\n";
echo "Location: $outputFile\n";
echo "Medium-confidence duplicates found: " . count($mediumConfidenceDuplicates) . "\n";

/**
 * Generate the markdown content
 */
function generateMarkdown(array $duplicates, array $metadata, array $statistics): string
{
    $count = count($duplicates);
    
    $md = "# Duplicate Review Report\n\n";
    $md .= "## Overview\n\n";
    $md .= "This document contains all **medium-confidence duplicates** (85-95% similarity) that require manual review.\n\n";
    $md .= "- **Generated:** {$metadata['generated_at']}\n";
    $md .= "- **Similarity Threshold:** {$metadata['similarity_threshold']}%\n";
    $md .= "- **Medium-Confidence Duplicates:** {$count}\n";
    $md .= "- **Similarity Range:** 85% - 94.99%\n\n";
    
    $md .= "### Similarity Distribution\n\n";
    $md .= "| Range | Count |\n";
    $md .= "|-------|-------|\n";
    foreach ($statistics['similarity_distribution'] as $range => $rangeCount) {
        $md .= "| {$range} | {$rangeCount} |\n";
    }
    $md .= "\n";
    
    $md .= "## Instructions\n\n";
    $md .= "For each duplicate pair below:\n\n";
    $md .= "1. **Review** both questions side-by-side\n";
    $md .= "2. **Check** the similarity scores and recommendation\n";
    $md .= "3. **Decide** on the appropriate action:\n";
    $md .= "   - ✅ **Merge**: Questions are duplicates and should be merged\n";
    $md .= "   - ✅ **Keep Both**: Questions are different enough to keep separately\n";
    $md .= "   - ✅ **Refine**: Questions overlap but need rewording to distinguish them\n\n";
    
    $md .= "---\n\n";
    $md .= "## Duplicate Pairs\n\n";
    
    foreach ($duplicates as $index => $dup) {
        $num = $index + 1;
        $md .= generateDuplicatePair($num, $dup);
        $md .= "\n---\n\n";
    }
    
    $md .= "## Summary\n\n";
    $md .= "- Total medium-confidence duplicates reviewed: **{$count}**\n";
    $md .= "- Review completion: [ ] Not started [ ] In progress [ ] Complete\n\n";
    $md .= "### Decision Summary\n\n";
    $md .= "Track your decisions here:\n\n";
    $md .= "- Merge decisions: _____ / {$count}\n";
    $md .= "- Keep both decisions: _____ / {$count}\n";
    $md .= "- Refine decisions: _____ / {$count}\n\n";
    
    return $md;
}

/**
 * Generate markdown for a single duplicate pair
 */
function generateDuplicatePair(int $num, array $dup): string
{
    $q1 = $dup['question_1'];
    $q2 = $dup['question_2'];
    $sim = $dup['similarity'];
    $type = $dup['type'];
    
    $md = "### Pair #{$num}\n\n";
    
    // Similarity scores
    $md .= "**Similarity Score:** {$sim['average_score']}%\n\n";
    $md .= "| Metric | Score |\n";
    $md .= "|--------|-------|\n";
    $md .= "| Similar Text | {$sim['similar_text_percent']}% |\n";
    $md .= "| Levenshtein | {$sim['levenshtein_percent']}% |\n";
    $md .= "| Average | **{$sim['average_score']}%** |\n\n";
    
    // Question comparison
    $md .= "#### Side-by-Side Comparison\n\n";
    $md .= "<table>\n";
    $md .= "<tr>\n";
    $md .= "<th>Question A</th>\n";
    $md .= "<th>Question B</th>\n";
    $md .= "</tr>\n";
    $md .= "<tr>\n";
    $md .= "<td>\n\n";
    $md .= formatQuestion($q1);
    $md .= "\n</td>\n";
    $md .= "<td>\n\n";
    $md .= formatQuestion($q2);
    $md .= "\n</td>\n";
    $md .= "</tr>\n";
    $md .= "</table>\n\n";
    
    // Metadata
    $md .= "#### Metadata\n\n";
    $md .= "- **Type:** " . ucfirst(str_replace('_', ' ', $type)) . "\n";
    $md .= "- **Question A Source:** {$q1['source']}\n";
    if (isset($q1['source_file'])) {
        $md .= " (Line {$q1['line_number']})\n";
    } else {
        $md .= " (New - Line {$q1['line_number']})\n";
    }
    $md .= "- **Question B Source:** {$q2['source']}\n";
    if (isset($q2['source_file'])) {
        $md .= " (Line {$q2['line_number']})\n";
    } else {
        $md .= " (New - Line {$q2['line_number']})\n";
    }
    $md .= "- **Recommendation:** {$dup['recommendation']}\n\n";
    
    // Decision checkboxes
    $md .= "#### Decision\n\n";
    $md .= "- [ ] **Merge** - These are duplicates, merge into one question\n";
    $md .= "- [ ] **Keep Both** - These are distinct questions, keep both\n";
    $md .= "- [ ] **Refine** - These overlap, but need rewording to clarify differences\n\n";
    
    $md .= "**Notes:**\n\n";
    $md .= "```\n";
    $md .= "(Add your review notes here)\n";
    $md .= "```\n\n";
    
    return $md;
}

/**
 * Format a question for display
 */
function formatQuestion(array $question): string
{
    $md = "**ID:** {$question['id']}\n\n";
    $md .= "**Question:**\n\n";
    $md .= "> {$question['text']}\n\n";
    $md .= "**Normalized:**\n\n";
    $md .= "`{$question['normalized']}`\n\n";
    
    return $md;
}
