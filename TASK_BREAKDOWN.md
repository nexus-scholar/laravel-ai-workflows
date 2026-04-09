# `laravel-ai-chain` Task Breakdown

## 1) Stabilize the package surface
- [x] Add `config/ai-chain.php` with defaults for graph execution, checkpointing, memory, and retrieval.
- [x] Expand `src/AiChainServiceProvider.php` to merge config, bind core services, and publish config.
- [x] Review `composer.json` `extra.laravel.aliases` and either implement `src/Facades/Chain.php` or remove the alias.

## 2) Harden graph runtime correctness
- [x] Validate `StateGraph::setEntryPoint()` / `compile()` input in `src/Graph/StateGraph.php`.
- [x] Validate edge destinations and graph consistency before `CompiledGraph` is constructed.
- [x] Add fail-fast errors in `src/Graph/CompiledGraph.php` for unknown nodes, invalid returns, and max-iteration overflow.
- [x] Keep `src/Graph/State.php` immutable and verify `with()` always returns a new state.
- [x] Review `src/Graph/Edge.php` for compile-time assumptions around direct vs conditional edges.

## 3) Make async execution safe
- [x] Refactor `src/Graph/Runners/QueueRunner.php` so queued jobs do not depend on serializing closure-bearing graphs.
- [x] Rework `src/Jobs/RunGraphNode.php` to carry only serializable execution context.
- [x] Harden `src/Graph/Checkpoint/CacheCheckpoint.php` with clearer history shape, TTL behavior, and resume semantics.

## 4) Harden memory and retrieval
- [x] Validate cache-store behavior and window trimming in `src/Memory/CacheConversationMemory.php`.
- [x] Make `src/Memory/SummaryMemory.php` deterministic and safe when summarization fails or returns empty output.
- [x] Tighten result normalization in `src/Retrieval/VectorStoreRetriever.php`, `HybridRetriever.php`, and `RerankingRetriever.php`.
- [x] Guard empty pipelines and invalid chains in `src/Chains/SequentialChain.php`.
- [x] Align `src/Chains/Chain.php` with Laravel AI `Agent` contract and stream/structured response handling.

## 5) Lock behavior with tests
- [x] Add negative-path graph tests in `tests/Unit/Graph/GraphTest.php`.
- [x] Add queue/checkpoint tests in `tests/Unit/Graph/QueueRunnerTest.php` and new checkpoint coverage.
- [x] Extend memory tests in `tests/Unit/Memory/*.php` for cache and summarization edge cases.
- [x] Extend retrieval tests in `tests/Unit/Retrieval/*.php` for duplicates, empty results, and malformed inputs.
- [x] Add package bootstrap tests for `AiChainServiceProvider` and config wiring.

## First files to touch
1. `src/AiChainServiceProvider.php`
2. `config/ai-chain.php`
3. `composer.json`
4. `src/Graph/StateGraph.php`
5. `src/Graph/CompiledGraph.php`

