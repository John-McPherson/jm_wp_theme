# JMC Custom Theme

Custom Full Site Editing WordPress theme for AMC Electrical, built with PHP, TypeScript, SCSS, Gutenberg blocks, and `theme.json`.

> **Status:** `0.1.0` foundation release in active development. The `dev` branch is the source of truth; `main` is reserved for versioned releases.

## Requirements

- WordPress 7.1 or later (as declared in `style.css`)
- PHP 8.0 or later
- A current Node.js LTS release (recommended; not yet enforced by `package.json`)
- npm
- Composer 2
- `rsync` for asset builds and packaging

## Setup

Clone the repository into `wp-content/themes`, then install dependencies:

```bash
npm ci
composer install
```

Build the theme assets:

```bash
npm run build
```

Activate **JMC Custom Theme** in WordPress.

For development with TypeScript and Sass watchers:

```bash
npm start
```

## Commands

| Command                | Purpose                                             |
| ---------------------- | --------------------------------------------------- |
| `npm start`            | Watch block JavaScript and Sass sources             |
| `npm run build`        | Build assets, compressed CSS, and block JavaScript  |
| `npm run build:assets` | Copy `src/assets` to `build/assets`                 |
| `npm run build:styles` | Compile frontend and editor Sass                    |
| `npm run build:js`     | Build Gutenberg block scripts with `wp-scripts`     |
| `npm run typecheck`    | Type-check TypeScript without emitting files        |
| `npm run lint:php`     | Check PHP syntax                                    |
| `npm run lint:js`      | Lint JavaScript and TypeScript                      |
| `npm run lint:styles`  | Lint SCSS                                           |
| `npm run lint:phpcs`   | Run WordPress Coding Standards                      |
| `npm run package`      | Build and assemble the release directory in `dist/` |

The package inclusion list contains the runtime directories, but currently also references `parts/`, `patterns/`, and `languages/`, which do not exist. Packaging fails until those paths are removed or created. See [Releasing](docs/releasing.md).

## Structure

| Path            | Responsibility                                                  |
| --------------- | --------------------------------------------------------------- |
| `functions.php` | Theme bootstrap only                                            |
| `inc/`          | Theme setup, asset loading, block registration, and helpers     |
| `components/`   | Reusable server-rendered PHP components                         |
| `src/`          | Authoring sources: SCSS, TypeScript, blocks, and static assets  |
| `build/`        | Generated browser and block assets used by WordPress            |
| `templates/`    | FSE templates; currently only the minimal `index.html` fallback |
| `theme.json`    | WordPress design settings and primitive tokens                  |
| `docs/`         | Architecture and operational documentation                      |

## Engineering documentation

- [Architecture](docs/architecture.md)
- [Development](docs/development.md)
- [Blocks](docs/blocks.md)
- [Design system](docs/design-system.md)
- [Accessibility](docs/accessibility.md)
- [Testing](docs/testing.md)
- [Releasing](docs/releasing.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## Browser support

No formal browser matrix has been approved yet. Until one is defined and tested, target current stable versions of Chrome, Edge, Firefox, and Safari, plus current iOS Safari.

## Current scope

The theme currently provides nine server-rendered blocks: five internal content/composition blocks and four editor-insertable section blocks. GitHub Actions runs type-checking, PHP/JS/SCSS linting, PHPCS, and a production build. Site header, navigation, footer, complete template coverage, automated tests, and a verified release package remain future milestones.

## Licence

GNU General Public License v2 or later. See the header in `style.css`.
