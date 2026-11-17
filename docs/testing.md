# Testing

## Current state

GitHub Actions runs the quality workflow once per pull request, and on pushes to `dev` and `main`, as two parallel jobs:

- **PHP** — on the minimum supported PHP version (8.0) and a current one (8.3): installs locked Composer dependencies, runs PHP syntax checks, PHPCS, and the focused PHPUnit regression tests.
- **Node** — on the version in `.nvmrc`: installs locked npm dependencies, runs the Jest unit tests, type-checks, lints JavaScript/TypeScript and SCSS, builds the theme and creates `dist/` in one step, and verifies required package paths.

The repository contains focused PHPUnit coverage for the Hero block’s server-side palette rendering, header template contracts, Navigation-block normalisation, link-destination validation, and the header call-to-action. Navigation coverage verifies supported links and submenu descendants are flattened in menu order, while incomplete and unsupported entries are skipped safely. `LinkHelperTest` covers accepted URLs, site paths, and normalised telephone links, and rejects unsafe or unsupported destinations. `HeaderCtaRenderTest` covers configured URL, path, and telephone output, new-tab handling, escaping and class sanitisation, and omission for empty, malformed, and unsafe data. `HeaderTemplateTest` verifies the call-to-action is nested and locked inside the header navigation. `FooterTemplateTest` verifies the footer part is registered, ends every template as a `<footer>`, nests the locked business details, a menu column, and the locked legal row inside the locked, inverse footer container, and hard-codes no business details. `FooterRenderTest` covers each footer palette, malformed palettes, and omission when there is no inner content. `FooterBusinessRenderTest` covers every field and its icon, the home link, the service-area label, telephone and email validation, rich-text normalisation and escaping, and omission of empty fields and wrappers. `FooterNavigationRenderTest` covers menu output and order, the landmark label and its fallback, `aria-current`, escaping, and omission for missing, wrong-type, non-menu, and empty menus. `FooterLegalRenderTest` covers the reading order of credit, copyright, and links, the author credit, the copyright year and holder fallbacks (holder, site title, year alone), rich-text normalisation and escaping, the landmark label and its fallback, `aria-current`, the published `legal` menu lookup, and omission of the links for missing, empty, draft, wrong-slug, and invalid menus. `NavigationMenusTest` covers the registered menus and their hooks, slug lookup by status, post type, and unusable slugs, and menu creation: missing menus are created, existing menus in any status are skipped, and creation runs only when the registered slugs change. `ServicesHelperTest` covers Services page resolution (configured, slug fallback, invalid, and missing parents, including deleted, draft, private, trashed, wrong-type, and mismatched pages), the services query contract (direct published, unprotected children ordered by menu order then title, with no limit, and no query without a parent), icon-name validation, and card data built from page fields, including older pages without an excerpt or icon and records that cannot produce a link. `ServiceListRenderTest` covers zero, one, and many services in query order, the queried parent, card heading levels and malformed values, registered, unregistered, and malformed icons, the decorative arrow, skipped unusable records, escaping, and the empty states: nothing for visitors, and an explanatory notice for editors previewing the block.\n\nJest unit tests cover the Navigation controller’s initial mobile state, toggle behaviour, Escape handling with focus return, and safe initialisation when required markup is absent. `src/editor/service-icon/utils.test.ts` covers when the Service icon panel appears, icon options (mapping, sorting, de-duplication, hiding interface icons, malformed responses), icon search, and reading the localised settings. `scripts/icons/shapes.test.js` covers converting Lucide shapes to paths and rejecting unsupported shapes; Jest also collects tests under `scripts/`.

Playwright browser tests run against an isolated `wp-env` site pinned to the WordPress release in `.wp-env.json`. Pull requests run Chromium only for fast feedback; pushes to `dev` and `main` run Chromium, Firefox, and WebKit as parallel jobs. A nightly scheduled run tests every browser against WordPress `master` to catch upstream breakage. Playwright browsers are cached between runs. The tests cover header rendering, sticky and short-viewport behaviour, the authenticated admin-toolbar offset, narrow-viewport overflow, configured and missing-logo states, deterministic title fallback, the logo homepage link, Site Editor block-recovery regressions, and navigation enhancement. Navigation tests create and remove isolated `wp_navigation` records and pages, verify mobile toggle/Escape/focus behaviour, and verify the no-JavaScript fallback.

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

`tests/phpunit/bootstrap.php` loads the real, WordPress-independent theme helpers (`inc/helpers/component-args.php`, `inc/helpers/html/attributes.php`, `inc/helpers/link.php`, `inc/helpers/navigation.php`, and `inc/helpers/text.php`) rather than duplicating them, so render tests exercise production attribute handling. It also provides WordPress test doubles: `esc_attr()`, `esc_html()`, and `esc_html_e()` HTML-encode their input so tests can assert escaped output, and `jmc_component()` includes the component template directly with `$args` in scope. `esc_url()` returns its input unchanged, so assert URL safety through theme validation such as `jmc_safe_link_url()`. Footer tests use further doubles: `get_post()` reads fixtures from `$GLOBALS['jmc_test_posts']`, `parse_blocks()` decodes `post_content` stored as JSON-encoded parsed blocks, `is_page()` compares against `$GLOBALS['jmc_test_current_page']`, `wp_get_icon()` returns a placeholder SVG recording the icon name for the icons the theme uses, and `home_url()` and `__()` return fixed or untranslated values. Legal and menu tests add: `get_posts()` filters the `jmc_test_posts` fixtures by `post_type`, `name`, `post_parent`, and `post_status`, returns every match when `posts_per_page` is `-1`, and records each query in `$GLOBALS['jmc_test_get_posts_queries']`; `get_bloginfo()` reads `$GLOBALS['jmc_test_bloginfo']`; `wp_date()` formats a fixed time in 2030; `get_option()` and `update_option()` use `$GLOBALS['jmc_test_options']`; `wp_insert_post()` records posts in `$GLOBALS['jmc_test_inserted_posts']`; and `add_action()` records hooks in `$GLOBALS['jmc_test_actions']`. Tests that set these globals must unset them in `tearDown()`. Services tests also load `tests/phpunit/doubles/services.php`, whose `jmc_test_wp_fixture()` writes options and pages into those shared globals and adds guarded doubles for page lookup by slug, permalinks, post meta, the icon registry (`tests/phpunit/doubles/class-wp-icons-registry.php`), `render_block()` for the core Icon block, and the request context (`wp_is_serving_rest_request()` and `current_user_can()`).

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
