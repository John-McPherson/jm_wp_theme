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

### Known issues

- Packaging references missing optional directories and exits non-zero.
- CI builds but does not execute or verify the release package.
- The npm audit excludes all development/build dependencies.
- `style.css` declares `jmc-custom-theme` while code and block metadata use `jmc-theme`.
- Internal content-block categories retain legacy `jm` and `jmc-blocks` values.
- Automated unit, integration, browser, visual, and accessibility tests are not yet established.
- Only a minimal `templates/index.html` FSE template exists.
