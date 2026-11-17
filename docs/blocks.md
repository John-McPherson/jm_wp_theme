# Block system

## Registration

On `init`, the theme recursively scans `build/js/blocks`. Each directory containing `block.json` is registered with `register_block_type()`. Adding source code without producing compiled metadata will not register a block.

Custom blocks use the `jmc/` namespace. The current global allowlist exposes only registered blocks with that prefix.

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

| Block                 | Purpose                                                                            | Rendering                 | Notes                                                                                                                  |
| --------------------- | ---------------------------------------------------------------------------------- | ------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| `jmc/button`          | Linked call-to-action                                                              | Dynamic PHP               | Internal composition block; primary button by default; direct inserter disabled                                        |
| `jmc/column`          | Groups related inner content within a section                                      | Dynamic PHP               | Structural composition block; direct inserter disabled                                                                 |
| `jmc/footer`          | Site footer container with a selectable palette                                    | Dynamic PHP               | Site chrome; direct inserter disabled; single instance; allows only footer blocks, each rendered as a column; inverse and locked in the footer part |
| `jmc/footer-business` | Business name, address, telephone, email, and service area                          | Dynamic PHP               | Restricted to `jmc/footer`; direct inserter disabled; single instance; locked against removal in the footer part      |
| `jmc/footer-navigation` | A column of links from a selected `wp_navigation` menu                          | Dynamic PHP               | Restricted to `jmc/footer`; insertable there, multiple allowed (one column each); no toggle or view script            |
| `jmc/header-cta`      | Optional header call-to-action linking to a page, URL, or telephone number         | Dynamic PHP               | Restricted to `jmc/navigation`; direct inserter disabled; single instance; locked against removal in the header part   |
| `jmc/navigation`      | Primary navigation rendered from a selected `wp_navigation` menu                    | Dynamic PHP + view script | Site chrome; direct inserter disabled; allows only `jmc/header-cta`, rendered as the last menu item                     |
| `jmc/heading`         | Semantic heading with configurable level                                           | Dynamic PHP               | Uses `jmc/variant` context; level defaults to `2`; direct inserter disabled                                            |
| `jmc/paragraph`       | Paragraph or small label text                                                      | Dynamic PHP               | `default` and `label` variations; uses `jmc/variant`; direct inserter disabled                                         |
| `jmc/service-card`    | Linked service summary with a predefined icon                                      | Dynamic PHP               | Restricted to `jmc/service-grid`; allows Heading and Paragraph children                                                |
| `jmc/cta-banner`      | Two-column call-to-action section                                                  | Dynamic PHP               | Palettes: default, secondary, inverse; left/right order; defaults to inverse/right                                     |
| `jmc/hero`            | Page introduction with composable content and optional decorative background image | Dynamic PHP               | Single-instance support; provides `jmc/variant`; supports default, secondary, and inverse palettes; invalid values fall back to default |
| `jmc/service-grid`    | Introductory content beside a responsive service-card grid                         | Dynamic PHP               | Allows Column children; palettes and left/right order; defaults to default/left                                        |
| `jmc/text-with-image` | Composable text content paired with an image                                       | Dynamic PHP + view script | Palettes and left/right order; defaults to default/right; contains legacy-looking attributes that need contract review |

This table should be updated whenever a block is added, renamed, deprecated, or removed.

### Composition model

- Section blocks are assigned to `jmc-section` and are intended for direct insertion.
- Content blocks are used inside section templates; their `supports.inserter` value is `false` where explicitly declared.
- `allowedBlocks`, `ancestor`, and block locking should keep editor composition valid without relying only on author training.
- Section blocks use the registered `jmc-section` category. Internal content-block metadata still contains legacy `jm` and `jmc-blocks` categories and should be normalised.

## Adding a block

1. Create the source directory and `block.json`.
2. Use the `jmc/<name>` namespace and appropriate `jmc-*` category.
3. Define attributes with valid defaults and explicit types.
4. Implement and type the editor component.
5. Implement server rendering and validate every incoming attribute.
6. Use existing PHP components for reusable markup.
7. Add block styles and ensure the editor imports them where required. Wrap rules in a theme layer (usually `@layer blocks`) and do not `@use` `scss/layers`; the layer order is printed globally by `inc/enqueue.php`. Style only the block's own classes: if a block relies on another block's styles, list that block's style handle in `block.json` (for example `"style": ["jmc-button-style", "file:./style-index.css"]`) instead of copying its rules.
8. Run `npm run build` and confirm compiled metadata exists under `build/js/blocks`.
9. Test empty, default, populated, and deliberately invalid attributes.
10. Test responsive layouts, all supported palettes, keyboard operation, and editor/frontend parity.
11. Add the block to this document.

## Palette and variant policy

Palettes are semantic presentation contracts, not arbitrary colour choices. A block must expose only palettes it can render accessibly. The default stored in `block.json`, options shown in the editor, PHP allowlist, and SCSS selector must agree exactly.

### Hero palette contract

The Hero block uses the same palette vocabulary in `block.json`, editor controls, PHP validation, and frontend classes:

| Attribute value | Frontend class |
| --- | --- |
| `default` | No additional palette class |
| `secondary` | `jmc-palette--secondary` |
| `inverse` | `jmc-palette--inverse` |

The omitted and declared default value is `default`. PHP treats unexpected palette input as `default`, so malformed or legacy content renders without an undefined-key notice and without an additional palette class. When changing this contract, verify the omitted value, every supported value, and malformed input under WordPress debug mode in both the editor and frontend.

Variants change structural presentation. If descendants require the value, provide it through block context with a namespaced key such as `jmc/variant` rather than copying attributes through unrelated blocks.

## Header call-to-action

`jmc/header-cta` stores a `text` label and a `link` object (`{ url, opensInNewTab }`, the shape saved by `LinkToolBar`). It is placed in `parts/header.html` as the only inner block of `jmc/navigation`, which renders it as the last `<li>` of the menu list. It therefore appears at the end of the desktop menu and inside the mobile dropdown, and it shares the dropdown's open, close, and Escape behaviour.

The link is validated by `jmc_safe_link_url()` (`inc/helpers/link.php`):

| Destination | Result |
| --- | --- |
| `http://` or `https://` URL with a host | Rendered unchanged |
| Site-relative path such as `/contact/` | Rendered unchanged |
| `tel:` link | Normalised to `tel:` plus an optional leading `+` and 3–15 digits; spaces, dashes, dots, and brackets are removed |
| Anything else, including `javascript:`, `data:`, `mailto:`, `//host`, and values containing whitespace or control characters | Rejected |

The block renders nothing when the label is empty or the link is missing, malformed, or rejected. Telephone links never open in a new tab. Output uses the `button-link` component with the `jmc-button--small` size modifier.

The block is not insertable, so a site whose header template part has been customised in the Site Editor does not pick it up from `parts/header.html`. Either reset the header template part, or add `<!-- wp:jmc/header-cta {"lock":{"remove":true}} /-->` inside the `jmc/navigation` block of the saved part.

## Footer

`parts/footer.html` is the `footer` template part, registered in `theme.json` and placed at the end of every template as a `<footer class="jmc-footer">` landmark. It contains:

```html
<!-- wp:jmc/footer {"palette":"inverse","lock":{"remove":true}} -->
  <!-- wp:jmc/footer-business {"lock":{"remove":true}} /-->
  <!-- wp:jmc/footer-navigation /-->
<!-- /wp:jmc/footer -->
```

### Footer container

`jmc/footer` paints its palette across the full width and centres its content in a `--jmc-container-xl` container. Each child block is one grid column: stacked on narrow screens, then side by side from the `$mobile` container width, at least `12rem` wide and wrapping as space runs out. The footer sets the smaller `p4` type style, and every footer link shares one hover and keyboard-focus treatment (highlight colour and underline). It renders nothing when its inner blocks produce no markup.

Its palette follows the Hero contract: `default` (no class), `secondary` (`jmc-palette--secondary`), and `inverse` (`jmc-palette--inverse`). The attribute default is `default`; the footer part sets `inverse`. Unknown or malformed values fall back to `default`.

### Business details

`jmc/footer-business` is the source of truth for the business details shown in the footer. Edit them in the Site Editor (**Appearance → Editor → Patterns → Template parts → Site footer**), directly on the block. Each field is optional and stored as a string attribute:

| Attribute | Output | Icon | Validation |
| --- | --- | --- | --- |
| `companyName` | Bold name linking to `home_url( '/' )` | None | — |
| `address` | Inside `<address>`; Shift+Enter line breaks become `<br>` | `core/map-marker` | — |
| `phone` | Inside `<address>`; a `tel:` link when valid | `jmc/phone` | `jmc_safe_link_url( 'tel:' … )`; an unusable number is shown without a link |
| `email` | Inside `<address>`; a `mailto:` link | `core/envelope` | `FILTER_VALIDATE_EMAIL`; an invalid address is hidden |
| `serviceArea` | Introduced by a bold, translatable "Areas covered:" label | `core/home` | — |

Values are saved by formatless `RichText`, so render code converts them with `jmc_plain_text()` (`inc/helpers/text.php`): line breaks become newlines, other markup is removed, entities are decoded once, and blank lines are dropped. Output is escaped afterwards. Empty fields are omitted, `<address>` is omitted when there is no address, phone, or email, and the block renders nothing when every field is empty.

### Icons

Icons come from the core icon registry, the one behind the core Icon block, through `wp_get_icon()`. They are decorative (`aria-hidden`), because each line's text already says what it is. Core's library has no telephone handset, so `inc/icons.php` registers a `jmc` icon collection containing `jmc/phone`, the WordPress Dashicons handset. Theme icons also appear in the core Icon block's picker. To add one, register it in `inc/icons.php` and mirror it in the editor component.

The registry strips the `fill` attribute from icon SVGs, so styles must set `fill: currentcolor` for an icon to follow `color`. Footer icons use `--jmc-text-highlight`.

### Footer navigation

Each `jmc/footer-navigation` block renders a flat list of links from the published `wp_navigation` menu chosen in its sidebar. Unlike `jmc/navigation`, it does not pick a menu automatically, has no mobile toggle, and loads no script. Submenu links are flattened into the list. The `<nav>` is labelled with the menu title, or "Footer" when the menu has none, so give each menu a meaningful title. The current page's link gets `aria-current="page"`; custom links never do. The block renders nothing when no menu is selected, the post is not a menu, or the menu has no links.

Add a column by inserting another Footer Navigation block inside the footer; remove one with the block toolbar. Create menus in **Appearance → Editor → Navigation**, or with WP-CLI:

```bash
wp post create --post_type=wp_navigation --post_status=publish --post_title="Services"
```

### Customised footer parts

Once the footer part has been saved in the Site Editor, the saved copy overrides `parts/footer.html`, so later changes to the theme file do not appear. Reset the **Site footer** template part to pick them up, or add the missing blocks to the saved part by hand.

## Button sizes

`.jmc-button` is the default size. Add `.jmc-button--small` for dense UI such as the header; it changes only padding, type size, and border width, never colours, so it combines with `.jmc-button__secondary` and every palette.

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

- Normalise internal content-block categories to one registered category, or remove category metadata where it has no effect.
- Revisit the `jmc/`-only global allowlist before FSE templates require core blocks.
- Change the `style.css` text-domain header from `jmc-custom-theme` to the canonical `jmc-theme` used by translations and block metadata.
- Review and remove or formally support the older top-level content attributes still declared by `jmc/text-with-image`.
