# Design system

## Principles

The theme uses two token layers:

1. **Primitives** define raw choices such as colour ramps, font families, fluid sizes, spacing, and radii.
2. **Semantic tokens and palettes** define roles such as surface, text, border, action, and inverse treatment.

Components should consume semantic roles. A raw value such as `brand-700` describes an ingredient, not why a component needs it.

## Sources of truth

| Concern | Source |
|---|---|
| WordPress colour palette | `theme.json` |
| Font families and local font files | `theme.json` and `build/assets/fonts` |
| Fluid type scale | `theme.json` |
| Fluid spacing scale | `theme.json` |
| Border radii | `theme.json` |
| Content and wide widths | `theme.json` |
| Semantic palette mappings | `src/scss/palettes/` |
| Shared SCSS tokens | `src/scss/tokens/` |
| Token generation | `scripts/generate-tokens.mjs` |
| Layout rules | `src/scss/layout/` |

## Colour

The primitive palette contains neutral `base`, blue `brand`, warm `accent`, and status colours. Do not select a primitive solely because it looks right in one component. Add or reuse a semantic role, then validate the foreground/background/state combinations in every supported palette.

Current section palettes are `default`, `secondary`, and `inverse`. Store the unprefixed value in block attributes; rendering code maps it to the relevant `jm-palette--*` class.

Required contrast targets are documented in [Accessibility](accessibility.md).

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

## Component states

Interactive components must define default, hover, active, focus-visible, disabled, and error states where applicable. Focus must remain visible across palettes and must not rely on colour alone.

## Guardrails

- No unexplained raw hex values in component styles.
- No one-off spacing values without a documented reason.
- No semantic HTML decisions based solely on visual size.
- No palette option without verified accessible text and control states.
- No divergence between editor and frontend token mappings.
