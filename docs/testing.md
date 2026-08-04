# Testing

## Current state

The repository has CI quality gates but no unit, integration, browser, visual-regression, or automated accessibility test suite. GitHub Actions installs locked Node and Composer dependencies, runs the configured type-check and linters, then builds the theme.

## Before every review

```bash
npm run typecheck
npm run lint:php
npm run lint:js
npm run lint:styles
npm run lint:phpcs
npm run build
```

Then verify:

- No Sass, TypeScript, or block build errors.
- No PHP warnings in the affected WordPress flow.
- No browser console errors.
- Changed blocks can be inserted, configured, saved, reloaded, and rendered.
- Each of the nine custom blocks renders correctly in every allowed parent/child composition.
- Empty, default, populated, and invalid attribute states are safe.
- Editor and frontend presentation remain aligned.
- Layout works at narrow mobile, tablet, desktop, and wide desktop widths.
- Keyboard focus and reduced-motion behaviour meet the accessibility standard.

## PHP checks

- Syntax linting for all PHP files is configured.
- WordPress Coding Standards via PHPCS is configured.
- PHPStan with WordPress stubs at an agreed level.
- Unit tests for argument and HTML-attribute helpers.
- Integration tests for block discovery and dynamic rendering.

## Frontend checks

- TypeScript type-checking, `wp-scripts lint-js`, and `wp-scripts lint-style` are configured.
- Unit tests for attribute transformations and editor controls.
- Playwright tests for block-editor and frontend smoke flows.
- axe integration for representative pages and block states.
- Visual regression snapshots for palettes and responsive variants.

## Accessibility manual matrix

Automated scans cannot validate reading order, usable focus, meaningful alternative text, or sensible screen-reader output. At release candidates, test:

- Keyboard-only navigation.
- 200% browser zoom and 320px reflow.
- Reduced-motion preference.
- At least one desktop screen reader/browser combination.
- VoiceOver with iOS Safari for primary navigation and contact journeys.

## WordPress compatibility matrix

At minimum, verify the lowest supported WordPress/PHP versions declared in `style.css` and the current supported production versions. Update `Tested up to` only after testing that WordPress release.

The current header declares both `Requires at least` and `Tested up to` as WordPress `7.1`, with PHP `8.0`. Treat those values as release claims that require evidence, not merely desired targets.

## Release smoke test

1. Produce the release archive.
2. Install it into a clean WordPress instance without `node_modules` or repository source files.
3. Activate the theme.
4. Open the site editor and frontend.
5. Insert and render every custom block.
6. Confirm CSS, scripts, fonts, images, components, templates, and PHP includes load.
7. Check debug logs and the browser console.

A working development checkout is not evidence that the distributable archive works.

CI currently runs `npm run build`, not `npm run package`, so the release-directory failure is not detected automatically. Add package execution and structural assertions before treating CI as a release gate.
