# SDK Adoption Milestones

This checklist tracks the Laravel AI SDK adoption roadmap for `laravel-ai-workflows`.

## M0 - Foundation and Compliance

- [ ] M0-PR1 (1 day): Composer/package hygiene
  - Scope: keep `composer.json` metadata and dependency classification clean.
  - Acceptance tests:
    - `composer validate --strict`
- [ ] M0-PR2 (1 day): Consumer compatibility contract
  - Scope: document and verify integration assumptions with `nexus-agents`.
  - Acceptance tests:
    - `vendor/bin/pest --configuration=phpunit.xml`

## M1 - Chain SDK Pass-through

- [x] M1-PR1 (1-2 days): Timeout + attachments pass-through in `Chain`
  - Scope: support prompt `timeout` and `attachments` without bypassing SDK internals.
  - Acceptance tests:
    - Chain unit tests assert prompt timeout/attachments are forwarded.
- [x] M1-PR2 (1-2 days): Provider failover arrays + provider options
  - Scope: typed pass-through for provider arrays/options.
  - Progress:
    - [x] Failover provider arrays are covered by Chain unit tests.
    - [x] Provider options pass-through supports both static arrays and resolver callbacks.
  - Acceptance tests:
    - Unit tests verify provider payload forwarding.
- [x] M1-PR3 (1 day): Boundary guard + docs note
  - Scope: codify no direct provider HTTP logic in chain layer.
  - Progress:
    - [x] Boundary notes added in chain code/docs.
    - [x] Static guard test added for `src/Chains` transport patterns.
  - Acceptance tests:
    - `vendor/bin/pest --configuration=phpunit.xml tests/Unit/Chains/ChainBoundaryGuardTest.php`

## M1.5 - Streaming Contract and Tool Interop

- [ ] M1.5-PR1 (1 day): Streaming contract ADR
  - Scope: decide stable API for raw events vs text deltas.
  - Acceptance tests:
    - Existing `stream()` behavior remains backward compatible.
- [ ] M1.5-PR2 (1-2 days): Event-preserving streaming implementation
  - Scope: expose SDK event metadata stream path.
  - Acceptance tests:
    - Unit tests verify event and text paths.
- [ ] M1.5-PR3 (1 day): `ChainTool` provider-tool semantics
  - Scope: clarify pass-through behavior for provider tools.
  - Acceptance tests:
    - Tool tests cover schema + forwarding behavior.

## M2 - Graph Runtime Alignment

- [ ] M2-PR1 (1-2 days): Queue lifecycle hooks
  - Scope: add success/failure callbacks in queue runner flow.
  - Acceptance tests:
    - `QueueRunner` tests validate callback invocation.
- [ ] M2-PR2 (1-2 days): Progress event surfacing
  - Scope: expose node-level progress for CLI/workflow runners.
  - Acceptance tests:
    - Graph tests validate progress sequence and payload shape.
- [ ] M2-PR3 (1 day): Queue safety enforcement mode
  - Scope: strict vs permissive behavior for non-queue-safe graphs.
  - Acceptance tests:
    - Strict mode fails fast, permissive mode warns.

## M3 - Memory and Retrieval Adapters

- [ ] M3-PR1 (1-2 days): Database conversation memory adapter
  - Scope: align package memory with SDK conversation storage.
  - Acceptance tests:
    - Persistence + rehydration tests.
- [ ] M3-PR2 (1-2 days): Retrieval strategy split
  - Scope: DB-vector path and provider-hosted store path behind a shared contract.
  - Acceptance tests:
    - Contract tests pass for both strategy implementations.
- [ ] M3-PR3 (1-2 days): Double-persistence policy
  - Scope: avoid conflicts between checkpointing and conversation memory.
  - Acceptance tests:
    - Integration test validates no duplicate persistence.

## M4 - Hardening (Tests and Docs)

- [ ] M4-PR1 (1-2 days): SDK-fake-first test migration
  - Scope: use SDK fakes for agent/retrieval integrations where possible.
  - Acceptance tests:
    - Test suite stays deterministic and network-free.
- [ ] M4-PR2 (1-2 days): Tutorials and docs parity
  - Scope: examples for failover, attachments, streaming events, provider tools, retrieval paths.
  - Acceptance tests:
    - Examples/docs smoke checks remain valid.

## Default PR Gate

```bash
composer validate --strict
vendor/bin/pest --configuration=phpunit.xml
vendor/bin/pint --test src tests
vendor/bin/phpstan analyse --memory-limit=512M
```

