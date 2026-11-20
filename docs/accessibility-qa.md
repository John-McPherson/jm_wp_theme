# Manual accessibility QA checklist

This is the single list of manual keyboard, visual and screen-reader checks for the theme. Automated scans cannot judge reading order, usable focus, meaningful alternative text or what a screen reader actually announces, so these checks are run by a person and the results are recorded.

The standards the checks verify are in [Accessibility](accessibility.md); the supported platforms come from [Platform support](support.md).

## When to run it

| When | What to run | Record |
| --- | --- | --- |
| Every release candidate, on staging | The whole checklist, on every combination in [Test setups](#test-setups) | Yes: a results file for the release (see [Recording results](#recording-results)) |
| A pull request that changes a user-facing block, template, pattern or editor control | The checks for the affected journey or block, on at least **D1** and one screen-reader setup | In the pull request description |

A release candidate is not ready for `main` until its results file exists and every failure has an issue (see [Reporting failures](#reporting-failures)).

## Test setups

These combinations come from the [support matrix](support.md#support-matrix). Use the current release of each browser and screen reader, and write the exact versions in the results file; the matrix, not this list, decides which versions are supported.

| ID | Device | Browser | Assistive technology | Used for |
| --- | --- | --- | --- | --- |
| **D1** | macOS desktop | Chrome | None (keyboard only) | Keyboard and visual checks, editor checks |
| **D2** | Windows desktop | Firefox | None (keyboard only) | Keyboard checks in a second engine |
| **D3** | macOS desktop | Safari | None (keyboard only, with **Press Tab to highlight each item** turned on) | Keyboard checks in WebKit |
| **SR1** | macOS desktop | Safari | VoiceOver | Frontend and editor screen-reader checks |
| **SR2** | iPhone (iOS) | Safari | VoiceOver | Frontend screen-reader and touch checks |
| **SR3** | Windows desktop | Chrome or Firefox | NVDA | Frontend and editor screen-reader checks |

Before starting:

- Test against the staging site (or, for a pull request, a site running the pull request's build) with representative content: a home page, the Services page with at least two service pages, one of which has a submenu entry in the primary menu, the Contact page and a missing URL for the 404 page.
- Fill in every Settings → Business field so the header, footer and contact details are complete.
- Use the default colour palette, and repeat the visual checks (V1–V3) with each palette under **Appearance → Editor → Styles**.
- Clear the browser cache after a deploy so the tested CSS and scripts are the released ones.

## Automated coverage

Some checks are already guarded by the automated suites in [Testing](testing.md), which run on every pull request. The checks below say so:

- **Automated** — the whole check is covered by Playwright in Chromium, Firefox and WebKit. Skip it on the keyboard-only setups (**D1–D3**) if CI passed for the build under test; still run it with each screen reader, because no test checks what is announced.
- **Partly automated** — the named test covers only part of the check, or covers it in rendered markup (PHPUnit) or a simulated DOM (Vitest) rather than a real browser. Run the whole check on every setup.

Separately, `tests/e2e/axe.spec.ts` scans the primary templates, the open mobile menu, an expanded submenu and the enquiry form's error and result states with axe, and fails on serious or critical violations such as missing names, labels or alternative text and insufficient contrast in the default palette. It marks no check here as automated: axe cannot tell whether a name, label or reading order makes sense, so run every check as listed.

If a manual check fails where a test claims coverage, report the failure and note in the issue that the test needs extending.

## Frontend checks

Run each journey with keyboard only (**D1–D3**), then with each screen reader (**SR1–SR3**). With a screen reader, move through the page with both the reading cursor (arrow keys, or swipes on iOS) and the landmark, heading and form-control lists (VoiceOver rotor, NVDA elements list).

### F1 — Header, skip link and primary navigation

Pages: any page, at desktop width and at 320 CSS px.

- [ ] **F1.1** The first Tab press shows a visible **Skip to content** link; activating it moves focus to the start of `<main>`.
  _Partly automated: `tests/e2e/accessibility.spec.ts` checks the link is the first Tab stop, has a visible size on focus and moves focus to `<main>`; it does not check what is announced._
- [ ] **F1.2** Focus then moves through the logo link, the primary navigation and the header call to action in visual order, with a visible focus indicator on each that is never hidden behind the sticky header.
- [ ] **F1.3** The logo link is announced as a link named after the site (or "Home"); the logo artwork itself is not announced.
  _Partly automated: `tests/e2e/header.spec.ts` checks the link's name and that the SVG is hidden from assistive technology._
- [ ] **F1.4** The primary navigation is announced as a navigation landmark with a meaningful name, and the current page's link is announced as current.
  _Partly automated: `tests/e2e/header.spec.ts` checks `aria-current` on the current page link only._
- [ ] **F1.5** On desktop, a submenu toggle is a separate button from its parent link, announces its name and its collapsed or expanded state, and opens with Enter or Space. Escape closes it and returns focus to the toggle; tabbing out of the submenu closes it.
  _Partly automated: `NavigationRenderTest` checks the toggle's markup and `src/blocks/site/navigation/submenus.test.ts` its Escape, focus and toggle behaviour, in a simulated DOM._
- [ ] **F1.6** At 320 CSS px, the menu button is announced with its expanded state, opens the menu with Enter or Space, and Escape closes it and returns focus to the button. Submenu links are reachable in the mobile menu.
  _Partly automated: `tests/e2e/header.spec.ts` opens and closes the menu by keyboard and checks focus returns to the button; submenus in the mobile menu are checked only in a simulated DOM._
- [ ] **F1.7** The header call to action is announced as a link whose name says what it does; a WhatsApp button's icon is not announced in place of its name.
- [ ] **F1.8** With JavaScript turned off, every navigation link is still reachable by keyboard.
  _Automated: `tests/e2e/header.spec.ts` ("keeps primary links available without JavaScript")._

### F2 — Home page and content sections

Page: the static front page.

- [ ] **F2.1** There is one H1 (the hero heading), and the heading list shows no skipped levels.
  _Partly automated: `tests/e2e/front-page.spec.ts` checks the hero heading is the only H1, not the other heading levels._
- [ ] **F2.2** The page has exactly one `main`, one `banner` (header) and one `contentinfo` (footer) landmark.
  _Partly automated: `tests/e2e/front-page.spec.ts` checks one `main` and that the header and footer render; `PageTemplatesTest` checks the template markup._
- [ ] **F2.3** Every link and button in the hero, service grid, text-with-image, trust and credentials and call-to-action sections is reachable in reading order and has a name that makes sense out of context. "Learn more" cues in the service grid are announced together with the service name.
- [ ] **F2.4** Meaningful images (text-with-image, credential logos) are announced with useful alt text; decorative images, icons and the hero background are not announced.
- [ ] **F2.5** Icon lists, bulleted lists and numbered lists are announced as lists with the right item count.
  _Partly automated: `tests/e2e/accessibility.spec.ts` checks core bulleted and numbered lists keep their markers, not what is announced._
- [ ] **F2.6** Credential logos that link elsewhere and open in a new tab announce that they open in a new tab.

### F3 — Services landing and service pages

Pages: the Services page and one service page.

- [ ] **F3.1** The hero breadcrumbs on the service page are announced as a breadcrumb navigation, start with "Home" and mark the current page.
- [ ] **F3.2** Each service card in the service list is announced as one link with the service name, and its icon is not announced.
- [ ] **F3.3** Heading levels in the service list follow on from the section heading without skipping.

### F4 — Contact page and enquiry form

Page: the Contact page.

- [ ] **F4.1** The contact details announce each line (phone, email, WhatsApp, address, opening hours, service area) with its label; phone and email links say what they do. Opening hours are read in day order, and closed days say "Closed".
- [ ] **F4.2** Every form field has a visible label that is also its accessible name, required fields are announced as required, and hints are announced with their field.
  _Partly automated: `EnquiryFormRenderTest` checks labels, `required` and `aria-describedby` in the markup._
- [ ] **F4.3** The hidden spam-trap field is never reachable by Tab or by the screen reader's reading cursor.
  _Partly automated: `EnquiryFormRenderTest` checks the spam-trap field's markup._
- [ ] **F4.4** Submitting the empty form moves focus to an error summary that is announced; each summary link moves focus to its field, and each field announces its own error.
  _Partly automated: `src/blocks/layouts/enquiry-form/view.test.ts` checks the focused summary and its links, in a simulated DOM._
- [ ] **F4.5** Correcting a field clears its error. A successful submission moves focus to the confirmation message, which is announced, without a page reload.
  _Partly automated: `src/blocks/layouts/enquiry-form/view.test.ts` checks clearing errors and a successful submission, in a simulated DOM._
- [ ] **F4.6** With JavaScript turned off, a submission returns a page whose result notice is announced and reachable.
  _Partly automated: `EnquiryFormRenderTest` checks each no-JavaScript result notice is rendered._
- [ ] **F4.7** On iOS (**SR2**), the email and phone fields open the email and telephone keyboards, the postcode field capitalises letters, and autofill suggestions work.

### F5 — Footer

Page: any page.

- [ ] **F5.1** The footer is announced as one `contentinfo` landmark; the footer navigation and legal links are named navigation landmarks.
- [ ] **F5.2** Footer menu groups announce their heading before their links.
- [ ] **F5.3** Business details announce each line with its label; icons are not announced, and the WhatsApp link has a name.
- [ ] **F5.4** The copyright, credit and legal links are read in visual order.

### F6 — 404 page

Page: any URL that does not exist.

- [ ] **F6.1** The page has one H1 that says the page was not found, and the page title says the same.
  _Partly automated: `ContactNotFoundTemplatesTest` checks the template has one H1 with no skipped levels, in the template markup._
- [ ] **F6.2** The homepage button and call to action are reachable and named.

### V — Visual checks (every frontend page)

Run on **D1** at desktop width unless the check names another setup.

- [ ] **V1** Focus is visible on every interactive element, in every palette and section palette (default, secondary, inverse), and is never hidden behind the sticky header or another element.
  _Partly automated: `tests/e2e/accessibility.spec.ts` checks the focus ring on a body link and a navigation link, and that a focused link is not under the sticky header, in the default palette only; `npm run test:contrast` measures the ring colour in every palette._
- [ ] **V2** At 200% browser zoom, and at 320 CSS px wide, all content and controls are available without horizontal scrolling (except content that needs two dimensions) and nothing overlaps or is cut off.
  _Partly automated: `tests/e2e/header.spec.ts` and `tests/e2e/front-page.spec.ts` check for horizontal overflow at 320 CSS px on the front page only._
- [ ] **V3** With the [text spacing bookmarklet](https://www.html5accessibility.com/tests/tsbookmarklet.html) applied (line height 1.5, paragraph spacing 2×, letter spacing 0.12em, word spacing 0.16em), no text is cut off or overlaps.
- [ ] **V4** With **Reduce motion** turned on in the operating system, page scrolling is not smooth-animated and no non-essential animation or transition plays (menus, submenus, form messages).
  _Partly automated: `tests/e2e/accessibility.spec.ts` emulates the preference and checks scrolling is not smooth and no element has a transition; it does not cover the editor or a real operating-system setting._
- [ ] **V5** Body-copy links can be told apart from surrounding text without relying on colour.
  _Partly automated: `tests/e2e/accessibility.spec.ts` checks a body link is underlined at rest, on hover and on focus._
- [ ] **V6** Bulleted and numbered lists show their bullets and numbers.
  _Partly automated: `tests/e2e/accessibility.spec.ts` checks core lists; `src/scss/foundations.test.ts` checks the List block's bullet and number styles._
- [ ] **V7** On iOS (**SR2**) in portrait and landscape, every tap target in the header, navigation, call-to-action buttons and form can be activated, and the page does not need horizontal scrolling.

## Editor checks

Run on **D1** with keyboard only, then with **SR1** or **SR3**. Use an Editor-role account, not an Administrator, for E1–E3. Use the WordPress keyboard shortcuts (**Alt+F10** for the block toolbar, **Ctrl+\`** / **Ctrl+Shift+\`** to move between editor regions; **Cmd** for **Ctrl** on macOS) rather than the mouse.

### E1 — Insert and configure layout blocks

In a new page:

- [ ] **E1.1** Open the block inserter with the keyboard, search for and insert each layout block: **Hero**, **Text with Image**, **Service Grid**, **CTA Banner** and **Enquiry Form**. Each one is announced by name in the inserter and inserted at the cursor, with focus moving into it.
- [ ] **E1.2** For each one, reach every **Settings** sidebar control (palette, order, layout, breadcrumbs, page source) by keyboard. Each control announces its label and current value, and changing it updates the block.
- [ ] **E1.3** Text with Image: choose, replace and remove an image from the Media Library by keyboard; the media buttons announce what they do.
- [ ] **E1.4** Service Grid: choose a parent page and reorder chosen pages with the keyboard; the move buttons announce the page and direction ("Move … up") and are announced as unavailable at the ends of the list.
- [ ] **E1.5** Patterns from the theme's pattern categories can be found and inserted by keyboard.

### E2 — Edit content blocks inside a section

- [ ] **E2.1** Move between the heading, paragraph, list and button blocks inside a section with the keyboard; each one is announced with its block name.
- [ ] **E2.2** Heading: change the level from the block toolbar where it is offered; locked headings do not offer the level control.
- [ ] **E2.3** Button: add, edit and remove a link with the link control by keyboard; choose each button type (link, Phone, Email, WhatsApp) and the new-tab setting, and each option announces its state.
- [ ] **E2.4** List: change the list style and icon, add and remove items with Enter and Backspace.
- [ ] **E2.5** Buttons: add a button from the group's add menu by keyboard.
- [ ] **E2.6** Locked blocks cannot be moved or removed, and the editor announces that they are locked rather than failing silently.

### E3 — Site Editor header and footer

In **Appearance → Editor → Patterns → Template parts**:

- [ ] **E3.1** Open the Header part and select the navigation by keyboard; its menu can be chosen in **Settings**, and the header call to action can be edited as in E2.3.
- [ ] **E3.2** Open the Footer part; the footer navigation's menu can be chosen and the footer legal text edited by keyboard; locked footer blocks behave as in E2.6.
- [ ] **E3.3** Save the part with the keyboard; the save confirmation is announced, and the frontend reflects the change.

### E4 — Business settings

- [ ] **E4.1** In **Settings → Business**, every field has a label announced as its name, the opening hours group is announced with its legend, and saving announces the result.

## Recording results

Each release candidate gets one results file:

1. Copy [`accessibility-qa/template.md`](accessibility-qa/template.md) to `docs/accessibility-qa/v<version>.md` (for example `v0.3.0.md`) on a branch from `dev`.
2. Fill in the build tested (commit and environment), the tester, the date and the exact versions of each setup.
3. Mark every check **Pass**, **Fail** (with the issue link), **N/A** (with a reason), **Not run** (with a reason) or **Auto** (an *Automated* check skipped on **D1–D3** because CI passed) for each setup it applies to.
4. Fill in the summary: the overall result, the failures and any checks not run.
5. Merge it into `dev` and on to `staging` with the release (see the [release checklist](releasing.md#release-checklist)), and link it from the release's `CHANGELOG.md` entry and the `staging` → `main` release pull request.

Keep previous results files: they are the history of what was tested for each release.

## Reporting failures

Open one GitHub issue per distinct failure, so it can be fixed and retested on its own:

- **Title:** what fails, where (for example "Mobile menu does not return focus to the menu button on Escape").
- **Labels:** `type: bug`, `area: accessibility`, a `scope:` label (`frontend`, `editor` or `both`), and a priority:
  - `priority: critical` — a visitor or editor cannot complete a journey (call, enquiry, navigation, publishing) with a supported setup.
  - `priority: high` — a WCAG 2.2 AA failure that makes a journey much harder but has a workaround.
  - `priority: medium` or `priority: low` — other failures and best-practice problems.
- **Body:** the check ID (for example **F1.6**), the setup and exact versions, the page and palette, steps to reproduce, what was announced or happened, what was expected, and the WCAG success criterion if known.
- **Milestone:** critical and high failures go in the current release milestone and block the release until fixed and retested; others go in the milestone they are planned for.

Record the issue link against the check in the results file. When a fix lands, rerun the check on the setup that failed and update the results file.
