#!/bin/bash
# Validate Laravel AI Chain skill implementation
# Checks SKILL.md frontmatter and directory structure

set -e

SKILL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SKILL_FILE="$SKILL_DIR/SKILL.md"

echo "🔍 Validating Laravel AI Chain Skill..."
echo

# Check if SKILL.md exists
if [ ! -f "$SKILL_FILE" ]; then
    echo "❌ SKILL.md not found at $SKILL_FILE"
    exit 1
fi

echo "✅ SKILL.md found"

# Check YAML frontmatter
if ! head -1 "$SKILL_FILE" | grep -q "^---"; then
    echo "❌ Missing YAML frontmatter start (---)"
    exit 1
fi

echo "✅ YAML frontmatter found"

# Check required fields
for field in "name" "description"; do
    if ! grep -q "^$field:" "$SKILL_FILE"; then
        echo "❌ Missing required field: $field"
        exit 1
    fi
    echo "✅ Field '$field' present"
done

# Check name field format
NAME=$(grep "^name:" "$SKILL_FILE" | cut -d' ' -f2- | tr -d '"' | tr -d "'")
if [[ ! "$NAME" =~ ^[a-z0-9]([a-z0-9-]*[a-z0-9])?$ ]]; then
    echo "❌ Invalid name format: $NAME"
    echo "   Must be lowercase letters, numbers, hyphens only"
    echo "   Must not start/end with hyphen"
    exit 1
fi

echo "✅ Name format valid: $NAME"

# Check directory structure
DIRS_TO_CHECK=("references" "scripts" "assets")
for dir in "${DIRS_TO_CHECK[@]}"; do
    if [ -d "$SKILL_DIR/$dir" ]; then
        echo "✅ Directory found: $dir/"
    fi
done

echo
echo "🎉 Validation passed!"
echo "Skill: $NAME"

