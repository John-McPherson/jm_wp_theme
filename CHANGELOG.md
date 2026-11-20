# Changelog

All notable changes will be documented here. The project follows semantic versioning once production releases begin.

## Unreleased

## [0.2.2] – 2026-11-20

Accessibility QA results: [docs/accessibility-qa/v0.2.2.md](docs/accessibility-qa/v0.2.2.md).

### Added

- [Manual accessibility QA checklist](docs/accessibility-qa.md): keyboard, visual, screen-reader and editor checks, the supported browser and screen-reader setups, a [results template](docs/accessibility-qa/template.md) recorded for each release, and how to report failures. [Releasing](docs/releasing.md#release-checklist) requires it for every release candidate.
- Theme favicons (`favicon.ico`, `favicon.svg`, `apple-touch-icon.png`) in the front end, admin and login screens, used only when no Site Icon is set in **Settings → General** (#189).
- The project board moves an issue to **Released** when its pull request reaches `main` through a release (#169).

### Changed

- The site logo uses the "AMC Electrical Solutions" wordmark (#189).
- **Breaking:** The block allow-list depends on the editor context (#12). The post and page editor offers `jmc/*` blocks only; the Site Editor also offers the core template, layout and navigation blocks the theme's templates and parts need. Plugin blocks are no longer allowed by default and opt in through the new `jmc_allowed_blocks` filter. See [Blocks](docs/blocks.md#allowed-blocks).
- **Breaking:** Block class names follow the `block.json` conventions (#13): `jmc-cta` becomes `jmc-cta-banner` (with `--stacked`, `--contact`, `--order-left` and `--order-right` modifiers), `jmc-footer-inner` becomes `jmc-footer__inner`, `jmc-button__secondary` becomes `jmc-button--secondary`, and the section grid custom properties are block-prefixed. Custom CSS that targets the old names stops applying; saved content needs no migration.
- The image component honours `loading` (`lazy`, `eager`, or `false` to let WordPress decide) and falls back to `large` for unknown image sizes (#14).

### Fixed

- Accessibility foundations (#59): links are underlined by default in every state; content lists keep their bullets and numbers, with navigation and component lists removing them in their own styles; one shared `:focus-visible` ring across the theme; the **Skip to content** link moves focus into `<main>`, and the fallback `index.html` template now has one; smooth scrolling, transitions and animations are switched off for `prefers-reduced-motion: reduce`.
- The Text with Image editor preview uses the image's alternative text instead of `#` (#158).
- A label paragraph that is the only paragraph in its column sits tight to the heading after it (#108).

## [0.2.1] – 2026-11-19

### Added

- `npm run i18n:pot` generates the `languages/jmc-theme.pot` translation template, and `npm run package` ships it.
- CI rejects the wrong text domain in TypeScript (ESLint) and in `style.css` and `block.json` metadata (PHPUnit).
- [Platform support](docs/support.md) policy: a versioned matrix of supported WordPress, PHP, Node, browser, device and assistive-technology versions, with upgrade, end-of-support, ownership and review rules.
- `package.json` declares `engines` (Node 24.18.0 or a later 24.x), and `.npmrc` makes npm refuse other versions.
- Block metadata is validated against the WordPress `block.json` schema, and the block contracts are documented in [Blocks](docs/blocks.md).
- GitHub Actions deploys to WP Engine: `dev` to development, `staging` to staging, and `main` to production (with approval), promoting the exact package tested on staging. See [Releasing](docs/releasing.md#deployment).

### Changed

- **Breaking:** PHP 8.3 is now the minimum (was 8.0), in `style.css` and `composer.json`. CI tests PHP 8.3 and 8.5, and the `wp-env` site runs PHP 8.3.
- CI quality checks run on pull requests only, not on every push.

### Fixed

- `style.css` declares the `jmc-theme` text domain used by the code, and the theme loads translations from `languages/`.
- The privacy policy text uses a plural form for the enquiry retention period.
- `block.json` contracts are normalised across all blocks.

## [0.2.0] – 2026-11-19

### Added

- Initial engineering documentation for architecture, development, blocks, design system, accessibility, testing, releases, contributions, and security.
- Five content/composition blocks: Button, Column, Heading, Paragraph, and Service Card.
- Four section blocks: CTA Banner, Hero, Service Grid, and Text with Image.
- Semantic palette, typography, spacing, radius, and layout foundations.
- Reusable PHP render components and typed component-argument helpers.
- Reusable React editor controls and shared design-token foundations.
- GitHub Actions quality checks for TypeScript, PHP, JavaScript, SCSS, PHPCS, and production builds.
- Consistent `jmc/` block namespaces and `jmc_` PHP global prefixes.
- A valid `jmc-section` block category and corrected category-filter shape.
- CTA Banner: a Stacked variation, one or more buttons through the new Buttons block, and omission of empty columns and empty banners.
- Phone and Email button variations that link to new site-wide business contact settings (Settings → Business).
- Buttons validate their destinations, accept `mailto:` links and bare phone numbers or email addresses, and honour the editor's open-in-new-tab setting.
- Contact Details block showing the business phone, WhatsApp, email, opening hours, and service area from site-wide settings, with field toggles, Lucide and brand icons, and in-editor editing of the settings.
- WhatsApp number, opening hours, and service area business settings (Settings → Business), alongside phone and email.
- A Settings → Business admin page (administrators only) that edits every business setting in one place: name, address, phone, WhatsApp, email, opening hours, and service area.
- CTA Banner (Contact Details) variation: a heading and text beside the contact details.
- WhatsApp Button variation with the WhatsApp logo, and optional pre-filled email subjects and bodies and WhatsApp messages, keeping line breaks.
- Simple Icons brand logos in the theme's icon collection, starting with WhatsApp.
- Site Logo block (`jmc/site-logo`): the theme's logo as an inline SVG in the header, coloured by `--jmc-logo-*` tokens that follow the surrounding palette, with Brand, Mono dark and Mono light styles.
- Hero, Service introduction and Why choose us patterns in new Heroes, Services and Trust pattern categories, with lorem ipsum placeholder copy until client copy is supplied.
- Switchable colour palettes (Appearance → Editor → Styles → Colors): the default navy and copper, Alternate palette 1 (black and red on light grey) and Alternate palette 2 (graphite and verdigris on concrete). Includes a contrast report (`npm run test:contrast`) and tests covering every palette, section palette and logo colour.
- Call to action and Contact details patterns in new Calls to action and Contact pattern categories. Service listings use the Service Grid block rather than a pattern.
- Front-page and page templates: the shared header and footer parts around one `<main>` landmark holding the page title (H1) and content. The title is omitted when the page contains a hero, whose heading is the H1.
- Services landing page and Service page templates, applied automatically to the Services page and its direct children, with breadcrumbs (Home → Services → Service) and matching starter patterns. Structured data is left to Yoast SEO.
- Home page starter pattern (`jmc/home-page`): the homepage's standard sections (hero, service introduction, Service Grid, why choose us and contact details), each locked, offered when a page is created.
- Contact (`page-contact`) and 404 templates, with a `jmc/contact-page` starter pattern that seeds the Contact page's own content (hero, enquiry form, contact details and call to action): one main landmark, a single H1, and 404 recovery through a homepage link and a call to action.
- Front-end site search is turned off: search requests get a 404 and the 404 template. Admin and editor searches still work.
- Service List: a **Parent page** setting, and a **Choose pages** toggle with an ordered page picker. The toggle is locked on when the parent has no published children, and the choice is kept if children are published later (#141).
- Service cards show a “Learn more” cue beside the arrow, with the text editable per grid (#141).
- Reusable `PageSelect` and `PageListInput` editor controls (#141).
- Primary navigation submenus: one level of submenus from the Site Editor menu. On desktop each opens as a dropdown on mouse hover or from its own disclosure button (keyboard and touch), one at a time, closing on Escape, focus leaving or a click outside; on mobile they are always expanded in the menu. Every link stays reachable without JavaScript, and deeper nesting is flattened into the submenu (#85).
- The header call-to-action uses the Button block's types (#148): a primary or secondary style, and Phone, Email and WhatsApp variations, switched like the Button block's, that read Settings → Business, with optional pre-filled email and WhatsApp messages. Existing header buttons render as before. Like other buttons, a header button's own link may now be a `mailto:` link.
- Footer navigation submenus: a top-level menu item with children renders as a group, its label an `h2` (linked when it has a URL) and its links a list labelled by that heading. Flat menus render as before, and deeper nesting is flattened into the group (#152).

### Changed

- Service grids list the published child pages of the page they are on (falling back to the Services page without a page), ready for Domestic and Commercial service categories. The editor preview shows the same pages as the site, including chosen pages (#141).
- With an even number of service cards, the Service grid's intro spans the first row at two columns so the cards pair up (#141).

- Buttons in a Buttons group stack in columns narrower than 30rem, with every button as wide as the widest, and sit side by side at their own width in wider columns.
- The Heading and Paragraph blocks list `jmc/service-grid` as an allowed ancestor, matching the intro the Services grid seeds, and each layout block's editor template now lives in `template.ts` and is checked against every block's `parent` and `ancestor` rules (#136).
- The Enquiry form no longer has an introduction paragraph; it is just the form. Introductions saved by older forms are no longer shown and are dropped the next time the page is saved.
- Primary buttons on light and secondary sections hover to `brand-800` instead of `brand-900`, a slightly softer dark that each palette can tune.
- The header uses the theme's inline SVG logo instead of the core Site Logo block's uploaded image.
- The business settings moved from Settings → General to Settings → Business; stored values are unchanged.
- The footer's business name and address are now site-wide settings, edited on Settings → Business or in the footer's Business details sidebar instead of on the block. Existing footer values are copied into the settings once.
- The opening-hours fields on the settings page line up in columns.
- Highlight text, icons and focus rings in the default section palette use `accent-600` (5.37:1 on `base-100`) instead of `accent-500` (3.65:1).
- The `error` colour is darker (#B33A3A) so error messages reach 4.5:1 on light sections, and the inverse palette uses a lightened error colour (`--jmc-error-color`) so errors stay readable on dark sections.
- Server-rendered editor previews (contact details, services list, navigation, footer navigation) are inert, so links in them no longer leave the editor.
- Development tooling upgraded to the latest `@wordpress/*` majors (`scripts` 37, `env` 12), Playwright 1.65 and PHPUnit 9.6.39, clearing every critical `npm audit` advisory; the remaining upstream-only advisories are documented in `docs/development.md`. Tooling now needs Node 22.13 or later (`.nvmrc` stays on 24).
- JavaScript unit tests run on Vitest instead of Jest.
- Breadcrumbs moved from a strip between the header and the hero into the hero (#145). On child pages the trail replaces the hero's label, starts with a home icon, and marks the current page in the highlight colour; a **Show breadcrumbs** setting (on by default) turns it off. The Services templates no longer include a breadcrumbs block.
- JavaScript unit tests cover the shared utilities, every block editor, the shared editor components and block registration, with coverage reported in CI (`npm run test:unit:coverage`). A front page E2E spec checks the hero heading, hero button link, landmarks and narrow-viewport overflow (#100).
- The Playwright E2E workflow caches the browsers' system packages instead of installing them from Ubuntu's mirrors on every run. It runs Chromium, Firefox and WebKit on pull requests into `dev` and `main`, plus the nightly run, and no longer runs on pushes (#164).

### Fixed

- The Heading block's level control is hidden for locked headings (the hero and page H1s) and shown for every other heading, offering H2–H6. It was previously shown only for locked headings (#157).
- Top-level blocks in the editor are spaced so the in-between block inserter can appear between theme sections (#149).

### Known issues

- Packaging references missing optional directories and exits non-zero.
- CI builds but does not execute or verify the release package.
- The npm audit excludes all development/build dependencies.
- `style.css` declares `jmc-custom-theme` while code and block metadata use `jmc-theme`.
- Internal content-block categories retain legacy `jm` and `jmc-blocks` values.
- Integration, visual, and automated accessibility tests are not yet established.
- Only a minimal `templates/index.html` FSE template exists.
