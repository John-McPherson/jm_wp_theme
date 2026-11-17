# Accessibility standard

## Target

The project targets WCAG 2.2 Level AA. This is an engineering requirement, not a claim that the unfinished theme has already been audited or certified.

## Keyboard and focus

- Every interactive control must be reachable and operable with a keyboard.
- Focus order must follow the visual and reading order.
- Use a deliberate, high-contrast `:focus-visible` treatment across every palette.
- Never remove an outline without providing an equal or better replacement.
- Composite controls such as menus must follow the appropriate keyboard pattern.

## Colour and contrast

- Normal text: minimum 4.5:1 contrast.
- Large text: minimum 3:1 contrast.
- Focus indicators, control boundaries, and meaningful graphics: minimum 3:1 against adjacent colours.
- Information and state must not be communicated by colour alone.
- Measure actual semantic foreground/background/state pairs, not isolated primitive colours.

## Content semantics

- Use one logical page-level heading and do not skip levels for visual effect.
- Typography classes control appearance; heading elements control document structure.
- Preserve visible bullets and numbering for content lists.
- Links within body copy must have a non-colour affordance, normally an underline.
- Use one `<main>` landmark and meaningful `<header>`, `<nav>`, and `<footer>` landmarks.
- Provide a visible-on-focus skip link to the main content.

## Images

- Hero background images are decorative and cannot provide alternative text.
- Do not place essential information exclusively in a background image.
- Meaningful images must use an image element/block and suitable alt text.
- Decorative `<img>` elements use an empty alt attribute; do not describe visual decoration.

## Motion

Respect `prefers-reduced-motion: reduce`. Global smooth scrolling and non-essential transitions/animations must be disabled or reduced for that preference.

## Authoring controls

Block defaults should guide editors toward valid heading structures, accessible palettes, and correct image use. Do not rely on documentation alone when the block API can prevent an invalid state.

## Required checks

For each new or changed user-facing feature:

- Navigate the complete flow with keyboard only.
- Confirm focus is always visible and not obscured.
- Zoom to 200% and check reflow at a 320 CSS-pixel width.
- Check text spacing and content resizing without loss.
- Run an automated axe or equivalent scan.
- Measure new colour combinations.
- Test meaningful images, headings, labels, names, roles, and values.
- Test reduced-motion behaviour.

## Known gaps

- Global link styles currently inherit text decoration and may make body links indistinguishable.
- Global list resets currently remove visible bullets and numbering.
- No deliberate shared `:focus-visible` system has been demonstrated.
- Global smooth scrolling does not yet include a reduced-motion override.
- Palette combinations do not yet have recorded contrast evidence.
- Skip-link, landmark, navigation, and template behaviour remain unverified.
- No automated accessibility check currently runs in CI.

These gaps must be resolved and manually verified before claiming WCAG 2.2 AA conformance.
