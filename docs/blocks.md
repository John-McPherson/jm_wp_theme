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

This inventory is pinned to the current `dev` branch. All current blocks are dynamic PHP blocks.

| Block | Purpose | Rendering | Notes |
|---|---|---|---|
| `jm/button` | Linked call-to-action | Dynamic PHP | Internal composition block; primary button by default; direct inserter disabled |
| `jm/column` | Groups related inner content within a section | Dynamic PHP | Structural composition block; direct inserter disabled |
| `jm/heading` | Semantic heading with configurable level | Dynamic PHP | Uses `jm/variant` context; level defaults to `2`; direct inserter disabled |
| `jm/paragraph` | Paragraph or small label text | Dynamic PHP | `default` and `label` variations; uses `jm/variant`; direct inserter disabled |
| `jm/service-card` | Linked service summary with a predefined icon | Dynamic PHP | Restricted to `jm/service-grid`; allows Heading and Paragraph children |
| `jm/cta-banner` | Two-column call-to-action section | Dynamic PHP | Palettes: default, secondary, inverse; left/right order; defaults to inverse/right |
| `jm/hero` | Page introduction with composable content and optional decorative background image | Dynamic PHP | Single-instance support; provides `jm/variant`; defaults to default palette |
| `jm/service-grid` | Introductory content beside a responsive service-card grid | Dynamic PHP | Allows Column children; palettes and left/right order; defaults to default/left |
| `jm/text-with-image` | Composable text content paired with an image | Dynamic PHP + view script | Palettes and left/right order; defaults to default/right; contains legacy-looking attributes that need contract review |

This table should be updated whenever a block is added, renamed, deprecated, or removed.

### Composition model

- Section blocks are assigned to `jm-section` and are intended for direct insertion.
- Content blocks are used inside section templates; their `supports.inserter` value is `false` where explicitly declared.
- `allowedBlocks`, `ancestor`, and block locking should keep editor composition valid without relying only on author training.
- Category metadata is currently inconsistent (`jm`, `jm-blocks`, and `jm-section`). Normalise it alongside the `inc/blocks.php` category fix.

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

Gutenberg evaluates deprecations for relevant block instances when content is opened in the editor. A successful migration is persisted only when that entity is saved; it does not automatically rewrite every post on the site.

Before changing saved attributes, saved `InnerBlocks` markup, or block names:

1. Determine whether existing post content will still parse and render.
2. Add a Gutenberg deprecation/migration when saved content requires it.
3. Keep server-side fallbacks for safely recoverable legacy values.
4. Document the migration and test existing content before release.

Changes confined to a dynamic block's PHP frontend markup normally do not need a Gutenberg deprecation. Site-wide rewrites require a separate, idempotent bulk migration—preferably a dry-run-capable WP-CLI command that recursively transforms parsed blocks, including posts, synced patterns, templates, and template parts.

## Current issues to resolve

- Correct the malformed custom-category return value in `inc/blocks.php`.
- Align Hero palette defaults and allowlists across metadata, editor, PHP, and SCSS.
- Revisit the `jm/`-only global allowlist before FSE templates require core blocks.
- Normalise block categories and text domains (`jm-custom-theme` is declared by the theme, while block metadata currently uses several values).
- Review and remove or formally support the older top-level content attributes still declared by `jm/text-with-image`.
