# Upgrade Guide

This guide documents migration notes for users moving from pre-release/local snapshots to the current public-ready package shape.

## 0.x pre-release -> 0.1.0

### 1) Package entrypoints
- Preferred Laravel entrypoint is `AiChainManager` from the container:
  - `app(Nexus\AiChain\AiChainManager::class)`
- Optional alias is available via package discovery:
  - `AiChain` -> `Nexus\AiChain\Facades\AiChain`

### 2) Chain composition
- `Chain` remains compatible with prior usage.
- New additive APIs:
  - `Chain::compose(...)`
  - `ChainFactory::chain(...)->then(...)->build()`
  - `$chain->then($next)`

### 3) Graph runtime strictness
- Graph compile/invoke is stricter and now throws earlier for invalid configurations:
  - missing/unknown entry points
  - invalid edge destinations
  - unknown next-node routes
  - node handlers that do not return `State`

### 4) Queue graph safety
- Queue dispatch enforces graph serialization safety.
- If a graph contains closure-based nodes/edges, provide a resolver key through `QueueRunner` so workers re-resolve the graph from the container.

### 5) Config and defaults
- New/standardized config file: `config/ai-chain.php`.
- Publish config in Laravel apps:
  - `php artisan vendor:publish --tag=ai-chain-config`

### 6) Memory/retrieval behavior
- Cache/snapshot payloads are now normalized for safer runtime handling.
- Retrieval layers apply stricter filtering for malformed/empty documents.

If your app relied on permissive behavior, add tests for your custom node/retriever contracts and update implementations to satisfy stricter invariants.

