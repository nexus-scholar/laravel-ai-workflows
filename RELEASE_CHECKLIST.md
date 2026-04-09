# Release Checklist (`laravel-ai-chain`)

## Pre-release
- [ ] Review `MAINTAINER_PLAYBOOK.md` and use the command flow for this release.
- [ ] Confirm `composer.json` package metadata/version strategy is final for this tag.
- [ ] Update `CHANGELOG.md` with release date and final highlights.
- [ ] Update `README.md` and `UPGRADE.md` if public API changed.
- [ ] Verify config defaults in `config/ai-chain.php` match documented values.

## Quality gates
- [ ] Validate package metadata:
  - [ ] `composer validate --strict`
- [ ] Run tests:
  - [ ] `composer test`
- [ ] Run style checks:
  - [ ] `composer pint`
- [ ] Run static analysis:
  - [ ] `composer stan`

## Laravel/package checks
- [ ] Verify package discovery wiring (`AiChainServiceProvider` + `AiChain` alias).
- [ ] Verify publish command path/tag:
  - [ ] `php artisan vendor:publish --tag=ai-chain-config`
- [ ] Verify manager/facade quickstart snippets still execute.

## Tag + publish flow
- [ ] Commit release docs/code.
- [ ] Copy/adapt `.github/RELEASE_DRAFT_v0.1.0.md` into GitHub Release notes.
- [ ] Create annotated git tag (for example `v0.1.0`).
- [ ] Push branch and tag.
- [ ] Confirm Packagist/GitHub release notes reflect `CHANGELOG.md`.

## Post-release
- [ ] Open follow-up issue for deferred work (if any).
- [ ] Mark completed roadmap items in `TASK_BREAKDOWN.md`.

