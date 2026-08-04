# Block system

## Registration

On `init`, the theme recursively scans `build/js/blocks`. Each directory containing `block.json` is registered with `register_block_type()`. Adding source code without producing compiled metadata will not register a block.

Custom blocks use the `jm/` namespace. The current global allowlist exposes only registered blocks with that prefix.

## Contract rules

- `block.json` is the canonical name, attributes, context, script, style, and render contract.
- Attribute defaults must be valid values accepted by both editor and PHP rendering.
- Enumerated attributes such as palette and variant must use identical allowlists everywhere.
- PHP must validate attributes even when the editor constrains the controls.
- Editor markup should approximate frontend layout and presentation.
- Dynamic blocks return server-rendered markup; save functions should not duplicate it.
- A block that has no meaningful content should avoid emitting empty wrapper markup.

## Current blocks

| Block | Purpose | Rendering | Notes |
|---|---|---|---|
| `jm/hero` | Page-level introductory section with composable inner content and an optional background image | Dynamic PHP | Provides variant context; palette/default handling must be made consistent before release |
| Content/layout blocks | Compose reusable page sections and contained content | Dynamic PHP | Active development; treat each `block.json` as its current contract |

This table should be updated whenever a block is added, renamed, deprecated, or removed.

## Adding a block

1. Create the source directory and `block.json`.
2. Use the `jm/<name>` namespace and appropriate `jm-*` category.
3. Define attributes with valid defaults and explicit types.
4. Implement and type the editor component.
5. Implement server rendering and validate every incoming attribute.
6. Use existing PHP components for reusable markup.
7. Add block styles and ensure the editor imports them where required.
8. Run `npm run build` and confirm compiled metadata exists under `build/js/blocks`.
9. Test empty, default, populated, and deliberately invalid attributes.
10. Test responsive layouts, all supported palettes, keyboard operation, and editor/frontend parity.
11. Add the block to this document.

## Palette and variant policy

Palettes are semantic presentation contracts, not arbitrary colour choices. A block must expose only palettes it can render accessibly. The default stored in `block.json`, options shown in the editor, PHP allowlist, and SCSS selector must agree exactly.

Variants change structural presentation. If descendants require the value, provide it through block context with a namespaced key such as `jm/variant` rather than copying attributes through unrelated blocks.

## Images

Background images are decorative: they must not contain information needed to understand the page. A meaningful image must render as an `<img>` or image block with appropriate alternative-text handling.

## Deprecation

Before changing saved attributes or names:

1. Determine whether existing post content will still parse and render.
2. Add a Gutenberg deprecation/migration when saved content requires it.
3. Keep server-side fallbacks for safely recoverable legacy values.
4. Document the migration and test existing content before release.

## Current issues to resolve

- Correct the malformed custom-category return value in `inc/blocks.php`.
- Align Hero palette defaults and allowlists across metadata, editor, PHP, and SCSS.
- Revisit the `jm/`-only global allowlist before FSE templates require core blocks.
