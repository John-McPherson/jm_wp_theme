# Contributing

## Workflow

1. Start from `dev` and select a focused issue.
2. Create a focused branch such as `feature/header-navigation` or `fix/hero-palette`.
3. Keep the change independently reviewable and avoid unrelated formatting churn.
4. Follow [Development](docs/development.md), [Blocks](docs/blocks.md), [Accessibility](docs/accessibility.md), and the [Roadmap and issue workflow](docs/roadmap.md).
5. Run the quality gate and relevant manual verification.
6. Update documentation when a public contract, command, token, workflow, or platform claim changes.
7. Open a pull request into `dev`, link the issue, and self-review the complete diff.

Formal release pull requests merge `dev` into `main` after completing the release checklist.

## Issue conventions

Each implementation issue should have one version milestone, one `type:` label, one `priority:` label, and relevant `area:` labels. Use `status:` labels only for exceptional states and keep large parent issues as trackers with linked task checklists.

Do not close a tracker until all required child outcomes and its own completion criteria are satisfied.

## Pull request expectations

Include:

- What changed and why.
- The linked issue and acceptance criteria addressed.
- Screenshots or recordings for visual/editor changes.
- Commands and manual test steps run.
- Accessibility checks completed.
- Block compatibility or migration impact.
- Packaging or deployment impact.
- Known limitations and follow-up issues.

Use closing keywords only when the pull request fully satisfies the linked issue.

## Review standard

A change is ready when it is understandable, validates and escapes data safely, preserves editor/frontend parity, uses the design system, meets the applicable accessibility standard, passes configured gates, and includes evidence for its issue acceptance criteria.

Do not commit secrets, local environment files, `node_modules`, `vendor`, or unexplained generated changes.
