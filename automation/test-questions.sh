#!/bin/bash
# Test script to verify all questions are properly accessible

echo "🧪 Interview Platform Quality Assurance Test Suite"
echo "=================================================="
echo ""

BASE_DIR="/home/neosoft/Desktop/nandan/laravelINterview/interview-bank"
TOTAL_QUESTIONS=0
TOTAL_ANSWERS=0
FAILED_TOPICS=()

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

test_topic() {
    local topic_dir="$1"
    local topic_name=$(basename "$topic_dir")
    
    echo "📋 Testing: $topic_name"
    
    # Check questions.md exists
    if [ ! -f "$topic_dir/questions.md" ]; then
        echo -e "  ${RED}✗ questions.md not found${NC}"
        FAILED_TOPICS+=("$topic_name")
        return
    fi
    
    # Count questions
    local q_count=$(grep -c "^[0-9]\+\. " "$topic_dir/questions.md" 2>/dev/null || echo "0")
    
    if [ "$q_count" -eq 0 ]; then
        echo -e "  ${RED}✗ No questions found in proper format${NC}"
        FAILED_TOPICS+=("$topic_name")
        return
    fi
    
    echo -e "  ${GREEN}✓ Questions: $q_count${NC}"
    TOTAL_QUESTIONS=$((TOTAL_QUESTIONS + q_count))
    
    # Count answers (may have multiple files)
    local a_count=0
    for answer_file in "$topic_dir"/answers*.md; do
        if [ -f "$answer_file" ]; then
            local count=$(grep -c "^[0-9]\+\. " "$answer_file" 2>/dev/null || echo "0")
            a_count=$((a_count + count))
        fi
    done
    
    if [ "$a_count" -gt 0 ]; then
        echo -e "  ${GREEN}✓ Answers: $a_count${NC}"
        TOTAL_ANSWERS=$((TOTAL_ANSWERS + a_count))
    else
        echo -e "  ${YELLOW}⚠ No answers found${NC}"
    fi
    
    # Check for code-examples
    if [ -d "$topic_dir/code-examples" ]; then
        local code_files=$(find "$topic_dir/code-examples" -type f 2>/dev/null | wc -l)
        echo -e "  ${GREEN}✓ Code examples: $code_files files${NC}"
    else
        echo -e "  ${YELLOW}⚠ No code-examples directory${NC}"
    fi
    
    echo ""
}

# Run tests for all topics
for topic_dir in "$BASE_DIR"/*; do
    if [ -d "$topic_dir" ]; then
        test_topic "$topic_dir"
    fi
done

# Summary
echo "=================================================="
echo "📊 Test Summary"
echo "=================================================="
echo -e "${GREEN}Total Questions: $TOTAL_QUESTIONS${NC}"
echo -e "${GREEN}Total Answers: $TOTAL_ANSWERS${NC}"

if [ ${#FAILED_TOPICS[@]} -gt 0 ]; then
    echo -e "${RED}Failed Topics:${NC}"
    for topic in "${FAILED_TOPICS[@]}"; do
        echo -e "  ${RED}• $topic${NC}"
    done
else
    echo -e "${GREEN}✓ All topics passed basic checks${NC}"
fi

# Calculate coverage
if [ "$TOTAL_QUESTIONS" -gt 0 ]; then
    COVERAGE=$((TOTAL_ANSWERS * 100 / TOTAL_QUESTIONS))
    echo ""
    echo "📈 Answer Coverage: ${COVERAGE}% ($TOTAL_ANSWERS / $TOTAL_QUESTIONS)"
    
    if [ "$COVERAGE" -lt 30 ]; then
        echo -e "${RED}⚠ Low answer coverage - should target 50%+${NC}"
    elif [ "$COVERAGE" -lt 50 ]; then
        echo -e "${YELLOW}⚠ Moderate answer coverage - can be improved${NC}"
    else
        echo -e "${GREEN}✓ Good answer coverage${NC}"
    fi
fi

echo ""
echo "✅ Test suite complete!"
