# Pattern taxonomy and registration conventions

- Status: Accepted
- Decision date: 2026-08-11
- Applies to: Theme-supplied block patterns and their editor registration
- Related issue: [#29](https://github.com/John-McPherson/jm_wp_theme/issues/29)

## Context

The theme needs a pattern library that is predictable for developers and easy for editors to browse. Without shared conventions, pattern names, categories, placeholders, locking, and registration can drift between implementations. That makes patterns harder to discover, creates one-off styling, and can leave copied starter content coupled to the theme.

Patterns in this theme are composition aids. They are not a second content store and are not a substitute for template parts or dynamic content retrieval.

## Decision

Theme patterns use the `jmc` namespace, kebab-case slugs, purpose-based categories, and WordPress pattern-file headers. They are unsynced starting points by default: after insertion, the blocks belong to the page and editors may customise their content.

Shared global structures belong in template parts. Collections whose content has an authoritative source, such as the services grid, must query that source rather than duplicate records inside a pattern.

## Naming

### Pattern identity

Every pattern has a stable slug in this form:

```text
jmc/<kebab-case-name>
```

Examples:

- `jmc/primary-navigation`
- `jmc/services-cta`
- `jmc/service-overview`
- `jmc/contact-details`

Rules:

- Use the `jmc` namespace for every theme-owned pattern.
- Use lowercase kebab-case after the namespace.
- Name the pattern for its content purpose, not its position, colour, or temporary campaign.
- Keep a published slug stable. Renaming a title does not require changing its slug.
- Do not encode versions in slugs. Replace or deprecate patterns deliberately.
- Titles and descriptions use clear editor-facing language rather than internal implementation terms.

## Purpose-based categories

Categories group patterns by the task they help an editor complete. Use the smallest useful set and add a category only when multiple patterns need it.

The initial category taxonomy is:

| Category slug | Editor label | Purpose |
| --- | --- | --- |
| `jmc-navigation` | Navigation | Navigation and wayfinding compositions |
| `jmc-cta` | Calls to action | Conversion-focused prompts and contact actions |
| `jmc-hero` | Heroes | Page introductions and primary propositions |
| `jmc-services` | Services | Service summaries, listings, and service-page sections |
| `jmc-content` | Content | General editorial sections and content layouts |
| `jmc-trust` | Trust | Credentials, testimonials, guarantees, and proof |
| `jmc-contact` | Contact | Contact details, enquiry prompts, and availability |
| `jmc-footer` | Footer | Footer-oriented compositions where a pattern is appropriate |

A pattern may belong to more than one category when each category materially improves discovery. For example, `jmc/services-cta` may use both `jmc-cta` and `jmc-services`. Do not create categories for purely visual variants.

Category slugs are namespaced with `jmc-` to avoid collisions. Categories must be registered before their patterns are presented in the editor. Registration belongs in a focused theme setup module rather than `functions.php`.

## Pattern files and registration

Store one pattern per PHP file under `patterns/`. The file name matches the slug portion after `jmc/`:

```text
patterns/services-cta.php
```

Prefer WordPress pattern-file headers and automatic theme pattern registration. Do not call `register_block_pattern()` for static theme patterns.

A representative header is:

```php
<?php
/**
 * Title: Services call to action
 * Slug: jmc/services-cta
 * Categories: jmc-cta, jmc-services
 * Description: A call-to-action section directing visitors to electrical services.
 */
?>
```

Requirements:

- `Title`, `Slug`, `Categories`, and `Description` are required.
- Add `Keywords` only when they provide useful alternative search terms.
- Add `Block Types`, `Post Types`, `Template Types`, or `Inserter` only to express a deliberate editor contract.
- Keep executable PHP out of static pattern markup except for narrowly scoped translation, escaping, or asset URL needs supported by WordPress theme patterns.
- Dynamic behaviour belongs in a block render callback or another documented runtime component, not in a static pattern file.
- Pattern markup must remain valid block markup and must not depend on editor-only state.

Programmatic registration is reserved for a documented requirement that file headers cannot satisfy, such as a genuinely runtime-generated pattern. The reason must be recorded alongside the implementation.

## Starting-point policy

Patterns are unsynced starting points unless an approved exception is documented.

After insertion:

- the editor owns the inserted block content;
- edits affect only that page or post;
- removing or changing the source pattern does not migrate existing content;
- pattern markup must not imply that copied content will remain centrally updated.

Use a template part for globally shared structures such as the site header or footer. Use a dynamic block or constrained Query Loop for data-backed collections. Do not use a pattern to maintain a second copy of service names, excerpts, images, ordering, or links.

## Locking

Lock only the minimum structure needed to keep a composition usable.

Appropriate locking may:

- prevent removal of a structural wrapper required for layout;
- prevent moving blocks when order carries semantic or functional meaning;
- protect a dynamic block whose children are generated from source data.

Ordinary copy, headings, links, buttons, and images remain editable. Do not lock content merely to preserve a preferred visual arrangement. Prefer `templateLock: "insert"` or targeted block-level move/remove locks over `templateLock: "all"` when they meet the requirement.

Every lock must have a specific structural, accessibility, or functional reason that can be explained during review.

## Placeholder content

Placeholder content must be safe, realistic, and obviously replaceable.

- Use concise neutral copy representative of an electrical-services website.
- Do not invent awards, accreditations, customer claims, prices, response times, guarantees, addresses, phone numbers, or legal statements.
- Do not use production customer data or personal information.
- Use descriptive labels instead of lorem ipsum where the content's purpose matters.
- Keep heading levels compatible with insertion beneath the page title; patterns must not assume they own the page's only `h1`.
- Use replaceable media that the project has the right to distribute.
- Provide meaningful alternative text for informative placeholder images; use an empty alternative attribute for decorative images.
- Avoid empty blocks whose only purpose is spacing.

### Links

- Internal placeholders use valid site-relative paths, such as `/services/` or `/contact/`, only where that destination is part of the agreed information architecture.
- Use `#` only when an intentionally unresolved destination is obvious to the editor and cannot accidentally ship as a meaningful action.
- Link text describes the destination or action; avoid “click here”.
- Do not invent external URLs, telephone numbers, or email addresses.
- Buttons are reserved for meaningful actions, not generic navigation styling.

Editors must verify all placeholder copy, media, and links before publication.

## Styling and design tokens

Patterns compose blocks; they do not introduce a parallel design system.

- Use presets registered in `theme.json` for colours, typography, spacing, layout, and other supported values.
- Use registered block styles and existing semantic `jmc-` classes where a preset cannot express the contract.
- Do not add arbitrary hex colours, pixel spacing, font sizes, inline CSS, or unregistered utility classes to pattern markup.
- Add a reusable token or component style to the design system when a justified value is missing.
- Verify that the editor and frontend render the same intended hierarchy and spacing.
- Pattern content must remain usable when editors change supported text lengths or images.

## Accessibility requirements

Every pattern must:

- preserve a logical heading hierarchy at likely insertion points;
- use descriptive link and button labels;
- avoid relying on colour alone to convey meaning;
- retain visible keyboard focus through theme styles;
- use correct list, navigation, quotation, and landmark semantics;
- provide appropriate image alternative text behaviour;
- avoid unnecessary tab stops and empty interactive elements;
- remain understandable at narrow viewport widths and at increased text size.

A visually attractive preview does not override semantic correctness.

## Review and verification

Before a pattern is accepted:

1. Confirm its slug, file name, title, description, and categories follow this document.
2. Insert it through the editor's pattern browser and confirm it appears in every declared category.
3. Confirm it is inserted as ordinary unsynced blocks.
4. Edit all intended copy, links, and media.
5. Move or remove unlocked content and confirm every lock is necessary.
6. Save, reload, and edit the page again without block validation errors.
7. Compare editor and frontend rendering at representative viewport widths.
8. Inspect the saved markup for arbitrary styles, unsafe placeholders, invalid links, duplicated source data, and unescaped output.
9. Check keyboard operation, focus visibility, headings, landmarks, link purpose, and image alternatives.
10. Run the repository's relevant lint, build, and validation commands.

The pull request must include the pattern's purpose, category decisions, locking rationale, placeholder review, and evidence of editor/frontend verification.

## Governance

Changes to this taxonomy are architectural changes:

- New categories require a demonstrated editor-discovery need across more than a single visual variant.
- New namespaces are not permitted for theme-owned patterns without replacing this decision.
- Exceptions to automatic file registration, unsynced behaviour, token usage, or locking rules require a documented reason.
- Removing a pattern affects future insertion only; existing inserted content must be assessed separately.
- Deprecated patterns should be hidden from the inserter before removal when editors need a transition period.

## Consequences

### Benefits

- Editors can find patterns by purpose rather than implementation detail.
- Stable names and registration rules reduce development drift.
- Unsynced insertion makes ownership and update behaviour explicit.
- Restrained locks preserve editor control.
- Token-only styling keeps patterns aligned with the shared design system.
- Dynamic or global content remains connected to its proper source of truth.

### Trade-offs

- Existing inserted patterns do not receive automatic updates.
- Purpose categories require curation as the library grows.
- Editor flexibility means content governance and verification remain necessary.
- Some compositions may need a dynamic block or template part instead of a pattern.
