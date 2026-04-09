# ADR-0001: Chain Streaming Contract

- Status: Accepted
- Date: 2026-04-09
- Related milestone: M1.5-PR1 (`SDK_ADOPTION_MILESTONES.md`)

## Context

`Chain::stream(array $inputs)` currently yields text deltas and is already used in tests and examples.

Laravel AI SDK streams richer event objects (`StreamableAgentResponse`) that include metadata (usage, stop reasons, tool events, etc.).

We need an adoption path that preserves existing behavior while enabling SDK event access.

## Decision

1. Keep `Chain::stream(array $inputs): Generator` as the backward-compatible text stream API.
2. Introduce a new API in M1.5-PR2 for event-preserving streaming (`streamEvents(...)`) rather than changing `stream()` return semantics.
3. `streamEvents(...)` will expose SDK stream events in-order and leave formatting/aggregation to callers.
4. `stream()` will continue to be implemented as a convenience layer over event streaming (extracting text deltas only).

## Rationale

- Avoids breaking users who expect `stream()` to yield strings.
- Matches SDK capability by adding a dedicated event path.
- Keeps simple UX for token-by-token output while enabling advanced tooling and telemetry.

## Consequences

### Positive

- Backward compatibility is preserved.
- Advanced integrations can consume full SDK stream metadata.
- Future graph/runtime streaming work can build on a stable event API.

### Negative

- Two streaming APIs must be documented and tested.
- Callers must choose between convenience (`stream`) and rich events (`streamEvents`).

## Implementation Notes for M1.5-PR2

- Add `streamEvents(array $inputs): iterable` to chain contract and concrete chain.
- Keep current stream extraction logic behavior unchanged.
- Add unit tests for both APIs:
  - `stream()` still yields string chunks.
  - `streamEvents()` yields native SDK events.

