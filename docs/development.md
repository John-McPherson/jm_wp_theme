# Development

## Prerequisites

- A local WordPress version matching the one declared in `style.css`
- PHP 8.3+
- Node.js 24.18.0 or a later 24.x, selected through `.nvmrc`
- npm and Composer 2
- `rsync`
- Docker Desktop or another Docker-compatible runtime for `wp-env`
- A Git working copy inside `wp-content/themes`

## Initial setup

```bash
nvm use
npm ci
composer install
npm run build
```

Activate the theme, then start JavaScript and Sass watchers with `npm start`. Static files in `src/assets/` are copied only by the build command, so rerun `npm run build:assets` or `npm run build` when they change.

### Isolated WordPress environment

The browser suite uses `wp-env` rather than a developer's normal WordPress database. Copy the example environment file and start Docker before running the suite:

```bash
cp .env.e2e.example .env.e2e
npm run env:start
npm run test:e2e
npm run env:stop
```

The development site is available at `http://localhost:8888` with username `admin` and password `password`. Playwright runs against Chromium, Firefox, and WebKit. Generated reports and failure evidence are written under `artifacts/e2e/` and are not committed.

`.nvmrc` selects Node 24, and `package.json` `engines` requires 24.18.0 or a later 24.x. `.npmrc` sets `engine-strict`, so `npm install` and `npm ci` fail with `EBADENGINE` on any other version; run `nvm install 24` to update. Supported versions are set out in [Platform support](support.md).

## Branch and issue workflow

- Branch from `dev` and open feature/fix pull requests back into `dev`.
- Use one GitHub issue for one independently reviewable outcome.
- Assign one version milestone, one `type:` label, one `priority:` label, and all relevant `area:` labels.
- Link the issue in the pull request and update documentation when a command, contract, token, workflow, or supported platform changes.
- Release by merging `dev` into `staging`, then `staging` into `main`; see [Releasing](releasing.md#branches).

See [Roadmap and issue workflow](roadmap.md) for milestone scope and label conventions.

### Project board automation

Milestones are releases; the *JMC Theme* project's **Sprint** field holds one-week iterations. Filter a view by `sprint:@current` to see the current sprint.

`.github/workflows/project-status.yml` moves issues across the board's **Status** column:

| Event | New status |
| --- | --- |
| Its Sprint becomes active (checked hourly, or run the workflow manually) | Ready |
| A branch named `<issue-number>-…` is created on GitHub (pushed, or created from the issue page) | In progress |
| A pull request is merged into `dev` (its head branch's issue number and any linked closing issues) | Release candidate |
| A pull request is merged into `main` (its own issues, plus those of every pull request merged into the release, found from its `Merge pull request #N` commits) | Released |

Issues only move forwards (Backlog → Ready → In progress → Release candidate → Released) and are added to the project if missing. Branches created only locally do nothing until pushed. GitHub sends no event when a project field changes, so the Ready move polls the board; it runs from `main` only, so it starts once the workflow is released. Because active-sprint cards leave Backlog automatically, the **Sprint** view filters it out (`sprint:@current -status:Backlog`); **Next Sprint** (`sprint:@next`) keeps Backlog, where upcoming cards wait until their sprint starts.

The workflow needs a `PROJECT_TOKEN` repository secret: a classic personal access token with the `project` and `repo` scopes. The default `GITHUB_TOKEN` cannot edit a user-owned project.

## Working conventions

### PHP

- Add `declare(strict_types=1);` to new PHP files.
- Add scalar and return types where WordPress compatibility permits.
- Treat block attributes and external values as untrusted.
- Validate before use and escape at the final output boundary.
- Keep hooks in focused `inc/` modules and reusable markup in components.

### TypeScript

- Treat `block.json` as the public attribute contract.
- Type editor props and attribute updates; do not add new `any` or unjustified assertions.
- Keep editor and frontend behaviour aligned.
- Avoid frontend JavaScript unless interaction requires it.
- The project is not yet in TypeScript strict mode; tightening it is roadmap work.

### SCSS and design tokens

- Consume existing semantic tokens before adding values.
- Put global foundations in shared layers and feature rules beside the feature.
- Change the cascade-layer order only in `src/scss/layers/index.scss`; `inc/enqueue.php` prints it before any other CSS, so block stylesheets must not `@use` it.
- Keep each component's rules in one stylesheet; depend on another block's style handle in `block.json` rather than duplicating its rules.
- Validate every palette and interactive state in both editor and frontend.
- Keep `theme.json` and SCSS token definitions deliberately synchronized.

### Generated files

Edit `src/`, never `build/`, as the source of truth. Run a production build before review.

### Dependency advisories

Every npm dependency is a development dependency, so `npm audit --omit=dev` must report 0. The full `npm audit` should have no critical advisories. The remaining high advisories come from three transitive packages that only the upstream tooling can update, and we don't force them with `overrides`:

| Package | Pulled in by | Advisory | Status |
| --- | --- | --- | --- |
| `isomorphic-git` 1.37.x | `@wordpress/env` | Path traversal and arbitrary file write on clone ([GHSA-cw2g-2vwq-88w6](https://github.com/advisories/GHSA-cw2g-2vwq-88w6), [GHSA-9qw7-j9xw-fv9c](https://github.com/advisories/GHSA-9qw7-j9xw-fv9c)) and a resolution loop ([GHSA-96wc-2m84-2cj7](https://github.com/advisories/GHSA-96wc-2m84-2cj7)) | Fixed in 1.38.6; waiting for `@wordpress/env` to update. Only used to fetch WordPress core for the local environment. |
| `serialize-javascript` 6.x | `copy-webpack-plugin` in `@wordpress/scripts` | RCE, DoS and XSS when serialising untrusted input ([GHSA-5c6j-r48x-rmvq](https://github.com/advisories/GHSA-5c6j-r48x-rmvq), [GHSA-qj8w-gfj5-8c6v](https://github.com/advisories/GHSA-qj8w-gfj5-8c6v), [GHSA-m5mm-p8h7-m29v](https://github.com/advisories/GHSA-m5mm-p8h7-m29v)) | Fixed in 7.1.3; waiting for `@wordpress/scripts` to update. Only serialises our own build config. |
| `picomatch` | Linters, bundlers and test tooling | ReDoS through repeated extglobs ([GHSA-fc5q-39xc-vjpf](https://github.com/advisories/GHSA-fc5q-39xc-vjpf)) | No patched release yet. Only matches our own glob patterns. |

When upgrading dependencies, re-run `npm audit` and remove a row once its fix arrives through the parent package.

## Translations

Every translatable string uses the `jmc-theme` text domain: the `style.css` header, `__()` and friends in PHP and TypeScript, and `textdomain` in each `block.json`. `inc/setup.php` loads translations from `languages/`, which `style.css` declares as its `Domain Path`.

### Writing translatable strings

- Pass whole sentences, not fragments joined together, so translators can reorder them.
- Put a `/* translators: … */` comment directly above any call whose string contains a placeholder, and number placeholders (`%1$s`, `%2$s`) when there is more than one.
- Use `_n()` whenever a number decides the wording, even if the number is usually more than one.
- Use `_x()` when a short string could be translated more than one way.

### Validation

CI rejects the wrong or a missing text domain in three places:

- PHP: PHPCS's `WordPress.WP.I18n` sniff (`npm run lint:phpcs`), which also flags missing translator comments and unordered placeholders.
- TypeScript: the `@wordpress/i18n-text-domain` ESLint rule, pinned to `jmc-theme` in `eslint.config.mjs` (`npm run lint:js`). The rest of `@wordpress/eslint-plugin`'s i18n rules check translator comments and placeholders.
- Metadata: `TextDomainTest` checks the `style.css` header and every `block.json` (`composer test`).

### Extracting the translation template

```bash
npm run i18n:pot
```

This builds the block scripts and writes `languages/jmc-theme.pot` with WP-CLI's `make-pot`, installed as a Composer dev dependency, so `composer install` must have run. It reads PHP, `block.json`, `theme.json`, the palette style variations and patterns from source, and editor strings from the compiled scripts in `build/js`: WP-CLI cannot parse TypeScript, and the build keeps `translators:` comments. `npm run i18n:make-pot` skips the build when `build/js` is already current. Both commands delete `dist/` first, because `make-pot` reads the theme header from `dist/style.css` when it exists, even though `dist/` is excluded.

The POT file is generated, so it is not committed. `npm run package` generates it and ships it in `dist/languages/`.

### Testing a locale

Check translations with a test locale in `wp-env` (`npm run env:start`):

1. Generate the template with `npm run i18n:pot`.
2. Copy `languages/jmc-theme.pot` to `languages/fr_FR.po` and translate a few representative strings with a visible marker such as `[FR] `. Cover a PHP-rendered block (the enquiry form), a template part (the footer's "Site created by %s"), a block title from `block.json`, and an editor control (Hero's "Palette Settings"). Set the `Language` header to `fr_FR`.
3. Compile it, the `.mo` file first, because `make-json` removes the editor strings from the `.po` file:

   ```bash
   vendor/bin/wp i18n make-mo languages/fr_FR.po
   vendor/bin/wp i18n make-json languages/fr_FR.po
   ```

4. Switch the site language. WordPress only accepts an installed locale:

   ```bash
   npx wp-env run cli wp language core install fr_FR --activate
   ```

5. Check the frontend and the block editor for the marked strings. Editor controls are translated from the generated `jmc-theme-fr_FR-<hash>.json` files, and block titles and descriptions from the `.mo` file, server-side.
6. Clean up: `npx wp-env run cli wp language core uninstall fr_FR`, `npx wp-env run cli wp option delete WPLANG`, and delete the `fr_FR` files from `languages/` so they don't end up in a package.

## Debugging

- Missing block: build and confirm its compiled directory contains `block.json` under `build/js/blocks`.
- Missing shared styles: confirm `build/css/style.css` exists; enqueueing intentionally returns early otherwise.
- Missing component: confirm the requested component resolves to a PHP file under `components/`.
- Editor/frontend mismatch: compare both SCSS entry points, wrapper classes, attributes, and block context.
- Missing pattern: WordPress caches the theme's pattern files until the theme version changes. Add `define( 'WP_DEVELOPMENT_MODE', 'theme' );` to your local `wp-config.php` so new and edited patterns appear on reload. Do not set it in production, where it turns off pattern and `theme.json` caching.
- Use `WP_DEBUG` and `WP_DEBUG_LOG` locally; never expose debug output in production.

## Definition of done

- Type-checking and all configured linters pass.
- Production build and package commands pass when packaging is affected.
- No PHP warnings or browser console errors occur in the changed flow.
- Empty, default, invalid, and populated states are safe.
- Editor and frontend presentation remain aligned.
- Responsive, keyboard, focus, reduced-motion, and relevant screen-reader behaviour are checked.
- Documentation and block contracts are updated.
- The issue acceptance criteria are met and verification is recorded in the pull request.

See [Testing](testing.md) for the current gates and manual matrix.
