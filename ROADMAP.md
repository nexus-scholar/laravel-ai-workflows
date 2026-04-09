# `laravel-ai-workflows` Roadmap

## Phase 0 — Stabilize the package surface
- [ ] Add package config and publishable defaults.
- [ ] Make `src/AiChainServiceProvider.php` register bindings, config, and integrations.
- [ ] Resolve package metadata drift around facades and aliases.

## Phase 1 — Make graph execution trustworthy
- [ ] Validate graph definitions before compilation.
- [ ] Add explicit exceptions for invalid nodes, edges, and node return types.
- [ ] Confirm `State` remains immutable and `with()` returns a new instance.

## Phase 2 — Make async execution production-safe
- [ ] Refactor `src/Graph/Runners/QueueRunner.php` and `src/Jobs/RunGraphNode.php` to avoid serializing closures.
- [ ] Define checkpoint/resume semantics in `src/Graph/Checkpoint/CacheCheckpoint.php`.
- [ ] Add max-iteration and recovery tests for long-running workflows.

## Phase 3 — Harden memory and retrieval
- [ ] Tighten `CacheConversationMemory` around concurrent updates and cache-store behavior.
- [ ] Make `SummaryMemory` failure-safe and deterministic.
- [ ] Validate retriever output shapes and deduplication in `VectorStoreRetriever`, `HybridRetriever`, and `RerankingRetriever`.

## Phase 4 — Lock behavior with tests
- [ ] Cover graph happy paths and failure paths in `tests/Unit/Graph/*`.
- [ ] Add memory/retrieval edge-case tests in `tests/Unit/Memory/*` and `tests/Unit/Retrieval/*`.
- [ ] Add feature tests for Laravel bootstrapping and package wiring.

## Definition of done
- [ ] The package boots cleanly in Laravel without manual glue code.
- [ ] Graph execution fails fast on invalid input and is covered by tests.
- [ ] Queue-backed graph runs can be resumed or safely retried.
- [ ] Memory and retrieval APIs behave predictably under edge cases.

