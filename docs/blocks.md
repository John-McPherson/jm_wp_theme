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
| `jmc/button`          | Linked call-to-action                                                              | Dynamic PHP               | Internal composition block; primary or secondary; Button, Phone Button and Email Button variations (`linkType`); direct inserter disabled; see [Buttons](#buttons) |
| `jmc/buttons`         | Group of one or more `jmc/button` blocks                                           | Dynamic PHP               | Allows only `jmc/button`; unlocked inside locked sections; adds buttons from its own **+** menu; renders nothing when every button is omitted; direct inserter disabled |
| `jmc/column`          | Groups related inner content within a section                                      | Dynamic PHP               | Structural composition block; direct inserter disabled                                                                 |
| `jmc/footer`          | Site footer container with a selectable palette                                    | Dynamic PHP               | Site chrome; direct inserter disabled; single instance; allows only footer blocks, each rendered as a column, with the legal row spanning the bottom; inverse and locked in the footer part |
| `jmc/footer-business` | Business name, address, telephone, email, and service area                          | Dynamic PHP               | Restricted to `jmc/footer`; direct inserter disabled; single instance; locked against removal in the footer part      |
| `jmc/footer-navigation` | A column of links from a selected `wp_navigation` menu                          | Dynamic PHP               | Restricted to `jmc/footer`; insertable there, multiple allowed (one column each); no toggle or view script            |
| `jmc/footer-legal`    | Site credit, copyright notice with the current year, and links from the `legal` menu | Dynamic PHP              | Restricted to `jmc/footer`; direct inserter disabled; single instance; locked against removal in the footer part      |
| `jmc/header-cta`      | Optional header call-to-action linking to a page, URL, or telephone number         | Dynamic PHP               | Restricted to `jmc/navigation`; direct inserter disabled; single instance; locked against removal in the header part   |
| `jmc/navigation`      | Primary navigation rendered from a selected `wp_navigation` menu                    | Dynamic PHP + view script | Site chrome; direct inserter disabled; allows only `jmc/header-cta`, rendered as the last menu item                     |
| `jmc/heading`         | Semantic heading with configurable level                                           | Dynamic PHP               | Uses `jmc/variant` context; level defaults to `2`; direct inserter disabled                                            |
| `jmc/paragraph`       | Paragraph or small label text                                                      | Dynamic PHP               | `default` and `label` variations; uses `jmc/variant`; direct inserter disabled                                         |
| `jmc/service-list`    | Linked cards for the published child pages of the Services page                    | Dynamic PHP               | Restricted to `jmc/service-grid`; direct inserter disabled; card heading level `2`–`6`, default `3`; previewed in the editor with `ServerSideRender`; see [Services grid](#services-grid) |
| `jmc/cta-banner`      | Call-to-action section with a heading, supporting copy, and one or more buttons     | Dynamic PHP               | Columns and Stacked variations (`layout`); palettes; left/right order (columns only); defaults to inverse/right/columns; see [Call-to-action banner](#call-to-action-banner) |
| `jmc/enquiry-form`    | Enquiry form section with an optional introduction                                 | Dynamic PHP + view script | Palettes; single instance; submits without reloading the page and works without JavaScript; stores and emails each enquiry; see [Enquiry form](#enquiry-form) |
| `jmc/hero`            | Page introduction with composable content and optional decorative background image | Dynamic PHP               | Single-instance support; provides `jmc/variant`; supports default, secondary, and inverse palettes; invalid values fall back to default |
| `jmc/service-grid`    | Introductory content and the published services in one responsive grid              | Dynamic PHP               | Two locked Column children: the intro and `jmc/service-list`; palettes; defaults to default; the `order` attribute is currently unused |
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

`mailto:` links are rejected unless the caller passes `allow_mailto: true`; the header CTA does not, `jmc/button` does (see [Buttons](#buttons)).

The block renders nothing when the label is empty or the link is missing, malformed, or rejected. Telephone links never open in a new tab. Output uses the `button-link` component with the `jmc-button--small` size modifier.

The block is not insertable, so a site whose header template part has been customised in the Site Editor does not pick it up from `parts/header.html`. Either reset the header template part, or add `<!-- wp:jmc/header-cta {"lock":{"remove":true}} /-->` inside the `jmc/navigation` block of the saved part.

## Call-to-action banner

`jmc/cta-banner` is an insertable section for a closing call to action: a heading, supporting copy, and one or more contact actions. Its content is a locked template of two `jmc/column` blocks:

```text
jmc/cta-banner
├── jmc/column
│   └── jmc/heading (level 2)
└── jmc/column
    ├── jmc/paragraph
    └── jmc/buttons
        └── jmc/button (one or more)
```

Authors edit the text and buttons but cannot add, remove, or move the columns, heading, or paragraph. `jmc/buttons` is unlocked, so buttons can be added, removed, and reordered inside it.

### Attributes

| Attribute | Values | Default | Output |
| --- | --- | --- | --- |
| `layout` | `columns`, `stacked` | `columns` | `stacked` adds `jmc-cta--stacked` |
| `palette` | `default`, `secondary`, `inverse` | `inverse` | No class, `jmc-palette--secondary`, or `jmc-palette--inverse` |
| `order` | `left`, `right` | `right` | `jmc-column-left` or `jmc-column-right` on the container; columns layout only |

Unknown or malformed values fall back to the default. The `layout` attribute is exposed as two block variations, **CTA Banner** (columns, the default) and **CTA Banner (Stacked)**, which can be chosen in the inserter or switched from the block's variation control.

- **Columns** stacks the two columns on narrow screens and places them side by side from the `$tablet` width. `order` decides which side the copy and buttons sit on; the editor labels it **Buttons Left** / **Buttons Right**.
- **Stacked** keeps one centred column at every width, limited to `--jmc-container-sm`, with the heading above the copy and centred buttons. `order` has no effect and its control is hidden.

### Empty content

Headings, paragraphs, buttons, and the buttons group each render nothing when empty. The banner then removes any column left with no content, so it does not hold an empty grid cell, and a remaining single column spans the full width. When nothing is left, the banner renders nothing at all. The editor still shows the template's placeholders.

### Migrating existing banners

Banners saved before `jmc/buttons` was introduced hold `jmc/button` directly in the second column. They keep rendering on the frontend. Because the section is locked to its template, opening one in the editor replaces that button with an empty `jmc/buttons` group, so its label and destination must be re-entered before saving.

## Buttons

`jmc/button` renders a single `.jmc-button` link through the `button-link` component. It is hidden from the inserter, so inside `jmc/buttons` new buttons are added from the group's **+** menu (**Button**, **Phone button**, or **Email button**). Its `buttonType` attribute selects `primary` (default) or `secondary` (`jmc-button__secondary`).

### Link types

`linkType` decides where the button links. It is exposed as three block variations that can be switched from the block's variation control:

| `linkType` | Variation | Destination |
| --- | --- | --- |
| `link` (default) | Button | The button's own `link` (`{ url, opensInNewTab }`), edited from the toolbar |
| `phone` | Phone Button | The site-wide business phone number |
| `email` | Email Button | The site-wide business email address |

Unknown values fall back to `link`. Phone and email buttons have no link toolbar; their sidebar shows the shared detail instead (see [Business contact settings](#business-contact-settings)). New phone and email buttons start with the labels "Call us" and "Email us".

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

## Business contact settings

The business phone number and email address used by Phone and Email buttons are site-wide options, registered in `inc/contact.php` with helpers in `inc/helpers/contact.php`:

| Option | Edited in | Stored value |
| --- | --- | --- |
| `jmc_business_phone` | **Settings → General → Business phone number**, or a Phone button's sidebar | The number as written, with whitespace collapsed, only when it is a valid `tel:` number; otherwise empty |
| `jmc_business_email` | **Settings → General → Business email address**, or an Email button's sidebar | One valid address; otherwise empty |

Both are registered with `show_in_rest`, so the block editor edits them through the site entity and they are saved with the site settings: changing one updates every button of that type. Users who cannot manage options cannot read them, so the button sidebar shows a notice instead of the field. An invalid value is saved as empty, which hides those buttons.

Enquiries from the [enquiry form](#enquiry-form) are sent to `jmc_business_email`, falling back to the site admin email.

`jmc_business_contact_url( 'phone' | 'email' )` re-validates the stored value and returns a `tel:` or `mailto:` URL, or an empty string. `jmc/footer-business` does not use these settings yet; see #115.

## Enquiry form

`jmc/enquiry-form` is a section with an optional introduction (paragraphs only) above a single, centred form column. One is allowed per page. Its attributes are `palette` (`default`, `secondary`, `inverse`; default `default`) and `submitText` (the button label; default "Send enquiry"). Its anchor, or `enquiry-form`, names the section and prefixes every field ID.

### Fields

| Field | Required | Rules |
| --- | --- | --- |
| Name | Yes | Up to 100 characters |
| Email address | Yes | A valid address; replies go to it |
| Phone number | No | Digits, spaces, brackets, dashes and a leading `+` (3–15 digits) |
| Postcode | No | A full UK postcode, stored as `LS1 4AP` |
| Service | No | One of the [Services pages](#services-grid); the select is left out when there are none |
| Message | Yes | Up to 5,000 characters; line breaks kept |
| Consent | Yes | Agreeing to the business using the details to reply; links to the privacy policy page when one is set |

`jmc_validate_enquiry()` (in `inc/helpers/enquiry.php`) is the single source of these rules. `jmc_enquiry_error_messages()` supplies the messages to both the server and the view script, so a visitor sees the same text either way.

### Submitting

With JavaScript, `view.ts` validates the form, posts it to `POST /wp-json/jmc/v1/enquiries` and updates the page in place:

- **Sent** — the form is hidden and a confirmation is focused. A `jmc:enquiry-sent` event is dispatched on `document`, with no personal data, for analytics (#83).
- **Invalid** — each field shows its error and `aria-invalid`, and a focused summary links to each field.
- **Expired** — fresh tokens are fetched and the visitor is asked to send again.
- **Rate limited, error or no connection** — a message offers the business phone number and email.

While sending, the button is `aria-disabled` and further submissions are ignored. When the page loads, the script fetches a fresh nonce and timing token from `GET /wp-json/jmc/v1/enquiries/token`, so forms on cached pages still work.

Without JavaScript, the form posts to `admin-post.php` (action `jmc_enquiry`) and native validation applies. The same handler runs, then redirects back with `?jmc_enquiry_result=<status>#<notice id>`, which the block shows as a notice.

### Checks and outcomes

`jmc_submit_enquiry()` checks the nonce, then `jmc_handle_enquiry()` applies the remaining checks in this order:

1. **Rate limit** — 10 submissions an hour per visitor, counted in a transient keyed by a hash of the IP address.
2. **Spam** — the hidden honeypot is filled, the signed timing token is missing or altered, the form was submitted within 3 seconds or more than 2 days after it was rendered, or the message has more than 2 links.
3. **Validation.**
4. **Duplicates** — the same email address and message within 24 hours.

Spam and duplicates are reported to the visitor as sent, but nothing is stored or emailed. Every submission fires `jmc_enquiry_outcome` with one of `sent`, `delivery_failed`, `duplicate`, `spam`, `rate_limited`, `invalid`, `store_failed` or `expired`. The hook carries no personal data, for monitoring (#81). Behind a proxy or CDN, filter `jmc_enquiry_client_ip` with the visitor's real address, or every visitor shares one rate limit. The production setup is tracked in #125.

### Storage and delivery

Each accepted enquiry is saved as a private `jmc_enquiry` post before it is emailed (`inc/enquiries.php`), so a failed email is never lost:

- **Delivery** — `jmc_send_enquiry()` emails the business, with `Reply-To` set to the enquirer, and records the status (`sent` or `failed`), the number of attempts and the last mail error. `sent` means the mail provider accepted the message; use an SMTP plugin for reliable delivery. A failure also writes a log line containing only the enquiry ID, and fires `jmc_enquiry_delivery`.
- **Admin** — **Enquiries** lists name, email, postcode, service, delivery status and date, with a **Failed to send** filter. Each enquiry opens as a read-only view with **Reply by email**, **Resend** and **Move to bin**. Editors and administrators can see enquiries; nobody can create them by hand.
- **Recovering a failed delivery** — a notice on the dashboard and the Enquiries screens counts failed enquiries. Fix the business email address (**Settings → General**) or the site's mail settings, then use **Resend**.
- **Retention** — a daily cron job (`jmc_purge_enquiries`) deletes enquiries older than 365 days; filter `jmc_enquiry_retention_days` to change this. Switching theme unschedules it.
- **Privacy** — suggested privacy-policy text is added under **Settings → Privacy**. Enquiries are included in **Tools → Export Personal Data** and **Erase Personal Data**, matched by email address.

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
  <!-- wp:jmc/footer-business {"lock":{"remove":true}} /-->
  <!-- wp:jmc/footer-navigation /-->
  <!-- wp:jmc/footer-legal {"lock":{"remove":true}} /-->
<!-- /wp:jmc/footer -->
```

### Footer container

`jmc/footer` paints its palette across the full width and centres its content in a `--jmc-container-xl` container. Each child block is one column: stacked on narrow screens, then side by side from the `$mobile` container width, at least `12rem` wide, sharing the width equally (so fewer columns spread further apart) and wrapping as space runs out. The legal row is the exception: it always takes a full-width row along the bottom. The layout is a wrapping flexbox rather than an `auto-fit` grid, because a full-width item stops `auto-fit` from collapsing empty tracks and squeezes the columns to the left. The footer sets the smaller `p4` type style, and every footer link shares one hover and keyboard-focus treatment (highlight colour and underline). It renders nothing when its inner blocks produce no markup.

Its palette follows the Hero contract: `default` (no class), `secondary` (`jmc-palette--secondary`), and `inverse` (`jmc-palette--inverse`). The attribute default is `default`; the footer part sets `inverse`. Unknown or malformed values fall back to `default`.

### Business details

`jmc/footer-business` is the source of truth for the business details shown in the footer. Phone and Email buttons use the separate [business contact settings](#business-contact-settings) instead; #115 tracks moving the footer onto them. Edit them in the Site Editor (**Appearance → Editor → Patterns → Template parts → Site footer**), directly on the block. Each field is optional and stored as a string attribute:

| Attribute | Output | Icon | Validation |
| --- | --- | --- | --- |
| `companyName` | Bold name linking to `home_url( '/' )` | None | — |
| `address` | Inside `<address>`; Shift+Enter line breaks become `<br>` | `core/map-marker` | — |
| `phone` | Inside `<address>`; a `tel:` link when valid | `jmc/phone` | `jmc_safe_link_url( 'tel:' … )`; an unusable number is shown without a link |
| `email` | Inside `<address>`; a `mailto:` link | `core/envelope` | `FILTER_VALIDATE_EMAIL`; an invalid address is hidden |
| `serviceArea` | Introduced by a bold, translatable "Areas covered:" label | `core/home` | — |

Values are saved by formatless `RichText`, so render code converts them with `jmc_plain_text()` (`inc/helpers/text.php`): line breaks become newlines, other markup is removed, entities are decoded once, and blank lines are dropped. Output is escaped afterwards. Empty fields are omitted, `<address>` is omitted when there is no address, phone, or email, and the block renders nothing when every field is empty.

### Footer icons

Footer icons come from the core icon registry through `wp_get_icon()`; see [Icons](#icons). They are decorative (`aria-hidden`), because each line's text already says what it is. Core's library has no telephone handset, so the theme registers `jmc/phone`, the WordPress Dashicons handset. Footer icons are filled, so their styles set `fill: currentcolor`, and they use `--jmc-text-highlight`.

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
| [Lucide](https://lucide.dev) | Listed in `src/icons/lucide.json` | Service icons, the service card arrow |
| WordPress Dashicons | `jmc/phone` | Footer business details (#106 tracks moving the footer to Lucide) |

Icons are decorative (`aria-hidden`) unless they carry meaning that the surrounding text does not.

### Lucide icons

Lucide is free to use under the ISC licence (some icons, derived from Feather, under MIT). It is a development dependency, `lucide-static`, and only the icons listed in `src/icons/lucide.json` ship with the theme. `npm run build:icons`, which runs as part of `npm run build`, executes `scripts/icons/build.js`. For each listed icon it:

1. reads the icon's shapes from `lucide-static/icon-nodes.json`;
2. converts `rect`, `circle`, `ellipse`, `line`, `polyline`, and `polygon` to equivalent paths (`scripts/icons/shapes.js`), because the registry's sanitiser keeps only `svg`, `path`, and `polygon` and drops stroke attributes;
3. writes `build/assets/icons/lucide/<name>.svg`.

It also writes `manifest.json` and copies Lucide's `LICENSE` into that directory, so the licence ships with the packaged theme. `inc/icons.php` registers each manifest entry as `jmc/<name>`. The build fails if a listed name does not exist in Lucide or uses a shape the converter does not support.

To add an icon, find its name on lucide.dev, add `"<name>": "<Label>"` to `src/icons/lucide.json`, and run `npm run build`. It then appears in the Service icon picker. If it is an interface icon rather than a service icon, also add it to `INTERFACE_ICONS` in `src/editor/service-icon/utils.ts`. To update Lucide, bump `lucide-static`, rebuild, and check the icons in use.

### Styling icons

Lucide icons are outlines: `svg.lucide` in `src/scss/components/_icons.scss` sets `fill: none` and a `currentcolor` stroke (width 2, round caps and joins), so they follow `color`. Filled icons (core icons and `jmc/phone`) lose their `fill` attribute in the registry, so their styles must set `fill: currentcolor`.

Two WordPress styles sit outside the theme's cascade layers and therefore beat any layered rule, whatever its specificity:

- The core Icon block sets `.wp-block-icon svg { fill: currentColor }`. An unlayered `.wp-block-icon svg.lucide { fill: none }` keeps Lucide icons as outlines.
- Global styles set `:root :where(.wp-block-icon svg) { width: 24px }`. To size an icon rendered through the Icon block, target its SVG with an unlayered rule that is more specific, as `.jmc-service-card__icon svg` does.

## Button sizes

`.jmc-button` is the default size. Add `.jmc-button--small` for dense UI such as the header; it changes only padding, type size, and border width, never colours, so it combines with `.jmc-button__secondary` and every palette.

Keyboard focus (`:focus-visible`) uses the hover colours plus a 2px `--jmc-text-highlight` outline offset by 2px. Every button colour pair meets WCAG AA in the default, secondary, and inverse palettes (lowest text pair 8.15:1), and the focus outline is at least 3:1 against each palette background (lowest 3.65:1, default palette).

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
- Remove or implement the `order` attribute declared by `jmc/service-grid`; it has no effect, and the editor looks it up in the palette class map.
