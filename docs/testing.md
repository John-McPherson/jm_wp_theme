# Testing

## Current state

GitHub Actions installs locked Node and Composer dependencies, audits production npm dependencies, runs focused PHPUnit and Jest regression tests, type-checks, runs PHP syntax checks, lints JavaScript/TypeScript and SCSS, runs PHPCS, builds the theme, creates `dist/`, and verifies required package paths.

The repository contains focused PHPUnit coverage for the Hero block’s server-side palette rendering, header template contracts, and Navigation-block normalisation. Navigation coverage verifies supported links and submenu descendants are flattened in menu order, while incomplete and unsupported entries are skipped safely.\n\nJest unit tests cover the Navigation controller’s initial mobile state, toggle behaviour, Escape handling with focus return, and safe initialisation when required markup is absent.

Playwright browser tests run against an isolated `wp-env` site in Chromium, Firefox, and WebKit. They cover header rendering, sticky and short-viewport behaviour, the authenticated admin-toolbar offset, narrow-viewport overflow, configured and missing-logo states, deterministic title fallback, the logo homepage link, Site Editor block-recovery regressions, and navigation enhancement. Navigation tests create and remove isolated `wp_navigation` records and pages, verify mobile toggle/Escape/focus behaviour, and verify the no-JavaScript fallback.

Broader WordPress integration coverage, visual-regression tests, and automated accessibility tests are not yet configured. Static quality gates and focused regression tests do not replace full behavioural verification.

## Local quality gate

Before review, run:

```bash
npm ci
composer install
composer test
npm run test:unit -- --runInBand
npm run typecheck
npm run lint:php
npm run lint:js
npm run lint:styles
npm run lint:phpcs
npm run build
npm run package
```

Confirm the command set passes from a clean dependency install. CI is authoritative for the supported Linux, Node, and PHP combination.

## JavaScript unit tests\n\nJest is provided through `@wordpress/scripts`. Run all JavaScript unit tests with:\n\n```bash\nnpm run test:unit -- --runInBand\n```\n\nRun only the Navigation controller test while developing:\n\n```bash\nnpm run test:unit -- src/blocks/site/navigation/view.test.ts --runInBand\n```\n\nUse watch mode for an interactive local workflow:\n\n```bash\nnpm run test:unit -- --watch\n```\n\n## Browser end-to-end tests

Start Docker, create the local environment file once, and run the isolated WordPress environment:

```bash
cp .env.e2e.example .env.e2e
npm run env:start
npm run build\nnpm run test:e2e\nnpm run env:stop
```

Use `npm run test:e2e:headed` or `npm run test:e2e:debug` while diagnosing a test. Use Playwright's `--project=chromium`, `--project=firefox`, or `--project=webkit` option to target one engine.\n\nRun only the Navigation browser tests in Chromium:\n\n```bash\nnpm run test:e2e -- tests/e2e/header.spec.ts -g "theme navigation" --project=chromium\n```\n\nRun `npm run build` after changing block scripts or metadata, before starting or testing the local WordPress environment.

Tests that change WordPress options or theme modifications must capture and restore the original state. The shared CI environment runs with one worker to prevent database-state races.

The HTML report is written to `artifacts/e2e/report/`; screenshots, videos, traces, and error contexts are written to `artifacts/e2e/test-results/`. These are disposable artifacts and must not be committed. Future visual-regression baselines are test source and should remain under `tests/e2e/`, separate from generated failure artifacts.

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
- Expand Playwright editor and frontend coverage beyond the header.
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
