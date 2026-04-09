# Contributing

Thanks for contributing to `nexus/laravel-ai-workflows`.

## Development Setup

```bash
composer install
```

## Quality Checks

Run these before opening a PR:

```bash
composer test
composer pint
composer stan
composer validate --strict
```

## Pull Request Guidelines

- Keep changes focused and scoped.
- Add or update tests for behavior changes.
- Update relevant docs under `docs/` when API or workflow behavior changes.
- Follow existing coding style and strict typing conventions.
- Prefer immutable updates (`State::with(...)`) in graph/state code.

## Commit Messages

Use clear, imperative commits, for example:

- `Add conditional edge validation in StateGraph`
- `Document memory strategy usage in docs`

## Reporting Issues

Open issues at:

- https://github.com/mouadh/nexus/issues

