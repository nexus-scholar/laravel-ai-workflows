#!/bin/bash
# Run Laravel AI Chain tests
# Validates core functionality

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)"

echo "🧪 Running Laravel AI Chain Tests"
echo

cd "$PROJECT_ROOT"

if [ ! -f "vendor/bin/pest" ]; then
    echo "❌ Pest not installed. Run: composer install"
    exit 1
fi

echo "Running Pest test suite..."
./vendor/bin/pest

echo
echo "✅ Tests passed"

