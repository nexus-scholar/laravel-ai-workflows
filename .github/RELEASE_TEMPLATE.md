# Release {{version}}

> Date: {{date}}

## Highlights
- Laravel-native AI composition layer on top of `laravel/ai`.
- Production-hardened graph runtime and queue-safe execution paths.
- Fluent chain composition via `Chain`, `SequentialChain`, and `ChainFactory`.

## Added
- Chain composition primitives (`Chain`, `SequentialChain`, `ChainFactory`).
- Laravel manager/facade entrypoints (`AiChainManager`, `AiChain`).
- Graph orchestration core (`StateGraph`, `CompiledGraph`, immutable `State`).
- Queue execution with resolver-aware dispatch (`QueueRunner`, `RunGraphNode`).
- Checkpointing (`CacheCheckpoint`) and normalized memory/retrieval primitives.

## Changed
- Stricter graph compile/runtime validation (entry points, edges, routes, node return types).
- Queue dispatch now guards unsafe graph serialization and supports resolver-based reconstruction.
- `Chain` aligned to `laravel/ai` `Agent` contract with provider/model overrides.

## Fixed
- Package alias drift resolved via real facade implementation.
- Edge-case handling improved across checkpointing, memory summarization, and retrieval normalization.

## Quality
- Full package test suite passing.
- Tutorial/usage examples in `examples/` verified to run standalone.

## Upgrade Notes
See `UPGRADE.md` for migration details.

## Verification Commands
```bash
vendor/bin/pest
php examples/01-basic-chain.php
php examples/02-chain-with-memory.php
php examples/03-chain-with-retrieval.php
php examples/04-state-graph-workflow.php
php examples/05-manager-and-factory.php
php examples/fluent-chain-factory.php
```

## Links
- Changelog: `CHANGELOG.md`
- Upgrade guide: `UPGRADE.md`
- Release checklist: `RELEASE_CHECKLIST.md`

