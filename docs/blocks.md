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
| `jmc/button`          | Linked call-to-action                                                              | Dynamic PHP               | Primary or secondary; Button, Phone Button, Email Button and WhatsApp Button variations (`linkType`); optional pre-filled email or WhatsApp message; insertable only inside `jmc/text-with-image`, `jmc/hero` and `jmc/buttons` (`ancestor`); see [Buttons](#buttons) |
| `jmc/buttons`         | Group of one or more `jmc/button` blocks                                           | Dynamic PHP               | Allows only `jmc/button`; unlocked inside locked sections; adds buttons from its own **+** menu; renders nothing when every button is omitted; insertable only inside `jmc/cta-banner` (`ancestor`) |
| `jmc/column`          | Groups related inner content within a section                                      | Dynamic PHP               | Structural composition block; direct inserter disabled; inherits its section's lock, except inside `jmc/cta-banner`, where its content is unlocked and limited to the CTA's content blocks |
| `jmc/contact-details` | Business phone, WhatsApp, email, opening hours, and service area                   | Dynamic PHP               | Restricted to `jmc/column`; values come from the [business contact settings](#business-contact-settings), the block only chooses which to show; renders nothing when nothing is set; see [Contact details](#contact-details) |
| `jmc/credential`      | One accreditation, guarantee or review logo with an optional link                  | Dynamic PHP               | Restricted to `jmc/credential-list`; informative logo whose alt text names the credential; renders nothing without an image; see [Credentials](#credentials) |
| `jmc/credential-list` | Centred, wrapping list of `jmc/credential` logos                                   | Dynamic PHP               | Restricted to `jmc/trust-credentials`; direct inserter disabled; starts with one credential; credentials can be added, removed and reordered; renders nothing when empty |
| `jmc/footer`          | Site footer container with a selectable palette                                    | Dynamic PHP               | Site chrome; direct inserter disabled; single instance; allows only footer blocks and `jmc/trust-credentials`, each rendered as a column, with the credential logos spanning the top and the legal row the bottom; inverse and locked in the footer part |
| `jmc/footer-business` | Business name and address, with the shared phone, WhatsApp, email, and service area | Dynamic PHP               | Restricted to `jmc/footer`; direct inserter disabled; single instance; locked against removal in the footer part      |
| `jmc/footer-navigation` | A column of links from a selected `wp_navigation` menu                          | Dynamic PHP               | Restricted to `jmc/footer`; insertable there, multiple allowed (one column each); no toggle or view script            |
| `jmc/footer-legal`    | Site credit, copyright notice with the current year, and links from the `legal` menu | Dynamic PHP              | Restricted to `jmc/footer`; direct inserter disabled; single instance; locked against removal in the footer part      |
| `jmc/header-cta`      | Optional header call-to-action linking to a page, URL, or telephone number         | Dynamic PHP               | Restricted to `jmc/navigation`; direct inserter disabled; single instance; locked against removal in the header part   |
| `jmc/navigation`      | Primary navigation rendered from a selected `wp_navigation` menu                    | Dynamic PHP + view script | Site chrome; direct inserter disabled; allows only `jmc/header-cta`, rendered as the last menu item                     |
| `jmc/heading`         | Semantic heading with configurable level                                           | Dynamic PHP               | Uses `jmc/variant` context; level defaults to `2`; insertable only inside `jmc/text-with-image`, `jmc/hero` and `jmc/cta-banner` (`ancestor`) |
| `jmc/list`            | Styled list of short points with icon, bullet or number markers                    | Dynamic PHP               | `listStyle` `icon` (default), `bullet` or `number`; `icon` from a fixed Lucide set; provides `jmc/listStyle` and `jmc/listIcon`; allows only `jmc/list-item`; insertable only inside `jmc/text-with-image` and `jmc/cta-banner` (`ancestor`); renders nothing when empty; see [Lists](#lists) |
| `jmc/list-item`       | One point in a `jmc/list`                                                          | Dynamic PHP               | Restricted to `jmc/list`; bold, italic and links only; Enter starts the next item; renders nothing without visible text |
| `jmc/paragraph`       | Paragraph or small label text                                                      | Dynamic PHP               | `default` and `label` variations; uses `jmc/variant`; insertable only inside `jmc/text-with-image`, `jmc/hero` and `jmc/cta-banner` (`ancestor`) |
| `jmc/service-list`    | Linked cards for the published child pages of the Services page                    | Dynamic PHP               | Restricted to `jmc/service-grid`; direct inserter disabled; card heading level `2`–`6`, default `3`; previewed in the editor with `ServerSideRender`; see [Services grid](#services-grid) |
| `jmc/cta-banner`      | Call-to-action section with a heading, supporting copy, and one or more buttons     | Dynamic PHP               | Columns, Stacked and Contact Details variations (`layout`); palettes; left/right order (not stacked); defaults to inverse/right/columns; its two columns are fixed but their content is editable; see [Call-to-action banner](#call-to-action-banner) |
| `jmc/hero`            | Page introduction with composable content and optional decorative background image | Dynamic PHP               | Single-instance support; provides `jmc/variant`; supports default, secondary, and inverse palettes; invalid values fall back to default |
| `jmc/service-grid`    | Introductory content and the published services in one responsive grid              | Dynamic PHP               | Two locked Column children: the intro and `jmc/service-list`; palettes; defaults to default; the `order` attribute is currently unused |
| `jmc/trust-credentials` | Strip of credential logos at the top of the site footer                        | Dynamic PHP               | Direct inserter disabled; allows only `jmc/credential-list`; placed and locked in the footer part; palettes, defaulting to the surrounding palette; renders nothing without logos |
| `jmc/text-with-image` | Composable text content paired with an image                                       | Dynamic PHP + view script | Palettes and left/right order; defaults to default/right; unlocked: allows `jmc/heading`, `jmc/paragraph`, `jmc/list` and `jmc/button`; contains legacy-looking attributes that need contract review |

This table should be updated whenever a block is added, renamed, deprecated, or removed.

### Composition model

- Section blocks are assigned to `jmc-section` and are intended for direct insertion.
- Content blocks are used inside sections. Those authors add themselves (`jmc/heading`, `jmc/paragraph`, `jmc/button`, `jmc/buttons`, `jmc/list`) are in the inserter but declare an `ancestor` list, so they appear only inside the sections that use them and never at the top level of a page. Purely structural blocks such as `jmc/column` keep `supports.inserter: false`.
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

`mailto:` links are rejected unless the caller passes `allow_mailto: true`; the header CTA does not, `jmc/button` does (see [Buttons](#buttons)).

The block renders nothing when the label is empty or the link is missing, malformed, or rejected. Telephone links never open in a new tab. Output uses the `button-link` component with the `jmc-button--small` size modifier.

The block is not insertable, so a site whose header template part has been customised in the Site Editor does not pick it up from `parts/header.html`. Either reset the header template part, or add `<!-- wp:jmc/header-cta {"lock":{"remove":true}} /-->` inside the `jmc/navigation` block of the saved part.

## Call-to-action banner

`jmc/cta-banner` is an insertable section for a closing call to action: a heading, supporting copy, and one or more contact actions. It starts from a template of two `jmc/column` blocks:

```text
jmc/cta-banner
├── jmc/column
│   └── jmc/heading (level 2)
└── jmc/column
    ├── jmc/paragraph
    └── jmc/buttons
        └── jmc/button (one or more)
```

The section is locked with `templateLock: 'insert'`, so the two columns cannot be added or removed (they can be reordered, though the `order` setting is the usual way to swap sides). Each column unlocks its own content: `jmc/column` looks up its parent and, inside `jmc/cta-banner`, sets `templateLock: false` and allows `jmc/heading`, `jmc/paragraph`, `jmc/list`, `jmc/buttons` and `jmc/contact-details`. Authors can therefore add, remove and reorder content in either column; the template is only the starting content. `jmc/buttons` is unlocked too, so buttons can be added, removed, and reordered inside it.

The section does not use a full lock because a fully locked block re-syncs its inner blocks to the template, recursively, which would discard anything an author adds to a column. Columns in other sections, such as `jmc/service-grid`, are unaffected and keep their section's lock.

The **CTA Banner (Contact Details)** variation uses a different template, with no buttons:

```text
jmc/cta-banner (layout: contact)
├── jmc/column
│   ├── jmc/heading (level 2)
│   └── jmc/paragraph
└── jmc/column
    └── jmc/contact-details
```

### Attributes

| Attribute | Values | Default | Output |
| --- | --- | --- | --- |
| `layout` | `columns`, `stacked`, `contact` | `columns` | `stacked` adds `jmc-cta--stacked`; `contact` adds `jmc-cta--contact` |
| `palette` | `default`, `secondary`, `inverse` | `inverse` | No class, `jmc-palette--secondary`, or `jmc-palette--inverse` |
| `order` | `left`, `right` | `right` | `jmc-column-left` or `jmc-column-right` on the container; not used by the stacked layout |

Unknown or malformed values fall back to the default. The `layout` attribute is exposed as three block variations, **CTA Banner** (columns, the default), **CTA Banner (Stacked)** and **CTA Banner (Contact Details)**, which can be chosen in the inserter or switched from the block's variation control.

- **Columns** stacks the two columns on narrow screens and places them side by side from the `$tablet` width. `order` decides which side the copy and buttons sit on; the editor labels it **Buttons Left** / **Buttons Right**.
- **Contact Details** stacks on narrow screens. From `$tablet` it gives the heading and copy two-thirds of the width and the contact details one-third, and bottom-aligns the details with the copy. `order` places the details column; the editor labels it **Contact Details Left** / **Contact Details Right**, and the default puts the details on the right. Fractions are used rather than content sizing, because section columns are `inline-size` containers and report no content width to the grid. Switching an existing banner to or from this variation rebuilds its columns from the new template (the editor does this explicitly, since the section no longer re-syncs), discarding their content; undo restores it.
- **Stacked** keeps one centred column at every width, limited to `--jmc-container-sm`, with the heading above the copy and centred buttons. `order` has no effect and its control is hidden.

### Empty content

Headings, paragraphs, buttons, and the buttons group each render nothing when empty. The banner then removes any column left with no content, so it does not hold an empty grid cell, and a remaining single column spans the full width. When nothing is left, the banner renders nothing at all. The editor still shows the template's placeholders.

### Migrating existing banners

Banners saved before `jmc/buttons` was introduced hold `jmc/button` directly in the second column. They keep rendering on the frontend, and, now that the section no longer re-syncs to its template, they keep that button in the editor too. A bare button is not one of the blocks a CTA column allows, so it can be edited but not duplicated or moved; to add more buttons, insert a **Buttons** group, recreate the button inside it, and remove the old one.

## Buttons

`jmc/button` renders a single `.jmc-button` link through the `button-link` component. It is in the inserter only inside `jmc/text-with-image`, `jmc/hero` and `jmc/buttons` (its `ancestor` list). Inside `jmc/buttons`, new buttons are usually added from the group's **+** menu (**Button**, **Phone button**, **Email button**, or **WhatsApp button**), which presets the link type. Its `buttonType` attribute selects `primary` (default) or `secondary` (`jmc-button__secondary`).

### Link types

`linkType` decides where the button links. It is exposed as four block variations that can be switched from the block's variation control:

| `linkType` | Variation | Destination |
| --- | --- | --- |
| `link` (default) | Button | The button's own `link` (`{ url, opensInNewTab }`), edited from the toolbar |
| `phone` | Phone Button | The site-wide business phone number |
| `email` | Email Button | The site-wide business email address |
| `whatsapp` | WhatsApp Button | A WhatsApp chat (`https://wa.me/<digits>`) with the site-wide WhatsApp number |

Unknown values fall back to `link`. Phone, email and WhatsApp buttons have no link toolbar; their sidebar shows the shared detail instead (see [Business contact settings](#business-contact-settings)). New phone, email and WhatsApp buttons start with the labels "Call us", "Email us" and "Message us on WhatsApp".

WhatsApp buttons show the WhatsApp logo (`jmc/whatsapp`) before their label, through the `button-link` component's optional `icon` argument. It is decorative and takes the button's text colour; see [Brand icons](#brand-icons). In the editor the variation and the **+** menu use the same logo (`src/components/WhatsAppIcon.tsx`), because `block.json` icons can only name a Dashicon.

### Pre-filled messages

Email and WhatsApp buttons can carry their own optional message, set in the button's **Pre-filled Message** panel. The visitor can change it before sending.

| Attribute | Used by | Added to the link as |
| --- | --- | --- |
| `emailSubject` | Email buttons | `subject=` (single line) |
| `emailBody` | Email buttons | `body=`, line breaks as `%0D%0A` (RFC 6068) |
| `whatsappMessage` | WhatsApp buttons | `text=`, line breaks as `%0A` |

`jmc_contact_url_with_message()` builds the query: it keeps line breaks (at most one blank line in a row), collapses other whitespace, encodes with `rawurlencode()` (spaces as `%20`, since `+` is literal in `mailto:`), and leaves out empty parts. Phone buttons and plain link buttons ignore these attributes.

### Link escaping

`jmc_the_attributes()` escapes every `href` with `jmc_esc_href()`. It is `esc_url()`, except that encoded line breaks survive inside the `body` of a `mailto:` link and the `text` of a `https://wa.me/` link. `esc_url()` strips them everywhere to prevent header injection; inside a message they are harmless and needed. Line breaks in any other part, including mail header fields such as `subject` or `bcc`, are stripped as usual, field names are matched case-insensitively, and a link that already contains the internal placeholder falls back to plain `esc_url()`. Every other link is escaped exactly as before.

### Link validation

A `link` button's destination is validated by `jmc_safe_link_url()` with `allow_mailto: true`:

| Destination | Result |
| --- | --- |
| `http://` or `https://` URL with a host, or a site-relative path | Rendered unchanged |
| `tel:` link | Normalised to `tel:` plus an optional `+` and 3–15 digits |
| `mailto:` link | One valid address, optionally followed by a query such as `?subject=Quote%20request`; the scheme is lower-cased |
| Anything else, including `#`, `javascript:`, `//host`, several recipients, and values containing whitespace | Rejected |

In the editor, a bare phone number or email address typed into the link field is saved as a `tel:` or `mailto:` link (`src/utils/contactLink.ts`). Without this, the link control would save it as `https://01234 567890` or `https://hello@example.com`.

The button renders nothing when its label is empty or its destination is missing or rejected. `opensInNewTab` opens a web link in a new tab with `rel="noopener noreferrer"` and a screen-reader hint; content saved with the older `link.target: "_blank"` shape is honoured too. Telephone and email links never open in a new tab.

### Buttons group

`jmc/buttons` wraps its buttons in `<div class="jmc-buttons">`: full-width and stacked on narrow screens, then a wrapping row from the `$compact` width. It renders nothing when all of its buttons are omitted. It can be used in any section, though only `jmc/cta-banner` places it today.

## Lists

`jmc/list` is a styled list of short points, such as benefits or included services. It holds `jmc/list-item` blocks and can be inserted inside `jmc/text-with-image` (after the paragraph, for example) and inside either column of `jmc/cta-banner`. It is not part of any default template.

### Attributes

| Attribute | Values | Default | Output |
| --- | --- | --- | --- |
| `listStyle` | `icon`, `bullet`, `number` | `icon` | `<ul class="jmc-list jmc-list--icon" role="list">`, `<ul class="jmc-list jmc-list--bullet">`, or `<ol class="jmc-list jmc-list--number">` |
| `icon` | `check`, `circle-check`, `shield-check`, `plus`, `zap`, `arrow-right` | `check` | The Lucide icon `jmc/<icon>` before each item; used only by the `icon` style |

The list passes both values to its items as the `jmc/listStyle` and `jmc/listIcon` context. Unknown or malformed values fall back to the defaults, in PHP (`render.php` in both blocks) and in the editor (`src/blocks/content/list/options.ts`, which also holds the editor's option lists). The values must match the `enum`s in `block.json`, the PHP allowlists, and `src/icons/lucide.json`; `options.test.ts` checks the editor side.

### Markup and accessibility

```html
<ul class="jmc-list jmc-list--icon" role="list">
	<li class="jmc-list__item">
		<svg class="jmc-list__icon lucide" aria-hidden="true" focusable="false">…</svg>
		<span class="jmc-list__text">Fully <strong>insured</strong></span>
	</li>
</ul>
```

- Icon lists remove the browser's markers with `list-style: none`, so they carry `role="list"`; Safari otherwise stops announcing them as lists. Bulleted and numbered lists keep their native markers and need no role.
- Icons are decorative: `wp_get_icon()` marks them `aria-hidden="true"`, so screen readers announce only the text.
- Item text allows bold, italic and links (`core/bold`, `core/italic`, `core/link` in the editor) and is filtered with `wp_kses()` to `<strong>`, `<em>` and `<a href target rel>` on output.

### Empty content

An item with no visible text renders nothing; the check runs on the text before filtering, so markup such as a lone `<script>` cannot leave stray text behind. A list whose items all render nothing renders nothing.

### Presentation

The icon or marker sits in a gutter (`--jmc-list-gutter`: the icon size plus `--jmc-padding-2xs`) and is aligned to the first line of text, so long items wrap beneath their text rather than under the icon. The list is indented from the surrounding content by `--jmc-list-indent` (`--jmc-padding-xs`), limited to the paragraph measure (`50ch`), and spaced like a paragraph from the next block. Icons and markers use `--jmc-text-highlight` and the text inherits the section's text colour, so lists follow the `default`, `secondary` and `inverse` palettes.

In the editor the list spans the column (less the indent) so its **+** appender sits in the bottom corner; items keep the frontend measure, so text wraps the same way. Enter at the end of an item starts a new item (`supports.splitting`), and Backspace at the start of an item joins it to the previous one.

## Business contact settings

The business contact details are site-wide options, registered in `inc/contact.php` with helpers in `inc/helpers/contact.php`. They are the single source of truth for Phone, Email and WhatsApp buttons, the Contact Details block and the footer's business details:

| Option | Edited in | Stored value |
| --- | --- | --- |
| `jmc_business_phone` | **Settings → General → Business phone number**, a Phone button's sidebar, or the Contact Details or Footer Business Details sidebar | The number as written, with whitespace collapsed, only when it is a valid `tel:` number; otherwise empty |
| `jmc_business_whatsapp` | **Settings → General → WhatsApp number**, a WhatsApp button's sidebar, or the Contact Details or Footer Business Details sidebar | The number as written, only when it is in international format (optional `+`, 8–15 digits, no leading `0`); otherwise empty |
| `jmc_business_email` | **Settings → General → Business email address**, an Email button's sidebar, or the Contact Details or Footer Business Details sidebar | One valid address; otherwise empty |
| `jmc_business_hours` | **Settings → General → Opening hours**, or the Contact Details or Footer Business Details sidebar | `{ monday: { opens: "08:00", closes: "17:30" }, … }` in week order; a day is kept only with two valid 24-hour `HH:MM` times and a closing time after the opening time, otherwise it counts as closed |
| `jmc_business_service_area` | **Settings → General → Service area** (one place per line), or the Contact Details or Footer Business Details sidebar | A list of place names: trimmed, whitespace collapsed, de-duplicated case-insensitively, and without blank entries, entries containing `<` or `>`, or entries over 100 characters |

All are registered with `show_in_rest` and a REST schema, so the block editor edits them through the site entity (`src/components/SiteSetting.tsx`; the shared **Business details** sidebar panel is `src/components/ContactSettings.tsx`) and they are saved with the site settings when the post is saved: changing one updates every block that uses it. Users who cannot manage options cannot read them, so the sidebars show a notice instead of the fields. The editor removes a cleared time rather than storing an empty one, which the schema would reject, and warns when a day will be saved as closed.

`jmc_business_contact_url( 'phone' | 'email' | 'whatsapp' )` re-validates the stored value and returns a `tel:`, `mailto:` or `https://wa.me/` URL, or an empty string. `jmc_business_contact_details()` returns every detail re-validated for display: a phone, email or WhatsApp value only with a working link, and opening hours for all seven days (`null` when closed) once any day is open.

## Contact details

`jmc/contact-details` shows the [business contact settings](#business-contact-settings) as a description list. It has no values of its own, so the details are the same everywhere; five `show*` toggles (`showPhone`, `showWhatsApp`, `showEmail`, `showHours`, `showServiceArea`, all on by default, malformed values counting as on) choose which fields to show. Its sidebar also edits the settings themselves (`ContactSettings`).

It is restricted to `jmc/column` (`parent`) and is placed by the [CTA Banner (Contact Details)](#call-to-action-banner) variation. The block has no heading; the banner's heading introduces it.

```html
<address class="jmc-contact-details">
  <dl class="jmc-contact-details__list">
    <div class="jmc-contact-details__item jmc-contact-details__item--phone">
      <dt class="jmc-contact-details__label">[icon]<span class="jmc-contact-details__label-text">Phone</span></dt>
      <dd class="jmc-contact-details__value"><a href="tel:01234567890">01234 567890</a></dd>
    </div>
    …
  </dl>
</address>
```

- Each item pairs a `<dt>` label with a `<dd>` value. The label text is visually hidden (`sr-only`), so each value sits beside its icon as in the footer, but screen readers still announce it, and it reads as "Phone: 01234 567890" without styles.
- Phone, WhatsApp and email values link with the value as written as the link text.
- Opening hours list every day in week order with `<time datetime="08:00">8:00 am</time> to <time …>` in the site's time format, or "Closed". Service areas are a list. Both lists carry `role="list"` because their bullets are removed.
- Icons are decorative and in the footer's style: `jmc/phone`, `jmc/whatsapp`, `jmc/mail`, `jmc/clock`, and `jmc/house`.
- A missing, invalid or hidden field is left out entirely, and the block renders nothing when no field is left.

In the editor the block previews `render.php` with `ServerSideRender`, re-rendering once the settings are saved; see [Editor previews](#editor-previews).

### Structured data

The block outputs no schema.org data, and the theme adds none centrally. Yoast is planned to own structured data, so a theme `LocalBusiness` object would duplicate or conflict with it. The settings are stored in structured form (per-day hours, a list of places) so an integration can use them later.

## Editor previews

Blocks that preview their server render (`jmc/contact-details`, `jmc/service-list`, `jmc/navigation`, and `jmc/footer-navigation`) wrap `ServerSideRender` in `Disabled`. The preview is inert, so clicking a link, phone number or email address selects the block instead of leaving the editor or opening another app. Other blocks edit their text directly and render no live links in the editor.

## Services grid

`jmc/service-grid` lists the site's services without anyone maintaining a list. Services are published pages directly beneath the Services page, and every card is built from those page records; see the [services content model](architecture/services-content-model.md) for the decision behind this.

The block holds two locked `jmc/column` blocks: an editable intro (a label paragraph and an H2) and a column containing `jmc/service-list`. `jmc/service-list` renders the cards on the server from page data, so there is no card content to edit inside the block. It replaces the hand-authored `jmc/service-card` block, which has been removed (see [Migrating from `jmc/service-card`](#migrating-from-jmcservice-card)).

### Choosing the Services page

`jmc_get_services_page()` (`inc/helpers/services.php`) resolves the parent page:

| Situation | Status | Result |
| --- | --- | --- |
| A published page is chosen under **Settings → Reading → Services page** | `configured` | That page's children are listed |
| Nothing is chosen, and a published page has the slug `services` | `resolved` | That page's children are listed |
| The chosen page is deleted, unpublished, trashed, or not a page | `invalid` | Nothing is listed. The slug is deliberately **not** used as a fallback, so the misconfiguration stays visible |
| Nothing is chosen, and there is no published `services` page | `missing` | Nothing is listed |

The choice is stored by page ID in the `jmc_services_page_id` option (also exposed through the REST settings endpoint), so renaming the Services page does not break the grid. Leaving the setting on its default (use the page with the `services` slug) is fine for a site whose Services page keeps that slug.

### Which pages appear

`jmc_services_query_args()` asks for pages that are direct children of the Services page, published, and not password-protected, ordered by `menu_order` then title, with no item limit. Drafts, private, scheduled, and password-protected pages, deeper descendants, and unrelated pages never appear. An unresolved parent runs no query at all, so top-level pages can never leak into the grid. Because cards are read from the pages on every render, changes to a service's title, excerpt, icon, slug, status, or order show up without editing the grid.

### Cards

Each card (`components/service-card.php`) is a list item containing:

- the service icon, rendered through the core Icon block and marked decorative;
- the page title as a heading, holding the card's only link, to the page's permalink. The level comes from the Service List block's **Card Settings** (`2`–`6`, default `3`; H1 is reserved for the page title);
- the page excerpt (`post_excerpt`) as the summary;
- a decorative arrow in the bottom-right corner.

The link is stretched over the whole card, so each card is one click or tap target with a single link and a single tab stop. Keyboard focus outlines the whole card, hover adds a shadow, and the arrow slides right on hover and focus (not with reduced motion). Titles and excerpts are escaped. A page with no title or URL produces no card; a missing excerpt or icon only omits that part, so older pages still render, but the publishing requirements below still apply.

### Layout

The intro and the cards share one grid on the section container: one column below `$tablet` (768px), two from `$tablet`, and three from `$laptop` (1024px), so the intro takes the first cell and the cards flow after it. To achieve this, every wrapper between the container and the cards (the column, the list, and the editor's block wrappers) uses `display: contents`. The list keeps `role="list"`, so its semantics survive. One side effect: in the editor the Service List block cannot be selected by clicking the canvas; select it in List View.

While the grid is one column, the intro gets extra vertical padding so it does not feel cramped above the cards. Cards use the `--jmc-card-background` surface token, with `--jmc-padding-m` inside below `$tablet` and `--jmc-padding-l` above.

### Empty states

When nothing can be listed, visitors see the intro and no list. Editors previewing the block (a REST request by a user who can edit posts) see a notice explaining why instead:

| Cause | Notice |
| --- | --- |
| No services published yet | Publish a page directly beneath the Services page, with an excerpt and icon |
| No Services page found | Choose one under **Settings → Reading**, or publish a page with the `services` slug |
| The chosen Services page is missing or unpublished | Choose a published page |

### Service icons

When a page's parent is the Services page, the page sidebar shows a **Service icon** panel. It offers the theme's `jmc` icons (see [Icons](#icons)) as a searchable grid, and shows the current choice with a **Remove** link. Interface icons that are not service icons, `jmc/arrow-right` and `jmc/phone`, are hidden from the picker by `INTERFACE_ICONS` in `src/editor/service-icon/utils.ts`.

The choice is stored in the `jmc_service_icon` page meta, which only users who can edit the page may change. `jmc_sanitize_service_icon()` saves the value only when it is a registered icon name, and the card checks it again when rendering, so an icon that is later unregistered simply disappears from the card. Saving accepts any registered icon, including core ones; only the picker is limited to theme icons.

The panel's script is `src/editor/service-icon/`, built to `build/js/editor/service-icon.js` by an extra entry in `webpack.config.js` and enqueued by `inc/services.php` on page editor screens only. Page excerpts are enabled by the same file.

### Publishing a service

1. Create a page and set its **Parent** to the Services page (**Page → Parent**).
2. Give it a unique, specific title. It becomes the card heading and link text.
3. Write an **Excerpt** in the page sidebar: one or two plain sentences for the card.
4. Choose a **Service icon**.
5. Set **Order** (**Page → Order**): lower numbers come first; equal numbers sort by title.
6. Publish. The service appears in every Service Grid immediately.

To retire a service, unpublish the page or move it from beneath the Services page, then review redirects and any menus that link to it.

### Migrating from `jmc/service-card`

Earlier versions stored service cards as `jmc/service-card` blocks inside the grid. That block no longer exists, so any saved content still containing it shows an unsupported-block warning in the editor. Recreate each service as a page (above), then remove the old block. To find affected content:

```bash
wp eval 'global $wpdb; print_r( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE \"%wp:jmc/service-card%\" AND post_type <> \"revision\"" ) );'
```

## Footer

`parts/footer.html` is the `footer` template part, registered in `theme.json` and placed at the end of every template as a `<footer class="jmc-footer">` landmark. It contains:

```html
<!-- wp:jmc/footer {"palette":"inverse","lock":{"remove":true}} -->
  <!-- wp:jmc/trust-credentials {"lock":{"remove":true}} -->
    <!-- wp:jmc/credential-list -->
      <!-- wp:jmc/credential /-->
    <!-- /wp:jmc/credential-list -->
  <!-- /wp:jmc/trust-credentials -->
  <!-- wp:jmc/footer-business {"lock":{"remove":true}} /-->
  <!-- wp:jmc/footer-navigation /-->
  <!-- wp:jmc/footer-legal {"lock":{"remove":true}} /-->
<!-- /wp:jmc/footer -->
```

### Footer container

`jmc/footer` paints its palette across the full width and centres its content in a `--jmc-container-xl` container. Each child block is one column: stacked on narrow screens, then side by side from the `$mobile` container width, at least `12rem` wide, sharing the width equally (so fewer columns spread further apart) and wrapping as space runs out. The credential logos (`jmc/trust-credentials`) and the legal row are the exceptions: the logos always take a full-width row along the top, and the legal row a full-width row along the bottom. The layout is a wrapping flexbox rather than an `auto-fit` grid, because a full-width item stops `auto-fit` from collapsing empty tracks and squeezes the columns to the left. The footer sets the smaller `p4` type style, and every footer link shares one hover and keyboard-focus treatment (highlight colour and underline). It renders nothing when its inner blocks produce no markup.

Its palette follows the Hero contract: `default` (no class), `secondary` (`jmc-palette--secondary`), and `inverse` (`jmc-palette--inverse`). The attribute default is `default`; the footer part sets `inverse`. Unknown or malformed values fall back to `default`.

### Credentials

The footer opens with a strip of accreditation, guarantee and review logos: `jmc/trust-credentials` holds one `jmc/credential-list`, which holds the `jmc/credential` logos. Credentials are logos only; text claims with a label and detail are out of scope (see #8). Edit them in the Site Editor on the **Site footer** template part. The section block is not in the inserter, so it exists only where the footer part places it; it is locked against removal there.

Each credential stores two attributes:

| Attribute | Purpose | Validation |
| --- | --- | --- |
| `imageId` | Media Library image shown as the logo, at the `medium` size | Must be a positive integer for an existing image attachment; otherwise the credential renders nothing |
| `link` | Optional `{ url, opensInNewTab }` destination, set from the block toolbar | `jmc_safe_link_url()` (web URLs, site paths and `tel:`; no `mailto:`); an unsafe or malformed link leaves the logo unlinked |

**Alternative text.** A logo is the credential itself, so it is informative, never decorative: its alt text comes from the Media Library and should name the credential (for example "NICEIC Approved Contractor"). When the image has no alt text, the image title is used so the logo, and any link around it, is never unnamed; the editor sidebar warns about the missing alt text. The [image component](#images) takes the alt as an argument.

**Links.** A linked logo is wrapped in one `<a>` whose clickable area covers the whole tile. With **Open in new tab**, the link gets `target="_blank"` and `rel="noopener noreferrer"`, and screen readers hear "(opens in a new tab)" after the logo's name. Telephone links never open a new tab. Keyboard focus outlines the tile with a 2px `--jmc-text-highlight` outline.

**Layout.** Logos sit in a centred, wrapping row of equal-width `3 / 2` tiles with no border or background, each image scaled to fit (`object-fit: contain`). Tiles are sized for 2 per row on narrow screens, then 3, 4 and 5 from the `$mobile`, `$tablet` and `$laptop` widths; a shorter row stays centred. In the footer the section drops its own padding and spans a full row.

**Empty states.** A credential without a usable image renders nothing, a list with no rendered credentials renders nothing, and the section renders nothing when its list is empty, so the footer shows no empty band until a logo is added. In the editor, an empty credential is a dashed **Add logo** tile that opens the Media Library, and the list's standard **+** appender adds another credential. A new list starts with one empty credential.

The section's `palette` attribute (`default`, `secondary`, `inverse`) follows the Hero contract; `default` adds no class, so in the footer the logos sit on the footer's own palette.

### Business details

`jmc/footer-business` shows the business name and address, stored on the block, followed by the phone, WhatsApp, email and service area from the [business contact settings](#business-contact-settings), so the footer always matches the contact buttons and Contact Details blocks. Edit the block in the Site Editor (**Appearance → Editor → Patterns → Template parts → Site footer**): the name and address directly on the block, the shared details in its **Business details** sidebar panel (or Settings → General). Every line is optional:

| Line | Source | Output | Icon | Validation |
| --- | --- | --- | --- | --- |
| Name | `companyName` attribute | Bold name linking to `home_url( '/' )` | None | — |
| Address | `address` attribute | Inside `<address>`; Shift+Enter line breaks become `<br>` | `jmc/map-pin` | — |
| Phone | `jmc_business_phone` | Inside `<address>`; a `tel:` link | `jmc/phone` | Omitted when missing or not diallable |
| WhatsApp | `jmc_business_whatsapp` | Inside `<address>`; a `wa.me` link, with a visually hidden "WhatsApp:" label so screen readers don't hear a second phone number | `jmc/whatsapp` | Omitted when missing or not international |
| Email | `jmc_business_email` | Inside `<address>`; a `mailto:` link | `jmc/mail` | Omitted when missing or invalid |
| Service area | `jmc_business_service_area` | After `<address>`: a bold, translatable "Areas covered:" label and the places as one phrase, "Pudsey, Leeds and Bradford" (the "and" is translatable) | `jmc/house` | Omitted when the list is empty |

The shared lines are read through `jmc_business_contact_details()`, so a value is only shown with a working link. Opening hours are not shown in the footer; use the Contact Details block for them. The name and address are saved by formatless `RichText`, so render code converts them with `jmc_plain_text()` (`inc/helpers/text.php`): line breaks become newlines, other markup is removed, entities are decoded once, and blank lines are dropped. Output is escaped afterwards. Empty lines are omitted, `<address>` is omitted when there is no address, phone, WhatsApp or email, and the block renders nothing when every line is empty.

In the editor the shared lines are read-only and show the **saved** settings, which the REST API has already sanitised, so they match the frontend; sidebar edits appear once the template part is saved. Their links don't navigate in the editor. When no shared detail is set, the canvas shows an editor-only hint pointing to the sidebar.

**Legacy attributes.** Before #115 the block stored its own `phone`, `email` and `serviceArea` string attributes. They are still declared in `block.json`, so saving the footer keeps them, but they are not rendered or editable. On the first request after upgrading, `jmc_migrate_footer_contact_details()` (`inc/contact.php`, on `init`) reads the active theme's customised `footer` template part and copies each value into its setting when that setting is empty or invalid, sanitised as if submitted. The free-text service area is split into places on new lines, commas, semicolons, `&` and the word "and". Settings that already hold a value are never overwritten, and a value the settings reject (such as an undiallable phone number) is not copied but stays in the block attributes. The run is recorded in the autoloaded `jmc_footer_contact_migrated` option; delete that option to run it again.

### Footer icons

Footer icons come from the core icon registry through `wp_get_icon()`; see [Icons](#icons). They are decorative (`aria-hidden`), because each line's text already says what it is. They are Lucide outlines (`jmc/map-pin`, `jmc/phone`, `jmc/mail`, `jmc/house`) plus the filled WhatsApp logo (`jmc/whatsapp`), and use `--jmc-text-highlight`; the Contact Details block uses the same set.

### Footer navigation

Each `jmc/footer-navigation` block renders a flat list of links from the published `wp_navigation` menu chosen in its sidebar. Unlike `jmc/navigation`, it does not pick a menu automatically, has no mobile toggle, and loads no script. Submenu links are flattened into the list. The `<nav>` is labelled with the menu title, or "Footer" when the menu has none, so give each menu a meaningful title. The current page's link gets `aria-current="page"`; custom links never do. The block renders nothing when no menu is selected, the post is not a menu, or the menu has no links.

Add a column by inserting another Footer Navigation block inside the footer; remove one with the block toolbar. Create menus in **Appearance → Editor → Navigation**, or with WP-CLI:

```bash
wp post create --post_type=wp_navigation --post_status=publish --post_title="Services"
```

### Legal row

`jmc/footer-legal` closes the footer with a full-width row above a thin rule, its text a step (`0.875em`) smaller than the footer's `p4`. In reading order it contains:

1. **Site credit** — "Site created by John McPherson", linking to `mailto:mcpherso@pm.me`. The name and email are set in `render.php` (and mirrored in `edit.tsx`), not stored as attributes, because the credit is not site content.
2. **Copyright notice** — `© {year} {holder}`. The year comes from `wp_date( 'Y' )` on every render, in the site's timezone, so it never needs updating. The holder is the `copyrightHolder` attribute, edited inline on the block; when it is empty the site title (**Settings → General**) is used, and when both are empty the notice shows the year alone. The holder is normalised with `jmc_plain_text()` and escaped.
3. **Legal links** — the links from the theme-registered `legal` menu (see [Navigation menus](#navigation-menus)), in a `<nav>` labelled with the menu title, or "Legal" when it has none. Links follow the footer navigation contract: submenus are flattened, and the current page's link gets `aria-current="page"`. The `<nav>` is omitted when the menu is missing, unpublished, or has no links; the credit and copyright always render.

The credit sits directly above the copyright. On narrow screens the links stack beneath them; from the `$mobile` container width the links sit to the right, aligned with the copyright line.

To change the legal links, edit the **Legal** menu in **Appearance → Editor → Navigation**. The block has no menu setting: it always uses the `legal` slug, so the same template part works in every environment.

### Customised footer parts

Once the footer part has been saved in the Site Editor, the saved copy overrides `parts/footer.html`, so later changes to the theme file do not appear. Reset the **Site footer** template part to pick them up, or add the missing blocks to the saved part by hand.

## Navigation menus

Block themes have no menu locations: menus are `wp_navigation` posts. `inc/navigation.php` registers the theme's menus by slug instead:

| Slug | Title | Used by |
| --- | --- | --- |
| `main` | Main | Not yet; see #103 |
| `footer` | Footer | Not yet; see #103 |
| `legal` | Legal | `jmc/footer-legal` |

`jmc_ensure_navigation_menus()` creates any registered menu that is missing as a published `wp_navigation` post with that slug and title. It runs on `after_switch_theme` and on `init`, but only does work when the registered slugs change: it records the slug list in the `jmc_navigation_menus` option and returns early while that matches. It never changes or duplicates an existing menu, whatever its status (including drafts and trashed menus), and a menu an editor deletes is not recreated until the registered slugs change. Editors manage each menu's links, and may rename it, in **Appearance → Editor → Navigation**.

Look a menu up with `jmc_get_navigation_by_slug( slug: 'legal' )` (`inc/helpers/navigation.php`), which returns the published menu or `null`; pass `post_status` to include other statuses. Reference menus by slug rather than post ID in blocks and template parts, because IDs differ between environments.

To add a menu, add its slug and title to `jmc_navigation_menus()`; the changed slug list makes the next request create it.

## Icons

Theme icons are registered with the core Icons API in the `jmc` collection (`inc/icons.php`), so they render through `wp_get_icon( 'jmc/<name>' )` or the core Icon block exactly like core icons, and appear in the Icon block's picker.

| Source | Icons | Used by |
| --- | --- | --- |
| [Lucide](https://lucide.dev) | Listed in `src/icons/lucide.json` | Service icons, the service card arrow, footer and contact details icons, list markers |
| [Simple Icons](https://simpleicons.org) | Listed in `src/icons/simple-icons.json` | Brand logos: `jmc/whatsapp` on WhatsApp buttons and contact details |

Icons are decorative (`aria-hidden`) unless they carry meaning that the surrounding text does not.

### Lucide icons

Lucide is free to use under the ISC licence (some icons, derived from Feather, under MIT). It is a development dependency, `lucide-static`, and only the icons listed in `src/icons/lucide.json` ship with the theme. `npm run build:icons`, which runs as part of `npm run build`, executes `scripts/icons/build.js`. For each listed icon it:

1. reads the icon's shapes from `lucide-static/icon-nodes.json`;
2. converts `rect`, `circle`, `ellipse`, `line`, `polyline`, and `polygon` to equivalent paths (`scripts/icons/shapes.js`), because the registry's sanitiser keeps only `svg`, `path`, and `polygon` and drops stroke attributes;
3. writes `build/assets/icons/lucide/<name>.svg`.

It also writes `manifest.json` and copies Lucide's `LICENSE` into that directory, so the licence ships with the packaged theme. `inc/icons.php` registers each manifest entry as `jmc/<name>`. The build fails if a listed name does not exist in Lucide or uses a shape the converter does not support.

To add an icon, find its name on lucide.dev, add `"<name>": "<Label>"` to `src/icons/lucide.json`, and run `npm run build`. It then appears in the Service icon picker. If it is an interface icon rather than a service icon, also add it to `INTERFACE_ICONS` in `src/editor/service-icon/utils.ts`. To update Lucide, bump `lucide-static`, rebuild, and check the icons in use.

### Brand icons

Brand logos come from Simple Icons, a development dependency (`simple-icons`), the same way as Lucide. For each slug in `src/icons/simple-icons.json`, `scripts/icons/build.js` reads `simple-icons/icons/<slug>.svg`, keeps its single path (the registry would strip the `<title>`), and writes `build/assets/icons/simple-icons/<slug>.svg` with the class `simple-icon`, plus `manifest.json` and Simple Icons' `LICENSE.md`. `inc/icons.php` registers both sets in the `jmc` collection. The build fails if a slug does not exist or is not a single path. Brand icons are interface icons, so add them to `INTERFACE_ICONS` too.

Simple Icons is CC0, but each logo remains its owner's trademark and is subject to that brand's guidelines, linked from the icon's entry in `simple-icons/data/simple-icons.json`. The WhatsApp logo is used to link to WhatsApp chats and is shown in the theme's colours (the surrounding text or button colour) rather than WhatsApp green: a deliberate decision, because green would clash with the brand palette.

### Styling icons

Lucide icons are outlines: `svg.lucide` in `src/scss/components/_icons.scss` sets `fill: none` and a `currentcolor` stroke (width 2, round caps and joins), so they follow `color`. Brand icons are filled: `svg.simple-icon` sets `fill: currentcolor`. Core icons lose their `fill` attribute in the registry, so their styles must set `fill: currentcolor`.

Two WordPress styles sit outside the theme's cascade layers and therefore beat any layered rule, whatever its specificity:

- The core Icon block sets `.wp-block-icon svg { fill: currentColor }`. An unlayered `.wp-block-icon svg.lucide { fill: none }` keeps Lucide icons as outlines.
- Global styles set `:root :where(.wp-block-icon svg) { width: 24px }`. To size an icon rendered through the Icon block, target its SVG with an unlayered rule that is more specific, as `.jmc-service-card__icon svg` does.

## Button sizes

`.jmc-button` is the default size. Add `.jmc-button--small` for dense UI such as the header; it changes only padding, type size, and border width, never colours, so it combines with `.jmc-button__secondary` and every palette.

Keyboard focus (`:focus-visible`) uses the hover colours plus a 2px `--jmc-text-highlight` outline offset by 2px. Every button colour pair meets WCAG AA in the default, secondary, and inverse palettes (lowest text pair 8.15:1), and the focus outline is at least 3:1 against each palette background (lowest 3.65:1, default palette).

## Images

Background images are decorative: they must not contain information needed to understand the page. A meaningful image must render as an `<img>` or image block with appropriate alternative-text handling.

`components/image.php` renders a Media Library image with `wp_get_attachment_image()`. Its arguments are `image_id` (required; nothing renders without it), `classes`, `size` (default `large`), `sizes` (default `(max-width: 768px) 100vw, 50vw`) and `alt`. Without `alt`, the Media Library alternative text is used; a string `alt` replaces it, trimmed, and an empty string marks the image as decorative. Images load lazily.

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
- Remove or implement the `order` attribute declared by `jmc/service-grid`; it has no effect, and the editor looks it up in the palette class map.
