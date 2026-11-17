# Roadmap and issue workflow

## Release roadmap

### v0.2.0 – Site Foundations

Deliver the structure and content components required to build a usable website: accessible header and footer template parts, responsive navigation, primary templates, reusable patterns, and the highest-priority content blocks. Pull forward defects that block reliable page building.

**Complete when:** core pages can be assembled in the Site Editor, navigation works across screen sizes and input methods, and no placeholder template sections remain.

### v0.3.0 – Block Stabilisation

Make block and shared-component contracts reliable and consistent. Resolve known defects, normalize `block.json` schemas, strengthen validation and escaping, remove obsolete attributes, and align editor/frontend behaviour.

**Complete when:** every block has a consistent contract, handles missing or malformed data safely, and behaves predictably in editor and frontend.

### v0.4.0 – Quality & Testing

Add automated verification for critical behaviour: PHP helper/render tests, incremental TypeScript strictness, editor/frontend E2E smoke tests, and accessibility checks integrated into CI.

**Complete when:** CI rejects important type, coding-standard, behavioural, and accessibility regressions in block, template, and navigation workflows.

### v0.5.0 – Release Hardening

Make builds portable, repeatable, and safe to distribute. Replace environment-specific packaging, produce a deterministic artifact, and install/activate it in a clean supported WordPress environment.

**Complete when:** CI creates an installable package that activates cleanly with required templates, blocks, components, fonts, styles, and scripts.

### v1.0.0 – Production Release

Deliver the first stable, populated, production-supported theme. Complete content, responsive/design QA, performance, SEO, structured data, security review, browser/device testing, operational documentation, and launch approval.

**Complete when:** required content is complete, no release blockers remain, automated and manual checks pass, and the tested package is ready to deploy and roll back.

## Issue taxonomy

Each issue should normally have:

- One `type:` label: bug, feature, enhancement, testing, documentation, or maintenance.
- One `priority:` label: critical, high, medium, or low.
- One or more `area:` labels for affected systems.
- One version milestone.
- A `status:` label only for exceptional states such as blocked, needs-design, needs-content, or needs-review.
- One `scope:` label when frontend/editor impact benefits from being explicit.

Milestones represent release assignment; do not duplicate versions as labels.

## Issue content

A ready issue states:

- the problem or outcome;
- why it matters;
- bounded implementation scope;
- acceptance criteria;
- verification steps;
- dependencies and linked parent tracker, when applicable.

Keep implementation issues independently mergeable. Large outcomes remain open as tracker issues with linked task checklists.

## Project usage

Use one GitHub Project for the complete roadmap:

- **Milestones** answer which release contains the work.
- **Project Status** answers where the work is: Backlog, Ready, In progress, In review, Blocked, or Done.
- Labels answer what kind of work it is and which systems it affects.

Recommended views are Current release, Needs input, All backlog, and Release roadmap. Avoid duplicating label data in custom fields unless a field is needed for reliable sorting or automation.

## Pull request linkage

Feature and fix branches target `dev`. Link the relevant issue in the pull request, describe verification and accessibility evidence, and close child implementation issues only when their acceptance criteria are met. Parent trackers close when every required child outcome is complete.
