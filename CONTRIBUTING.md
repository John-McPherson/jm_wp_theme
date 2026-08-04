# Contributing

## Workflow

1. For a substantial or risky change, branch from `dev` with a focused name such as `feature/hero-variants` or `fix/block-categories`. Small coherent changes may be committed directly to `dev` while this remains a solo project.
2. Keep changes scoped and avoid unrelated formatting churn.
3. Follow [Development](docs/development.md), [Blocks](docs/blocks.md), and [Accessibility](docs/accessibility.md).
4. Run the production build and relevant tests.
5. Update documentation when a public contract, command, token, or workflow changes.
6. Self-review the complete diff. Use a pull request into `dev` when a durable review record is valuable.

For a formal version, open a pull request from `dev` to `main`, run the release checklist, merge, and tag the merge commit. No human approval is required while the project has one developer; passing checks and a deliberate self-review are the gate.

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
