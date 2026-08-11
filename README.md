# JMC Custom Theme

Custom Full Site Editing WordPress theme for AMC Electrical, built with PHP, TypeScript, SCSS, Gutenberg blocks, and `theme.json`.

> **Status:** `0.1.0` foundation release in active development. The `dev` branch is the integration branch; `main` is reserved for versioned releases. See the [roadmap](docs/roadmap.md).

## Requirements

- WordPress 7.1 or later, as declared in `style.css`
- PHP 8.0 or later
- Node.js 24, as declared in `.nvmrc`
- npm
- Composer 2
- `rsync` for asset builds and packaging

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
| `npm run build:styles` | Compile frontend and editor Sass |
| `npm run build:js` | Build Gutenberg block scripts with `wp-scripts` |
| `npm run typecheck` | Type-check TypeScript without emitting files |
| `npm run lint:php` | Check PHP syntax |
| `npm run lint:js` | Lint JavaScript and TypeScript |
| `npm run lint:styles` | Lint SCSS |
| `npm run lint:phpcs` | Run WordPress Coding Standards |
| `npm run package` | Build and assemble the release directory in `dist/` |

## Structure

| Path | Responsibility |
| --- | --- |
| `functions.php` | Theme bootstrap only |
| `inc/` | Setup, asset loading, block registration, and helpers |
| `components/` | Reusable server-rendered PHP components |
| `src/` | Authoring sources: SCSS, TypeScript, blocks, and static assets |
| `build/` | Generated runtime assets used by WordPress |
| `templates/` | FSE templates; currently the minimal `index.html` fallback |
| `theme.json` | WordPress settings and design primitives |
| `docs/` | Architecture and operational documentation |

## Engineering documentation

- [Architecture](docs/architecture.md)
- [Development](docs/development.md)
- [Blocks](docs/blocks.md)
- [Design system](docs/design-system.md)
- [Accessibility](docs/accessibility.md)
- [Testing](docs/testing.md)
- [Roadmap and issue workflow](docs/roadmap.md)
- [Releasing](docs/releasing.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## Current scope

The theme provides nine dynamic, server-rendered blocks: five internal composition blocks and four editor-insertable section blocks. GitHub Actions installs locked dependencies, type-checks, lints PHP/TypeScript/SCSS, runs PHPCS, builds the theme, packages `dist/`, and verifies required package paths.

The header, navigation, footer, complete template coverage, behavioural tests, automated accessibility checks, and clean-install package verification remain planned work. The current global block allowlist also needs to become context-aware before core Site Editor blocks are required.

## Browser support

No formal browser matrix has been approved. Until one is documented and tested, target current stable Chrome, Edge, Firefox, Safari, and current iOS Safari.

## Licence

GNU General Public License v2 or later. See `style.css`.
