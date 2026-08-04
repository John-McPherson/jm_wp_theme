# Changelog

All notable changes will be documented here. The project follows semantic versioning once production releases begin.

## Unreleased

### Added

- Initial engineering documentation for architecture, development, blocks, design system, accessibility, testing, releases, contributions, and security.
- Five content/composition blocks: Button, Column, Heading, Paragraph, and Service Card.
- Four section blocks: CTA Banner, Hero, Service Grid, and Text with Image.
- Semantic palette, typography, spacing, radius, and layout foundations.
- Reusable PHP render components and typed component-argument helpers.
- Reusable React editor controls and design-token generation tooling.

### Known issues

- The theme packaging command omits required runtime directories.
- The custom block-category filter returns an invalid structure.
- The Hero palette contract is inconsistent across layers.
- Automated testing and CI quality gates are not yet established.
- Block categories and text domains are inconsistent across current metadata.
- Only a minimal `templates/index.html` FSE template exists.
