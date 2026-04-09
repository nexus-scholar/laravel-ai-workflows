# Examples

 These examples are runnable without external API keys using a local `DemoAgent` implementation in `examples/Support/DemoAgent.php`.

## Included use cases

- `01-basic-chain.php`
  - Single prompt -> response chain.
- `02-chain-with-memory.php`
  - Conversation memory injection via `{history}`.
- `03-chain-with-retrieval.php`
  - Retrieval-augmented prompt with `{context}`.
- `04-state-graph-workflow.php`
  - Deterministic state machine using `StateGraph`.
- `05-manager-and-factory.php`
  - `AiChainManager` + fluent `ChainFactory` composition.
- `fluent-chain-factory.php`
  - Simple two-stage fluent chain composition.

## Run an example

```bash
php examples/01-basic-chain.php
```

```bash
php examples/04-state-graph-workflow.php
```

