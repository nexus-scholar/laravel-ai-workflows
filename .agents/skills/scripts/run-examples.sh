#!/bin/bash
# Run Laravel AI Chain examples
# Demonstrates core capabilities

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)"

echo "🚀 Running Laravel AI Chain Examples"
echo

cd "$PROJECT_ROOT"

EXAMPLES=(
    "examples/01-basic-chain.php"
    "examples/02-chain-with-memory.php"
    "examples/03-chain-with-retrieval.php"
    "examples/04-state-graph-workflow.php"
)

for example in "${EXAMPLES[@]}"; do
    if [ -f "$example" ]; then
        echo "▶️  Running: $example"
        php "$example" || echo "⚠️  Example failed (may require configuration)"
        echo
    fi
done

echo "✅ Examples complete"

