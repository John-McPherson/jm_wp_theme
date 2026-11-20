# Accessibility standard

## Target

The project targets WCAG 2.2 Level AA. This is an engineering requirement, not a claim that the unfinished theme has already been audited or certified.

## Keyboard and focus

- Every interactive control must be reachable and operable with a keyboard.
- Focus order must follow the visual and reading order.
- Use a deliberate, high-contrast `:focus-visible` treatment across every palette.
- Never remove an outline without providing an equal or better replacement.
- The shared ring is `:focus-visible` in `src/scss/base/_focus.scss`: a `--jmc-focus-width` solid outline in the section's `--jmc-text-highlight`, `--jmc-focus-offset` from the element. Components keep it unless they draw it somewhere better (a card around its stretched link), and then remove it from the link in their own block styles.
- The sticky header reserves its height with `scroll-padding-block-start`, so focused content scrolled into view is not hidden beneath it.
- Composite controls such as menus must follow the appropriate keyboard pattern.

## Colour and contrast

- Normal text: minimum 4.5:1 contrast.
- Large text: minimum 3:1 contrast.
- Focus indicators, control boundaries, and meaningful graphics: minimum 3:1 against adjacent colours.
- Information and state must not be communicated by colour alone.
- Measure actual semantic foreground/background/state pairs, not isolated primitive colours.
- `npm run test:contrast` records these pairs, including the site logo's colours, for every colour palette (`theme.json` and `styles/colors/`) in the default, secondary and inverse section palettes; `scripts/palettes/contrast.test.js` fails on any pair below these minimums.

## Content semantics

- Use one logical page-level heading and do not skip levels for visual effect.
- Typography classes control appearance; heading elements control document structure.
- Preserve visible bullets and numbering for content lists. The reset only removes list margins; navigation and component lists remove their markers and padding in their own block styles, scoped to their class.
- Links within body copy must have a non-colour affordance, normally an underline. Every link is underlined by default (`src/scss/base/_links.scss`), and hover thickens it; navigation, buttons and other components set their own decoration in their block styles.
- Use one `<main>` landmark and meaningful `<header>`, `<nav>`, and `<footer>` landmarks.
- Provide a visible-on-focus skip link to the main content. WordPress adds **Skip to content** to every block template with a `<main>`, so every template wraps its content in one `<main>` Group block. `inc/templates.php` gives that group `tabindex="-1"`, so following the link moves focus into it.

## Images

- Hero background images are decorative and cannot provide alternative text.
- Do not place essential information exclusively in a background image.
- Meaningful images must use an image element/block and suitable alt text.
- Decorative `<img>` elements use an empty alt attribute; do not describe visual decoration.

## Motion

Respect `prefers-reduced-motion: reduce`. Global smooth scrolling and non-essential transitions/animations must be disabled or reduced for that preference.

Smooth scrolling is only turned on under `prefers-reduced-motion: no-preference`. For `reduce`, a rule in the reset layer (`src/scss/base/_reset.scss`) sets every transition and animation to a near-zero duration with no delay, and scrolling to `auto`. It uses `!important` in the first layer, which outranks every later layer, so new block styles are covered without their own media query.

## Authoring controls

Block defaults should guide editors toward valid heading structures, accessible palettes, and correct image use. Do not rely on documentation alone when the block API can prevent an invalid state.

## Required checks

The manual keyboard, visual and screen-reader checks, the browser and screen-reader combinations they run on, and how results are recorded and failures reported are in the [manual accessibility QA checklist](accessibility-qa.md). Run the checks for the affected journey on every pull request that changes a user-facing feature, and the whole checklist at every release candidate.

In addition, for each new or changed user-facing feature:

- Make sure the axe scans in `tests/e2e/axe.spec.ts` cover it: add a scan for a new template or interactive state (see [Testing](testing.md)).
- Measure new colour combinations (`npm run test:contrast`).

## Automated scans

Every pull request runs axe over the primary templates, the navigation states, every custom block and the enquiry form, in Chromium, Firefox and WebKit (`tests/e2e/axe.spec.ts`; what each scan covers is in [Testing](testing.md)). A serious or critical violation fails the build. Minor and moderate ones are reported in the Playwright report without failing; fix them when you touch the code. A passing scan does not mean a page is accessible: axe cannot judge reading order, usable focus, meaningful alternative text or what a screen reader announces, which is what the [manual checks](accessibility-qa.md) are for.

### Accessibility exceptions

Fix a violation rather than accepting it. When that is not yet possible, record an exception in `tests/e2e/accessibility-exceptions.ts`, in the same pull request, so it is code-reviewed. Each entry names:

- the axe rule and the exact element selectors it covers, as the failure prints them (and, optionally, the scans), so the same rule still fails anywhere else;
- the **rationale**: why it is accepted for now and what fixing it needs;
- the **owner**: the GitHub user responsible for resolving it;
- the **review date** (`YYYY-MM-DD`): the spec fails once it has passed, so the exception is fixed or deliberately renewed;
- the **issue** tracking the fix.

Remove the entry in the pull request that fixes the violation. There are currently no exceptions.

## Known gaps

- The link underline, content list markers, shared focus ring, skip link and reduced-motion rules (#59) are covered by automated tests (see [Testing](testing.md)) but have not yet been through the [manual checks](accessibility-qa.md) (F1.1, F2.5, V1, V4–V6) in each supported browser and screen reader.
- Landmark, navigation and template behaviour remain unverified with a screen reader. Every template has one `<main>` landmark, checked by `SkipLinkTargetTest`; the contact and 404 templates also have one H1 with no skipped heading levels, checked from the template markup by `ContactNotFoundTemplatesTest`. Site search is turned off, so no template has a search form.
- The axe scans cover the frontend in the default colour palette only, with representative pattern content; editor states and the other palettes are not scanned (`npm run test:contrast` measures every palette's colour pairs).

These gaps must be resolved and manually verified before claiming WCAG 2.2 AA conformance.
