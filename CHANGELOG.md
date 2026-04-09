# Changelog

All notable changes to `nexus/laravel-ai-workflows` are documented in this file.

## [0.1.0] - 2026-04-09

### Added
- Chain composition primitives: `Chain`, `SequentialChain`, and `ChainFactory` for fluent composition.
- Laravel-facing manager/facade entrypoints: `AiChainManager` and `Nexus\\Workflow\Facades\AiChain`.
- Graph orchestration core: `StateGraph`, `CompiledGraph`, immutable `State`, and `Node` contract.
- Queue graph runner path: `QueueRunner` + `RunGraphNode` with resolver-based graph reconstruction.
- Checkpointing via `CacheCheckpoint` and `Checkpointable` contract.
- Memory primitives: `InMemoryConversation`, `CacheConversationMemory`, and `SummaryMemory`.
- Retrieval primitives: `VectorStoreRetriever`, `HybridRetriever` (RRF), and `RerankingRetriever`.
- Package config and provider wiring: `config/ai-chain.php` + `AiChainServiceProvider` bindings.

### Changed
- Graph compile/runtime now fail fast on invalid entry points, unknown nodes/routes, and invalid node return types.
- Queue dispatch now rejects unsafe serialized graphs unless a graph resolver key is provided.
- `Chain` aligns with `laravel/ai` `Agent` contract and supports provider/model overrides.
- Checkpoint/history loading and cached memory payloads are normalized to avoid malformed state.

### Fixed
- Removed stale package alias drift and replaced with a real facade alias.
- Added robust edge-case handling for `SequentialChain`, memory summarization, and retrieval normalization.

### Quality
- Expanded package test coverage across graph failure paths, queue/checkpoint flow, memory edge cases, retrieval edge cases, and fluent factory composition.

