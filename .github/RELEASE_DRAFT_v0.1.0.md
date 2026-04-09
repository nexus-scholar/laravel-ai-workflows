# v0.1.0

`nexus/laravel-ai-chain` first public release.

## Highlights
- Laravel-native AI composition layer built on top of `laravel/ai`.
- Production-hardened graph runtime with strict compile/runtime validation.
- Queue-safe graph execution with resolver-based dispatch for closure-heavy workflows.
- Fluent composition APIs for single and multi-step chain pipelines.
- Standalone tutorial examples and feature-level tutorial coverage.

## Added
- Chain composition primitives: `Chain`, `SequentialChain`, and `ChainFactory`.
- Laravel entrypoints: `AiChainManager` and `Nexus\AiChain\Facades\AiChain`.
- Graph core: `StateGraph`, `CompiledGraph`, immutable `State`, and `Node` contract.
- Queue execution path: `QueueRunner` + `RunGraphNode` with resolver-based graph reconstruction.
- Checkpointing: `CacheCheckpoint` + `Checkpointable` contract.
- Memory primitives: `InMemoryConversation`, `CacheConversationMemory`, `SummaryMemory`.
- Retrieval primitives: `VectorStoreRetriever`, `HybridRetriever`, `RerankingRetriever`.
- Package config and provider wiring: `config/ai-chain.php` + `AiChainServiceProvider` bindings.
- Tutorial examples in `examples/` and tutorial validation tests.

## Changed
- Graph compile/runtime now fail fast for invalid entry points, unknown nodes/routes, and invalid node return types.
- Queue dispatch rejects unsafe graph serialization unless a resolver key is provided.
- `Chain` aligns with `laravel/ai` `Agent` contract and supports provider/model overrides.
- Checkpoint/history and cached memory payloads are normalized for safer runtime behavior.

## Fixed
- Replaced stale alias reference with a real, package-backed facade alias.
- Improved edge-case handling in `SequentialChain`, memory summarization, and retrieval normalization.

## Quality / Verification
- Package tests: `66 passed` (`143 assertions`).
- Tutorial scripts in `examples/` executed successfully end-to-end.

## Upgrade Notes
- See `UPGRADE.md` for migration details from local/pre-release snapshots.

## Quick Verification Commands
```bash
vendor/bin/pest
php examples/01-basic-chain.php
php examples/02-chain-with-memory.php
php examples/03-chain-with-retrieval.php
php examples/04-state-graph-workflow.php
php examples/05-manager-and-factory.php
php examples/fluent-chain-factory.php
```

## References
- Changelog: `CHANGELOG.md`
- Upgrade Guide: `UPGRADE.md`
- Release Checklist: `RELEASE_CHECKLIST.md`
- Tutorial Path: `docs/laravel-ai-chain/tutorials/beginner-to-advanced.md`

