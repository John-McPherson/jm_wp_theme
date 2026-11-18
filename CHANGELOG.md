# Changelog

All notable changes will be documented here. The project follows semantic versioning once production releases begin.

## Unreleased

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
- Phone and Email button variations that link to new site-wide business contact settings (Settings → General).
- Buttons validate their destinations, accept `mailto:` links and bare phone numbers or email addresses, and honour the editor's open-in-new-tab setting.
- Contact Details block showing the business phone, WhatsApp, email, opening hours, and service area from site-wide settings, with field toggles, Lucide and brand icons, and in-editor editing of the settings.
- WhatsApp number, opening hours, and service area business settings (Settings → General), alongside phone and email.
- CTA Banner (Contact Details) variation: a heading and text beside the contact details.
- WhatsApp Button variation with the WhatsApp logo, and optional pre-filled email subjects and bodies and WhatsApp messages, keeping line breaks.
- Simple Icons brand logos in the theme's icon collection, starting with WhatsApp.

### Changed

- Server-rendered editor previews (contact details, services list, navigation, footer navigation) are inert, so links in them no longer leave the editor.
- Development tooling upgraded to the latest `@wordpress/*` majors (`scripts` 37, `env` 12), Playwright 1.65 and PHPUnit 9.6.39, clearing every critical `npm audit` advisory; the remaining upstream-only advisories are documented in `docs/development.md`. Tooling now needs Node 22.13 or later (`.nvmrc` stays on 24).
- JavaScript unit tests run on Vitest instead of Jest.

### Known issues

- Packaging references missing optional directories and exits non-zero.
- CI builds but does not execute or verify the release package.
- The npm audit excludes all development/build dependencies.
- `style.css` declares `jmc-custom-theme` while code and block metadata use `jmc-theme`.
- Internal content-block categories retain legacy `jm` and `jmc-blocks` values.
- Automated unit, integration, browser, visual, and accessibility tests are not yet established.
- Only a minimal `templates/index.html` FSE template exists.
