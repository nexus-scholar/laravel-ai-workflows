# Maintainer Playbook (`laravel-ai-chain`)

This runbook gives copy-ready PowerShell commands for release and hotfix operations.
Use it with:
- `RELEASE_CHECKLIST.md`
- `CHANGELOG.md`
- `UPGRADE.md`
- `.github/RELEASE_TEMPLATE.md`
- `.github/RELEASE_DRAFT_v0.1.0.md`

## 1) Preflight (before tagging)

```powershell
Push-Location "C:\Users\mouadh\Desktop\projects\nexus\laravel-ai-chain"
git --no-pager status
php vendor\bin\pest --colors=never
php vendor\bin\pint --test
Pop-Location
```

If `pint --test` fails, run:

```powershell
Push-Location "C:\Users\mouadh\Desktop\projects\nexus\laravel-ai-chain"
php vendor\bin\pint
Pop-Location
```

## 2) Standard release (`vX.Y.Z`)

1. Update release docs:
   - `CHANGELOG.md`
   - `UPGRADE.md` (if required)
   - `.github/RELEASE_DRAFT_vX.Y.Z.md` (copy from template)
2. Commit, tag, and push.

```powershell
Push-Location "C:\Users\mouadh\Desktop\projects\nexus\laravel-ai-chain"
git --no-pager add CHANGELOG.md UPGRADE.md README.md RELEASE_CHECKLIST.md MAINTAINER_PLAYBOOK.md .github/RELEASE_TEMPLATE.md .github/RELEASE_DRAFT_v0.1.0.md
git --no-pager add src tests examples docs
git --no-pager commit -m "chore(release): prepare v0.1.0"
git --no-pager tag -a v0.1.0 -m "Release v0.1.0"
git --no-pager push
git --no-pager push origin v0.1.0
Pop-Location
```

3. Open GitHub Release for `v0.1.0` and paste/adapt `.github/RELEASE_DRAFT_v0.1.0.md`.
4. Confirm Packagist picked up the tag.

## 3) Hotfix release (`vX.Y.Z+1`)

```powershell
Push-Location "C:\Users\mouadh\Desktop\projects\nexus\laravel-ai-chain"
git --no-pager checkout main
git --no-pager pull --ff-only
git --no-pager checkout -b hotfix/v0.1.1
# apply fix
php vendor\bin\pest --colors=never
php vendor\bin\pint --test
git --no-pager add src tests CHANGELOG.md
git --no-pager commit -m "fix: <short hotfix description>"
git --no-pager tag -a v0.1.1 -m "Hotfix v0.1.1"
git --no-pager push origin hotfix/v0.1.1
git --no-pager push origin v0.1.1
Pop-Location
```

Then publish GitHub Release notes (copy previous draft and adjust sections).

## 4) Rollback notes

Prefer forward fixes (new patch release) over rewriting tags.

If a bad release ships:
1. Document issue in GitHub release notes.
2. Prepare a new patch (`vX.Y.Z+1`) quickly.
3. Keep old tag immutable unless repository policy explicitly allows retagging.

If your repository policy allows tag correction (use carefully):

```powershell
Push-Location "C:\Users\mouadh\Desktop\projects\nexus\laravel-ai-chain"
git --no-pager tag -d v0.1.0
git --no-pager push origin :refs/tags/v0.1.0
# recreate corrected tag locally, then push
Pop-Location
```

