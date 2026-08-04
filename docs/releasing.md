# Releasing

## Status

The release pipeline is not yet safe. `npm run package` currently copies `build/`, `theme.json`, `style.css`, and `functions.php`, but omits PHP runtime dependencies including `inc/` and `components/`. Do not distribute the current `dist/` output.

## Versioning

Use semantic versioning:

- Patch: compatible fixes.
- Minor: compatible features or blocks.
- Major: breaking block contracts, migrations, or minimum-platform changes.

Keep the versions in `package.json`, `style.css`, and block metadata aligned where applicable.

## Branches

- `dev` is the active integration branch.
- `main` represents reviewed, releasable work.
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
4. Run formatting, linting, static analysis, tests, and accessibility checks.
5. Run the production build.
6. Build the archive from an explicit inclusion list.
7. Inspect the archive contents.
8. Install the archive into a clean supported WordPress environment.
9. Complete the smoke test in [Testing](testing.md).
10. Obtain review approval and merge the release to `main`.
11. Tag the exact release commit and retain the tested archive.
12. Deploy using the hosting-specific process and verify production health.

## Rollback

Before deployment, retain the previous known-good theme archive and any required database backup. If activation, rendering, PHP logs, or primary journeys fail, restore the previous archive and investigate outside production.

The hosting and deployment mechanism has not yet been selected. Document exact deployment, health-check, backup, and rollback commands here once hosting is established.
