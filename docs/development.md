# Development

## Prerequisites

- A local WordPress version matching the `7.1` minimum currently declared in `style.css`
- PHP 8.0+
- A current Node.js LTS release and npm
- `rsync`
- A Git working copy of the theme inside `wp-content/themes`

## Initial setup

```bash
npm install
npm run build
```

Activate the theme in WordPress, then start the watchers:

```bash
npm start
```

The development command watches JavaScript and Sass. Static files under `src/assets/` are copied by `npm run build`; rerun that command when assets change.

There is no `engines` contract in `package.json` yet. Record and enforce the chosen Node/npm versions before the first production release.

## Working conventions

### PHP

- Add `declare(strict_types=1);` immediately after the opening tag.
- Add scalar and return types wherever WordPress compatibility permits.
- Validate block attributes before use.
- Escape at output time with the appropriate WordPress function.
- Prefer named arguments when they make helper calls clearer.
- Keep hooks in focused `inc/` modules and markup in components.

### TypeScript

- Treat `block.json` as the public attribute contract.
- Type editor props and attribute updates.
- Keep edit/save/render behaviour aligned.
- Avoid frontend JavaScript unless the rendered feature requires interaction.

### SCSS

- Consume existing tokens before adding new values.
- Put global foundations in the shared SCSS layers and feature rules next to the feature.
- Use semantic palette variables instead of raw colour primitives in components.
- Confirm editor and frontend output both compile and look equivalent.

### Generated files

Edit files under `src/`, not compiled files under `build/`. Always run a production build before review so missing imports and compilation errors are caught.

When `theme.json` tokens change, run the token generator used by the project and review the generated SCSS diff before rebuilding. Generated files should be reproducible from their source.

## Adding a feature

1. Identify whether it is a block, reusable component, helper, or global foundation.
2. Define the smallest stable public contract.
3. Implement validation and empty states.
4. Add frontend and editor styles where applicable.
5. Test responsive behaviour, keyboard access, and all palettes.
6. Update the relevant documentation.

## Debugging

- If a custom block is missing, run `npm run build` and confirm its compiled directory contains `block.json` under `build/js/blocks`.
- If styles are missing, confirm `build/css/style.css` exists; the enqueue module intentionally returns early when it does not.
- If a component is missing, verify the requested component name maps to a `.php` file under `components/`.
- If editor and frontend differ, inspect both SCSS entry points and the block's wrapper classes/context.
- Enable `WP_DEBUG` and `WP_DEBUG_LOG` locally; never expose debug output in production.

## Definition of done

- Production build succeeds.
- No PHP warnings or browser console errors occur in the changed flow.
- Empty, default, invalid, and populated states behave safely.
- Frontend and editor presentation are acceptably aligned.
- Keyboard, focus, reduced-motion, and responsive behaviour are checked.
- Documentation and the block contract are updated.
- The generated release package is smoke-tested when the change affects runtime packaging.

See [Testing](testing.md) for the current manual checks and planned automation.
