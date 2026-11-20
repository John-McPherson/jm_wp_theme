# JMC Custom Theme

Custom Full Site Editing WordPress theme for AMC Electrical, built with PHP, TypeScript, SCSS, Gutenberg blocks, and `theme.json`.

> **Status:** `0.1.0` foundation release in active development. The `dev` branch is the integration branch; `main` is reserved for versioned releases. See the [roadmap](docs/roadmap.md).

## Requirements

- WordPress 7.1, as declared in `style.css`
- PHP 8.3 or later
- Node.js 24.18.0 or a later 24.x, as declared in `.nvmrc` and `package.json` `engines` (npm refuses other versions)
- npm
- Composer 2
- `rsync` for asset builds and packaging
- Docker Desktop or another Docker-compatible runtime for the isolated WordPress E2E environment

## Setup

Clone the repository into `wp-content/themes`, then install dependencies:

```bash
nvm use
npm ci
composer install
npm run build
```

Activate **JMC Custom Theme** in WordPress. Start the TypeScript and Sass watchers with:

```bash
npm start
```

## Commands

| Command | Purpose |
| --- | --- |
| `npm start` | Watch block JavaScript and Sass sources |
| `npm run build` | Build static assets, compressed CSS, and block JavaScript |
| `npm run build:assets` | Copy `src/assets` to `build/assets` |
| `npm run build:icons` | Generate the theme's Lucide icons into `build/assets/icons/lucide` |
| `npm run build:styles` | Compile frontend and editor Sass |
| `npm run build:js` | Build Gutenberg block scripts with `wp-scripts` |
| `npm run typecheck` | Type-check TypeScript without emitting files |
| `npm run lint:php` | Check PHP syntax |
| `npm run lint:js` | Lint JavaScript and TypeScript |
| `npm run lint:styles` | Lint SCSS |
| `npm run lint:phpcs` | Run WordPress Coding Standards |
| `npm run env:start` | Start the isolated WordPress environment at `http://localhost:8888` |
| `npm run env:stop` | Stop the isolated WordPress environment |
| `npm run test:e2e` | Run Playwright in Chromium, Firefox, and WebKit |
| `npm run test:e2e:headed` | Run Playwright with visible browser windows |
| `npm run test:e2e:report` | Open the latest HTML report |
| `npm run i18n:pot` | Build the block scripts and generate `languages/jmc-theme.pot` |
| `npm run i18n:make-pot` | Generate `languages/jmc-theme.pot` from the current build |
| `npm run package` | Build, generate the translation template, and assemble the release directory in `dist/` |

## Structure

| Path | Responsibility |
| --- | --- |
| `functions.php` | Theme bootstrap only |
| `inc/` | Setup, asset loading, block registration, and helpers |
| `components/` | Reusable server-rendered PHP components |
| `src/` | Authoring sources: SCSS, TypeScript, blocks, editor plugins, icon lists, and static assets |
| `scripts/` | Build and check scripts, such as the Lucide icon generator and the palette contrast report |
| `build/` | Generated runtime assets used by WordPress |
| `templates/` | FSE templates; currently the minimal `index.html` fallback |
| `parts/` | Registered FSE template parts, including the editable site header |
| `tests/e2e/` | Playwright browser tests, helpers, and committed fixtures |
| `artifacts/e2e/` | Ignored Playwright reports, traces, videos, screenshots, and failure output |
| `theme.json` | WordPress settings, design primitives, and template-part registration |
| `styles/colors/` | Colour palette style variations, switchable in Appearance → Editor → Styles |
| `docs/` | Architecture and operational documentation |

## Engineering documentation

- [Architecture](docs/architecture.md)
- [Development](docs/development.md)
- [Blocks](docs/blocks.md)
- [Design system](docs/design-system.md)
- [Accessibility](docs/accessibility.md)
- [Manual accessibility QA checklist](docs/accessibility-qa.md)
- [Testing](docs/testing.md)
- [Platform support](docs/support.md)
- [Roadmap and issue workflow](docs/roadmap.md)
- [Releasing](docs/releasing.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## Current scope

The theme provides nine dynamic, server-rendered blocks: five internal composition blocks and four editor-insertable section blocks. GitHub Actions installs locked dependencies, type-checks, lints PHP/TypeScript/SCSS, runs PHPCS, builds the theme, packages `dist/`, and verifies required package paths.

The site-identity header template part, cross-browser Playwright smoke coverage and automated axe accessibility scans are now configured. Primary navigation, header CTA, footer, complete template coverage, visual regression baselines, and clean-install package verification remain planned work.

## Browser support

The last two major versions of Chrome, Edge, Firefox and Safari (macOS and iOS), plus current Chrome for Android and Samsung Internet, from 320 CSS px wide. See [Platform support](docs/support.md) for the full matrix of WordPress, PHP, Node, browser, device and assistive-technology versions, and how it changes.

## Licence

GNU General Public License v2 or later. See `style.css`.
