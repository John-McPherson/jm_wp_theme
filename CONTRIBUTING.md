# Contributing

## Workflow

1. Branch from `dev` with a focused name such as `feature/hero-variants` or `fix/block-categories`.
2. Keep changes scoped and avoid unrelated formatting churn.
3. Follow [Development](docs/development.md), [Blocks](docs/blocks.md), and [Accessibility](docs/accessibility.md).
4. Run the production build and relevant tests.
5. Update documentation when a public contract, command, token, or workflow changes.
6. Open a pull request targeting `dev`.

## Pull request expectations

Include:

- What changed and why.
- Screenshots for visual/editor changes.
- Test steps and environments used.
- Accessibility checks completed.
- Any block compatibility or migration impact.
- Packaging impact.
- Known limitations or follow-up work.

## Review standard

A change is ready when it is understandable, safely validates and escapes data, preserves editor/frontend parity, uses the design system, meets the accessibility standard, and does not break a clean production build.

Do not commit secrets, local environment files, `node_modules`, or unexplained generated changes.
