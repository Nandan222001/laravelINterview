#!/bin/bash

echo "============================================"
echo " Interview Bank - Complete Automation"
echo "============================================"
echo ""

php "$(dirname "$0")/run-all.php"

if [ $? -ne 0 ]; then
    echo ""
    echo "Automation completed with errors!"
    exit 1
fi

echo ""
echo "All automation tasks complete!"
echo ""
echo "Opening dashboards..."

# Try to open HTML files in default browser
if command -v xdg-open > /dev/null; then
    xdg-open "$(dirname "$0")/output/statistics-dashboard.html" &
    sleep 1
    xdg-open "$(dirname "$0")/output/search.html" &
elif command -v open > /dev/null; then
    open "$(dirname "$0")/output/statistics-dashboard.html"
    sleep 1
    open "$(dirname "$0")/output/search.html"
else
    echo "Please manually open:"
    echo "  - output/statistics-dashboard.html"
    echo "  - output/search.html"
fi
