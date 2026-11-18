# Design system

## Principles

The theme uses two token layers:

1. **Primitives** define raw choices such as colour ramps, font families, fluid sizes, spacing, and radii.
2. **Semantic tokens and palettes** define roles such as surface, text, border, action, and inverse treatment.

Components should consume semantic roles. A raw value such as `brand-700` describes an ingredient, not why a component needs it.

## Sources of truth

| Concern                            | Source                                |
| ---------------------------------- | ------------------------------------- |
| WordPress colour palette           | `theme.json`, `styles/colors/`        |
| Font families and local font files | `theme.json` and `build/assets/fonts` |
| Fluid type scale                   | `theme.json`                          |
| Fluid spacing scale                | `theme.json`                          |
| Border radii                       | `theme.json`                          |
| Content and wide widths            | `theme.json`                          |
| Semantic palette mappings          | `src/scss/palettes/`                  |
| Shared SCSS tokens                 | `src/scss/tokens/`                    |
| Layout rules                       | `src/scss/layout/`                    |

## Colour

The primitive palette contains neutral `base`, `brand` and `accent` scales (blue and warm in the default palette; see [Colour palettes](#colour-palettes)), and status colours. Do not select a primitive solely because it looks right in one component. Add or reuse a semantic role, then validate the foreground/background/state combinations in every supported palette.

Current section palettes are `default`, `secondary`, and `inverse`. Store the unprefixed value in block attributes; rendering code maps it to the relevant `jmc-palette--*` class.

Surfaces raised above a section, such as service cards, use `--jmc-card-background`, which each palette sets to a colour distinct from its section background: `base-50` in `default` and `secondary`, `brand-800` in `inverse`.

The site logo fills its three parts from `--jmc-logo-primary`, `--jmc-logo-accent` and `--jmc-logo-secondary`. Like every role they alias `--jmc-color-*` tokens, never literal colours; they default in the semantic tokens and each palette reassigns them. The mappings and their contrast ratios are in [Site logo](blocks.md#site-logo).

Error messages and invalid-field borders use `--jmc-error-color`: the `error` preset on light sections, and the preset mixed 55% with `white` in `inverse`, because one red can't reach 4.5:1 on both light and dark backgrounds. Errors never rely on colour alone: each has a text message, and invalid fields also get a thicker border and `aria-invalid`.

Required contrast targets are documented in [Accessibility](accessibility.md).

### Colour palettes

Admins can switch the whole site between colour palettes in **Appearance → Editor → Styles → Colors → Palette**. Each palette is a colour-only style variation in `styles/colors/`, which replaces the `theme.json` preset colours. Every `--jmc-color-*` token reads a `--wp--preset--color--*` variable, so the semantic tokens, section palettes, blocks and logo follow the choice in the editor and on the front end with no SCSS changes. WordPress saves the choice in the site's global styles.

WordPress lists the `theme.json` palette first as **Default**, followed by the alternate palettes by title, so the panel reads Default, Alternate palette 1, Alternate palette 2.

| Palette             | File                                     | Base                                                                             | Brand                                                                                                                              | Accent                                                                                | Character                                                                                                                                            |
| ------------------- | ---------------------------------------- | -------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Default             | `theme.json`                             | Warm neutral (`base-100` #F4F4F2)                                                | Navy (`brand-700` #17466F, `brand-900` #0C2237)                                                                                    | Copper gold (`accent-500` #B56E22, `accent-600` #91561A)                              | The original palette.                                                                                                                                |
| Alternate palette 1 | `styles/colors/alternate-palette-1.json` | Light grey (`base-100` #ECECEC), steel secondary sections (`accent-100` #EEF1F4) | Black with crimson buttons (`brand-700` #A6192E, `brand-900` #0E0E0E) and a dark wine hover and inverse card (`brand-800` #3A0A12) | Signal red (`accent-500` #D22B20, `accent-600` #9A1010, inverse `accent-300` #EC4A3E) | Bold black and red. Highlight text is darker than `error` (#B33A3A), and the inverse highlight is brighter, so a highlight doesn't read as an error. |
| Alternate palette 2 | `styles/colors/alternate-palette-2.json` | Cool concrete (`base-100` #EFF3F5)                                               | Graphite (`brand-700` #3D434A, `brand-900` #1B1E23)                                                                                | Muted sage verdigris (`accent-500` #3B7266, `accent-600` #285B51)                     | Understated, for an architectural or high-end look. No gold or red, so it stays apart from the default and Alternate palette 1.                      |

Every palette keeps the same slugs, names and order as `theme.json`, including `white` and the unchanged `success`, `warning` and `error` colours, because a variation's palette replaces the whole theme palette. Apart from those four, no colour appears in more than one palette. The semantic and logo tokens map to the same steps in every palette, so each scale has to satisfy the same pairings:

- `accent-600` reaches 4.5:1 on `base-50`, `base-100` and `accent-100` (highlight text, icons and focus rings in the default and secondary sections).
- `accent-300` reaches 4.5:1 on `brand-800` and `brand-900` (inverse highlight, primary-button hover and logo accent).
- `accent-500` reaches 3:1 on `base-100` (the logo accent in the default section).
- `base-600` reaches 3:1 on `base-100` and `accent-100`, and `base-300` on `brand-900` (the logo wordmark).
- `brand-800` is the primary-button hover on light sections, so `base-50` and `base-100` labels need 4.5:1 on it, and it stays dark enough for the `success` border to reach 3:1 on inverse cards.

#### Adding a palette

1. Copy a file in `styles/colors/` and give it the next number in its `title` (`"Alternate palette 3"`) and a matching `slug` and file name (`alternate-palette-3`).
2. Change only the `base-*`, `brand-*` and `accent-*` colours, and don't reuse a colour from another palette. Keep every slug, name and the status colours. Use six-digit uppercase hex values.
3. Keep each scale running from light (`50`) to dark (`900`) at roughly the same lightness as the other palettes; the steps above carry contrast.
4. Run `npm run test:contrast` for the contrast table and `npm run test:unit -- scripts/palettes` for the checks. Both fail on any pairing below WCAG 2.2 AA in any section palette, including the logo colours. The tests also fail on changed slugs or status colours, on a colour shared with another palette, and when the highlight sits too close to the error colour.
5. Keep the file colour-only (`settings.color.palette`). If a scale step can't meet a pairing, adjust the step rather than adding colours to SCSS.
6. Preview it in the Site Editor across default, secondary and inverse sections, including a form with errors, then add it to the table above.

## Typography

- **Work Sans**: display and heading roles.
- **Source Sans 3**: body copy.
- **JetBrains Mono**: code or deliberately technical labels.

The `d*`, `h*`, and `p*` sizes control appearance only. Visual typography must not dictate semantic HTML: choose heading levels from document structure, then apply the appropriate visual role.

## Spacing and layout

Use the named fluid spacing scale from `3XS` through `3XL`. The configured content width is `768px`; the wide width is `1280px`. Prefer these shared constraints over one-off maximum widths.

Add a new spacing token only when an existing token cannot express a repeated design relationship. Avoid component-specific global tokens.

## Adding or changing a token

1. Identify the semantic job and all consumers.
2. Check whether an existing token already fulfils it.
3. Add the primitive to `theme.json` only if WordPress/editor access is needed.
4. Add or update the semantic SCSS mapping.
5. Test every palette and interactive state in editor and frontend.
6. Measure contrast where colour is involved.
7. Document the role here and include migration notes if existing content changes.

There is currently no automated token generator; changes shared between `theme.json` and SCSS must be kept in sync during review.

## Component states

Interactive components must define default, hover, active, focus-visible, disabled, and error states where applicable. Focus must remain visible across palettes and must not rely on colour alone.

## Guardrails

- No unexplained raw hex values in component styles.
- No one-off spacing values without a documented reason.
- No semantic HTML decisions based solely on visual size.
- No palette option without verified accessible text and control states.
- No divergence between editor and frontend token mappings.
