# JM Custom Theme

Custom Full Site Editing WordPress theme for AMC Electrical, built with PHP, TypeScript, SCSS, Gutenberg blocks, and `theme.json`.

> **Status:** `0.1.0` foundation release in active development. The `dev` branch is the source of truth; `main` is reserved for versioned releases.

## Requirements

- WordPress 7.1 or later (as declared in `style.css`)
- PHP 8.0 or later
- A current Node.js LTS release (recommended; not yet enforced by `package.json`)
- npm
- `rsync` for asset builds and packaging

## Setup

Clone the repository into `wp-content/themes`, then install dependencies:

```bash
npm install
```

Build the theme assets:

```bash
npm run build
```

Activate **JM Custom Theme** in WordPress.

For development with TypeScript and Sass watchers:

```bash
npm start
```

## Commands

| Command | Purpose |
|---|---|
| `npm start` | Watch block JavaScript and Sass sources |
| `npm run build` | Build assets, compressed CSS, and block JavaScript |
| `npm run build:assets` | Copy `src/assets` to `build/assets` |
| `npm run build:styles` | Compile frontend and editor Sass |
| `npm run build:js` | Build Gutenberg block scripts with `wp-scripts` |
| `npm run package` | Create `dist/`; currently incomplete and not suitable for release |

The current package command omits runtime PHP directories such as `inc/` and `components/`. See [Releasing](docs/releasing.md) before distributing the theme.

## Structure

| Path | Responsibility |
|---|---|
| `functions.php` | Theme bootstrap only |
| `inc/` | Theme setup, asset loading, block registration, and helpers |
| `components/` | Reusable server-rendered PHP components |
| `src/` | Authoring sources: SCSS, TypeScript, blocks, and static assets |
| `scripts/` | Development utilities, including design-token generation |
| `build/` | Generated browser and block assets used by WordPress |
| `templates/` | FSE templates; currently only the minimal `index.html` fallback |
| `theme.json` | WordPress design settings and primitive tokens |
| `docs/` | Architecture and operational documentation |

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

The theme currently provides nine server-rendered blocks: five internal content/composition blocks and four editor-insertable section blocks. Site header, navigation, footer, complete template coverage, CI, automated tests, and a verified release package are future milestones. See [Blocks](docs/blocks.md) for the exact inventory.

## Licence

GNU General Public License v2 or later. See the header in `style.css`.
