# Development

## Prerequisites

- A local WordPress version matching the minimum declared in `style.css`
- PHP 8.0+
- Node.js 24, selected through `.nvmrc`
- npm and Composer 2
- `rsync`
- A Git working copy inside `wp-content/themes`

## Initial setup

```bash
nvm use
npm ci
composer install
npm run build
```

Activate the theme, then start JavaScript and Sass watchers with `npm start`. Static files in `src/assets/` are copied only by the build command, so rerun `npm run build:assets` or `npm run build` when they change.

`.nvmrc` is the current Node contract. `package.json` does not yet declare an `engines` range, so non-nvm environments must select the matching major version explicitly.

## Branch and issue workflow

- Branch from `dev` and open feature/fix pull requests back into `dev`.
- Use one GitHub issue for one independently reviewable outcome.
- Assign one version milestone, one `type:` label, one `priority:` label, and all relevant `area:` labels.
- Link the issue in the pull request and update documentation when a command, contract, token, workflow, or supported platform changes.
- Formal release pull requests merge `dev` into `main`.

See [Roadmap and issue workflow](roadmap.md) for milestone scope and label conventions.

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
- Validate every palette and interactive state in both editor and frontend.
- Keep `theme.json` and SCSS token definitions deliberately synchronized.

### Generated files

Edit `src/`, never `build/`, as the source of truth. Run a production build before review.

## Debugging

- Missing block: build and confirm its compiled directory contains `block.json` under `build/js/blocks`.
- Missing shared styles: confirm `build/css/style.css` exists; enqueueing intentionally returns early otherwise.
- Missing component: confirm the requested component resolves to a PHP file under `components/`.
- Editor/frontend mismatch: compare both SCSS entry points, wrapper classes, attributes, and block context.
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
