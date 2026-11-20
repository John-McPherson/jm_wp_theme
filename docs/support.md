# Platform support

This policy states which WordPress, PHP, Node, browser, device and assistive-technology versions the theme supports, how they are tested, and when they change. Declared requirements (`style.css`, `composer.json`, `package.json`, `.nvmrc`, `.wp-env.json`) and CI must match it. When they disagree, this document is wrong or the code is: fix one of them in the same pull request.

## Support matrix

**Matrix version 1** — adopted 2026-11-19 for theme `0.3.0`.

| Platform | Supported | Declared in | Tested automatically |
| --- | --- | --- | --- |
| WordPress | 7.1 (the current major release) | `style.css` `Requires at least` / `Tested up to`, `.wp-env.json` | Playwright on the pinned 7.1 release; nightly against WordPress `master` |
| PHP | 8.3 and later, up to the newest stable release (8.5) | `style.css` `Requires PHP`, `composer.json` `require` and `config.platform` | PHPUnit, PHPCS and syntax lint on 8.3 and 8.5; Playwright runs WordPress on 8.3 |
| Node.js (development only) | 24, from 24.18.0 (with its bundled npm 11.16 or later) | `.nvmrc`, `package.json` `engines` (enforced by `.npmrc`) | Every Node CI job uses the newest 24.x through `.nvmrc` |
| Browsers | Last two major versions of Chrome, Edge, Firefox and Safari (macOS and iOS); current Chrome for Android and Samsung Internet | This document | Playwright on Chromium, Firefox and WebKit |
| Viewports | 320 CSS px wide and up, portrait and landscape, at up to 200% zoom | This document | Playwright checks for horizontal overflow at 320 px and header behaviour on short and narrow viewports |
| Input | Mouse, touch and keyboard only | This document | Playwright keyboard tests for navigation; touch is checked manually |
| Assistive technology | VoiceOver with Safari (macOS and iOS); NVDA with Chrome or Firefox (Windows) | This document | None. Checked manually at release candidates; see [Testing](testing.md#accessibility-release-matrix) |

### Why these versions

- **WordPress — latest major only.** The theme runs on one site that we deploy and update ourselves, so there is no installed base on older releases to protect. Supporting one major lets blocks use the current editor APIs without compatibility branches. The nightly run against `master` shows upcoming breakage before the next major ships.
- **PHP — 8.3 and later.** PHP 8.0 and 8.1 no longer receive security fixes, and 8.2 stops receiving them in December 2026. 8.3 is the oldest branch with active support. CI tests the floor and the newest release, because PHP breaks things at those two ends: features missing on the floor, and deprecations or removals on the newest.
- **Node 24.** Node is used only to build and test the theme; the packaged theme contains no Node code. Node 24 is an LTS release supported until April 2028. One pinned major keeps lockfile installs and builds the same on every machine. The floor of 24.18.0 comes from the locked `wp-env` dependencies (`@wp-playground/*`, `@php-wasm/*`), which require Node 24.18.0 and npm 11.16.0; with `engine-strict`, an older Node 24 would fail to install them anyway.
- **Browsers.** The stylesheets need `:has()`, `color-mix()`, container queries and cascade layers, which every listed browser has supported since 2023 (Safari 16.2, Firefox 121, Chrome and Edge 111). "Last two majors" stays inside that range and follows what visitors' browsers update to automatically. The three Playwright engines cover the rendering engines behind every listed browser: Blink (Chrome, Edge, Samsung Internet), Gecko (Firefox) and WebKit (Safari and every iOS browser).
- **Viewports.** 320 CSS px is the width WCAG 2.2 reflow (1.4.10) requires and the width of the smallest phones still in use. Supporting 200% zoom follows from the same criterion.
- **Assistive technology.** VoiceOver with iOS Safari covers the mobile visitors most likely to call or send an enquiry; NVDA is the most widely used free desktop screen reader. Together they cover both the WebKit and Blink/Gecko accessibility trees.

### What the automated matrix leaves out, and why

The suites run a matrix chosen by risk, not every combination:

- PHP versions between the floor and the newest (8.4) are not tested; a break that misses both ends is unlikely.
- Playwright runs each engine at desktop size and resizes the viewport for narrow and short-screen tests. It does not emulate mobile browsers' touch or browser chrome, so touch and iOS/Android behaviour are checked manually at release candidates.
- Real Edge, Samsung Internet and Safari builds are not run; their engines are.
- Assistive-technology output cannot be checked automatically.

## Unsupported combinations

| Combination | What happens |
| --- | --- |
| WordPress older than 7.1 | WordPress refuses to activate the theme because of `Requires at least`. |
| PHP older than 8.3 | WordPress refuses to activate the theme because of `Requires PHP`; `composer install` fails on the `php` requirement. |
| Node older than 24.18.0, or 25 and later | `npm install` and `npm ci` fail with an `EBADENGINE` error, because `.npmrc` sets `engine-strict`. |
| A newer WordPress than `Tested up to` | The theme may work but is unsupported until it has been tested and `Tested up to` raised. |
| Older or unlisted browsers | Not tested. Content, links, phone numbers and the enquiry form must stay usable, because navigation and the form work without JavaScript, but the layout and colours may degrade. Bugs are fixed only when they block a visitor journey. |

## Changing the matrix

A change to the matrix is its own pull request that:

1. updates the table above, bumps the matrix version and records the date;
2. updates every file in the "Declared in" column, the CI matrices in `.github/workflows/`, and any docs that repeat the versions ([README](../README.md), [Development](development.md), [Testing](testing.md));
3. passes CI on the new matrix.

### Upgrades

- **WordPress.** When a new major is released, test it with the full release checklist ([Releasing](releasing.md)), then raise `Tested up to`, `Requires at least` and the `.wp-env.json` pin together. Minor and security releases update the `.wp-env.json` pin without a matrix change.
- **PHP.** When PHP publishes a new release (each November), add it to CI as the newest version.
- **Node.** Move to the next LTS release once it enters active LTS (each October), updating `.nvmrc` and `engines` together.
- **Browsers.** The "last two majors" rule updates itself. Raise the CSS or JavaScript baseline only in a pull request that updates the browsers rationale above.

### End of support

- **WordPress.** Support for a major ends when the next one is adopted.
- **PHP.** Support for a branch ends at its end of security support (see [php.net/supported-versions](https://www.php.net/supported-versions.php)); raise the floor in the next theme release after that date.
- **Node.** Support for a release ends when the next LTS is adopted, and before its own end of life at the latest.
- Raising a floor is a breaking change and is noted in `CHANGELOG.md`.

## Ownership and review

- **Owner:** the theme maintainer (John McPherson) owns this policy and approves changes to the matrix.
- **Review cadence:** review the matrix before every theme release, at each new WordPress major release, and at least every three months. A review with no changes still records its date below.

| Reviewed | Matrix version | Outcome |
| --- | --- | --- |
| 2026-11-19 | 1 | Policy adopted (#48). |
