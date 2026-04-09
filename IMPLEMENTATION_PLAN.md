# `laravel-ai-workflows` Implementation Plan

## Goal
Make the package production-ready by hardening the bootstrap, graph runtime, queue execution, state handling, and test coverage.

## Checklist
- [ ] **Bootstrap and config**
  - Add a real config surface in `config/ai-chain.php`.
  - Expand `src/AiChainServiceProvider.php` to merge config, bind core services, and publish assets if needed.
  - Remove or implement any advertised facades/aliases that do not exist.
- [ ] **Graph runtime correctness**
  - Validate entry points, node existence, and edge destinations in `src/Graph/StateGraph.php`.
  - Add clear exceptions for invalid graphs and invalid node return values in `src/Graph/CompiledGraph.php`.
  - Keep `src/Graph/State.php` immutable and `Node::handle()` pure.
- [ ] **Queue and checkpoint safety**
  - Redesign `src/Graph/Runners/QueueRunner.php` and `src/Jobs/RunGraphNode.php` so jobs do not serialize closure-bearing graph objects.
  - Harden `src/Graph/Checkpoint/CacheCheckpoint.php` with safer persistence and resume semantics.
- [ ] **Memory and retrieval robustness**
  - Validate edge cases in `src/Memory/CacheConversationMemory.php` and `src/Memory/SummaryMemory.php`.
  - Tighten input/output contracts in `src/Retrieval/VectorStoreRetriever.php`, `HybridRetriever.php`, and `RerankingRetriever.php`.
  - Guard empty pipelines in `src/Chains/SequentialChain.php`.
- [ ] **Testing and verification**
  - Add unit tests for invalid graph definitions, queue dispatch, checkpoint persistence, and empty chain cases.
  - Add feature tests for Laravel service-provider bootstrapping and config publishing.

## Working order
1. Bootstrap/config
2. Graph validation
3. Queue/checkpoint redesign
4. Memory/retrieval hardening
5. Tests that lock behavior in

