#!/bin/bash

echo "============================================"
echo " Interview Bank - Validation Only"
echo "============================================"
echo ""

php "$(dirname "$0")/run-validation.php"

if [ $? -ne 0 ]; then
    echo ""
    echo "Validation found errors!"
    exit 1
fi

echo ""
echo "Validation complete! Check output folder."
