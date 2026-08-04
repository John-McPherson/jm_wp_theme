# Releasing

## Status

The release pipeline is not yet safe. The inclusion list now contains `build/`, `inc/`, `components/`, `templates/`, and root runtime files, but also names `parts/`, `patterns/`, and `languages/`, which do not exist on the current `dev` branch. `rsync` therefore exits non-zero. Remove missing optional paths or create them before distributing `dist/`.

## Versioning

The current development version is `0.1.0`. Use semantic versioning:

- `0.x.y` minor: a completed pre-release milestone.
- Patch: a compatible fix to the current milestone.
- `1.0.0`: the first complete, verified production release with stable content contracts.
- After `1.0.0`, major: a breaking public contract or minimum-platform change.

Keep the versions in `package.json`, `style.css`, and block metadata aligned where applicable.

## Branches

- `dev` is the active integration branch.
- `main` represents versioned, releasable work.
- Feature branches should target `dev` until the release workflow changes.

## Required archive contents

The final theme directory must include at least:

- `build/`
- `components/`
- `inc/`
- FSE `templates/`, `parts/`, and `patterns/` when present
- `functions.php`
- `style.css`
- `theme.json`
- Licence and other runtime documentation required for distribution

Exclude development-only files such as `node_modules/`, `src/`, `.git/`, local configuration, test output, and editor settings unless a runtime process explicitly needs them.

## Release checklist

1. Resolve all documented release blockers.
2. Update versions and `CHANGELOG.md`.
3. Install dependencies from the lockfile with `npm ci`.
4. Run type-checking, configured linters, tests, and accessibility checks.
5. Run the production build.
6. Run `npm run package` and require a zero exit status.
7. Inspect the archive contents.
8. Install the archive into a clean supported WordPress environment.
9. Complete the smoke test in [Testing](testing.md).
10. Open a `dev` to `main` PR and self-review the complete release diff. Human approval is not required for the current solo workflow.
11. Tag the exact release commit and retain the tested archive.
12. Deploy using the hosting-specific process and verify production health.

Suggested pre-1.0 milestones are: `0.2.0` core block library, `0.3.0` complete site structure, `0.4.0` content model, `0.5.0` responsive finish, `0.6.0` accessibility verification, `0.7.0` automated assurance, `0.8.0` performance/security, and `0.9.0` release candidate. Merge to `main` at formal version releases, not for every development commit.

## Rollback

Before deployment, retain the previous known-good theme archive and any required database backup. If activation, rendering, PHP logs, or primary journeys fail, restore the previous archive and investigate outside production.

The hosting and deployment mechanism has not yet been selected. Document exact deployment, health-check, backup, and rollback commands here once hosting is established.
