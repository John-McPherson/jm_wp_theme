# Releasing

## Status

The current package command builds the theme, recreates `dist/`, and copies `build/`, `inc/`, `components/`, `templates/`, and required root runtime files. CI runs the command and checks its essential structure.

This is a structural gate, not a complete release test. Packaging remains Unix-specific because it uses `mkdir`, `rm`, and `rsync`; CI does not yet install and activate the package in a clean WordPress environment.

## Versioning and milestones

Use semantic versioning. The active plan is:

| Milestone | Outcome |
| --- | --- |
| `v0.2.0 – Site Foundations` | Header, footer, navigation, templates, patterns, and essential site-building blocks |
| `v0.3.0 – Block Stabilisation` | Consistent block/component contracts and resolved implementation defects |
| `v0.4.0 – Quality & Testing` | Behavioural tests, stricter TypeScript, E2E, and accessibility automation |
| `v0.5.0 – Release Hardening` | Portable deterministic packaging and clean-install verification |
| `v1.0.0 – Production Release` | Complete content, final QA, and supported production release |

See [Roadmap and issue workflow](roadmap.md) for completion criteria.

Keep versions aligned in `package.json`, `style.css`, and block metadata where applicable. Patch releases contain compatible fixes; `1.0.0` is the first stable supported release.

## Branches

- `dev` is the active integration branch.
- Feature and fix pull requests target `dev`.
- `main` represents versioned, releasable work.
- Formal release pull requests merge `dev` into `main`.

## Required package contents

The installable theme must include:

- `build/`
- `components/`
- `inc/`
- `templates/`
- `parts/`, `patterns/`, and `languages/` when introduced
- `functions.php`
- `style.css`
- `theme.json`
- required licence and runtime documentation

Exclude `node_modules/`, `vendor/` unless runtime dependencies require it, `src/`, `.git/`, local configuration, and test output.

## Release checklist

1. Close or explicitly defer all milestone blockers.
2. Update versions and `CHANGELOG.md` once it exists.
3. Install dependencies from lockfiles with `npm ci` and `composer install`.
4. Run the complete local quality gate in [Testing](testing.md).
5. Run `npm run package` and inspect `dist/`.
6. Install and activate the package in a clean supported WordPress environment.
7. Complete block, template, navigation, accessibility, and compatibility smoke tests.
8. Update `Tested up to` only when supported by evidence.
9. Open and review a `dev` to `main` release PR.
10. Tag the exact release commit and retain the tested package.
11. Deploy with the hosting-specific runbook and verify production health.

## Rollback

Retain the previous known-good theme package and any required database backup before deployment. If activation, rendering, PHP logs, or primary journeys fail, restore the prior package and investigate outside production.

The hosting mechanism is not yet selected. Add exact deployment, health-check, backup, and rollback commands here before `v1.0.0`.
