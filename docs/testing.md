# Testing

## Current state

GitHub Actions installs locked Node and Composer dependencies, audits production npm dependencies, type-checks, runs PHP syntax checks, lints JavaScript/TypeScript and SCSS, runs PHPCS, builds the theme, creates `dist/`, and verifies required package paths.

The repository does not yet contain unit, integration, browser, visual-regression, or automated accessibility tests. Static quality gates are not behavioural verification.

## Local quality gate

Before review, run:

```bash
npm ci
composer install
npm run typecheck
npm run lint:php
npm run lint:js
npm run lint:styles
npm run lint:phpcs
npm run build
npm run package
```

Confirm the command set passes from a clean dependency install. CI is authoritative for the supported Linux/Node/PHP combination.

## Manual feature verification

For every changed block, component, template, or pattern:

- Exercise empty, default, populated, and deliberately invalid states.
- Insert, configure, save, reload, and render affected blocks.
- Check PHP debug logs and the browser console.
- Compare editor and frontend output.
- Test narrow mobile, tablet, desktop, and wide desktop widths.
- Test every supported palette, order, and variant.
- Verify allowed parent/child composition and block locking.
- Test keyboard operation, visible focus, reduced motion, zoom, and reflow.

## Planned automated coverage

Tracked under `v0.4.0 – Quality & Testing`:

- PHP unit tests for component-argument and HTML-attribute helpers.
- PHP render tests for dynamic blocks, validation, defaults, and malformed input.
- Incremental TypeScript strictness and typed block editor contracts.
- Playwright editor/frontend smoke tests.
- axe checks for representative editor and frontend states.
- Optional visual regression coverage for palettes and responsive variants.

Do not describe these checks as configured until they run in CI.

## Accessibility release matrix

Automated scans cannot validate reading order, usable focus, meaningful alternative text, or sensible assistive-technology output. At release candidates, test:

- Keyboard-only navigation.
- 200% zoom and 320 CSS-pixel reflow.
- Reduced-motion preference.
- At least one desktop screen-reader/browser combination.
- VoiceOver with iOS Safari for primary navigation and contact journeys.

## Compatibility and package verification

Test the lowest supported WordPress and PHP versions declared in `style.css`, plus the current production target. Update `Tested up to` only after testing that WordPress release.

CI currently verifies that required files exist in `dist/`; it does not install or activate the package in WordPress. Before release:

1. Produce `dist/` with `npm run package`.
2. Archive the packaged theme directory.
3. Install it in a clean supported WordPress environment without source dependencies.
4. Activate it and open the Site Editor and frontend.
5. Render all custom blocks and representative templates.
6. Confirm CSS, scripts, fonts, images, components, templates, and PHP includes load.
7. Review PHP logs and the browser console.

A working development checkout is not evidence that the distributable package works.
