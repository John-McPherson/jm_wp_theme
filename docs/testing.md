# Testing

## Current state

GitHub Actions installs locked Node and Composer dependencies, audits production npm dependencies, runs focused PHPUnit regression tests, type-checks, runs PHP syntax checks, lints JavaScript/TypeScript and SCSS, runs PHPCS, builds the theme, creates `dist/`, and verifies required package paths.

The repository contains focused PHPUnit coverage for the Hero block’s server-side palette rendering and header template contracts. Header coverage verifies template-part registration, inclusion from the intended template, editable Site Logo and Site Title blocks, and the absence of hard-coded business identity markup.

Playwright browser tests run against an isolated `wp-env` site in Chromium, Firefox, and WebKit. They cover header rendering, sticky and short-viewport behaviour, the authenticated admin-toolbar offset, narrow-viewport overflow, configured and missing-logo states, deterministic title fallback, the logo homepage link, and Site Editor block-recovery regressions.

Broader WordPress integration coverage, visual-regression tests, and automated accessibility tests are not yet configured. Static quality gates and focused regression tests do not replace full behavioural verification.

## Local quality gate

Before review, run:

```bash
npm ci
composer install
composer test
npm run typecheck
npm run lint:php
npm run lint:js
npm run lint:styles
npm run lint:phpcs
npm run build
npm run package
```

Confirm the command set passes from a clean dependency install. CI is authoritative for the supported Linux, Node, and PHP combination.

## PHP regression tests

PHPUnit configuration is defined in `phpunit.xml.dist`. PHP tests and their isolated WordPress test doubles live under `tests/phpunit/`.

Run the PHP test suite with:

```bash
composer test
```

Render regression tests must exercise omitted, default, supported, and deliberately malformed attribute values where applicable. PHP notices and warnings should cause test failures.

The current test doubles provide only the behaviour required by isolated render tests. They are not a substitute for WordPress integration tests and should not reproduce unrelated WordPress functionality.

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

- Expand PHP unit coverage for component-argument and HTML-attribute helpers.
- Expand PHP render coverage across dynamic blocks, validation, defaults, and malformed input.
- Add WordPress integration coverage where isolated test doubles cannot represent runtime behaviour accurately.
- Increase TypeScript strictness and add typed block-editor contract tests.
- Add Playwright editor and frontend smoke tests.
- Add axe checks for representative editor and frontend states.
- Consider visual-regression coverage for palettes and responsive variants.

Do not describe planned checks as configured until they run in CI.

## Accessibility release matrix

Automated scans cannot validate reading order, usable focus, meaningful alternative text, or sensible assistive-technology output. At release candidates, test:

- Keyboard-only navigation.
- 200% zoom and 320 CSS-pixel reflow.
- Reduced-motion preference.
- At least one desktop screen-reader and browser combination.
- VoiceOver with iOS Safari for primary navigation and contact journeys.

## Compatibility and package verification

Test the lowest supported WordPress and PHP versions declared in `style.css`, plus the current production target. Update `Tested up to` only after testing that WordPress release.

CI currently verifies that required files exist in `dist`; it does not install or activate the package in WordPress. Before release:

1. Produce `dist/` with `npm run package`.
2. Archive the packaged theme directory.
3. Install it in a clean supported WordPress environment without source dependencies.
4. Activate it and open the Site Editor and frontend.
5. Render all custom blocks and representative templates.
6. Confirm CSS, scripts, fonts, images, components, templates, and PHP includes load.
7. Review PHP logs and the browser console.

A working development checkout is not evidence that the distributable package works.
