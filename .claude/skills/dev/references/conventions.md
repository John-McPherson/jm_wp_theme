# House conventions

Patterns lifted from the existing code. Match them. Where existing code deviates (legacy
categories, `as any` in `index.ts`, an un-prefixed helper), leave it alone unless the
ticket is about it — follow the pattern below for *new* code and note the deviation as a
follow-up.

## Contents
1. Block folder layout
2. `block.json`
3. `index.ts` / `index.tsx`
4. `edit.tsx`
5. `render.php`
6. PHP components (`components/`)
7. PHP helpers (`inc/helpers/`)
8. Editor components and utils (`src/components/`, `src/utils/`)
9. View scripts (`view.ts`)
10. SCSS
11. Tests
12. Formatting and naming

---

## 1. Block folder layout

```
src/blocks/<layouts|content|site>/<name>/
├── block.json      contract (name, attributes, context, supports, assets)
├── index.ts(x)     registerBlockType
├── edit.tsx        editor UI
├── render.php      server render (all blocks are dynamic)
├── style.scss      shared frontend + editor styles
├── editor.scss     editor-only tweaks (only if needed)
├── view.ts         frontend JS (only if interaction requires it)
└── view.test.ts    Jest tests for view.ts
```

Use `index.tsx` when `save` returns JSX (`<InnerBlocks.Content />`), `index.ts` when it
returns `null`.

## 2. `block.json`

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "jmc/<name>",
  "version": "0.1.0",
  "title": "Title Case Name",
  "category": "jmc-section",
  "icon": "layout",
  "description": "One sentence.",
  "attributes": {
    "palette": {
      "type": "string",
      "enum": ["default", "secondary", "inverse"],
      "default": "default"
    },
    "imageId": { "type": "number", "default": 0 }
  },
  "example": { "attributes": { }, "innerBlocks": [ ], "viewportWidth": 1200 },
  "supports": { "anchor": true, "customClassName": true, "html": false },
  "textdomain": "jmc-theme",
  "editorScript": "file:./index.js",
  "style": "file:./style-index.css",
  "render": "file:./render.php"
}
```

- Every attribute has an explicit `type` and a valid `default`. Enumerations use `enum`.
- Store media as an ID (`imageId`), not a URL, for anything rendered on the frontend.
- Content blocks: `"supports": { "inserter": false, ... }`, and `usesContext:
  ["jmc/variant"]` when they change presentation inside a parent. Parents provide context
  with `providesContext: { "jmc/variant": "<attr>" }`.
- Add `editorStyle`/`viewScript` only when those files exist.

## 3. `index.ts`

```ts
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: () => <InnerBlocks.Content />, // or `() => null` with no inner blocks
} as any );
```

## 4. `edit.tsx`

```tsx
import { __ } from '@wordpress/i18n';

import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import bindFields from '../../../utils/bindFields';

import MediaInput from '../../../components/MediaInput';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

const TEMPLATE = [
	[ 'jmc/paragraph', { variant: 'label' } ],
	[ 'jmc/heading', { level: '2', lockLevel: true } ],
	[ 'jmc/paragraph' ],
	[ 'jmc/button' ],
];

const ALLOWED_BLOCKS = [ 'jmc/heading', 'jmc/paragraph', 'jmc/button' ];

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
};

export default function Edit( { attributes, setAttributes } ) {
	const bind = bindFields( attributes, setAttributes );

	const { palette } = attributes;

	const blockProps = useBlockProps( {
		className: [ 'jmc-section', 'jmc-<name>', VARIANT_CLASSES[ palette ] ]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'jmc-section__column' },
		{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE, templateLock: false }
	);

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Palette Settings', 'jmc-theme' ) }>
					<SelectInput { ...bind.select( 'palette' ) } type="palette" />
				</Sidebar.Section>
			</Sidebar>

			<section { ...blockProps }>
				<div className="jmc-section__container">
					<div { ...innerBlocksProps } />
				</div>
			</section>
		</>
	);
}
```

- Module-level `UPPER_CASE` constants for `TEMPLATE`, `ALLOWED_BLOCKS`, class maps.
- Wire controls through `bindFields` (`bind.text`, `bind.select`, `bind.media`,
  `bind.link`) — don't write inline `onChange={ ( v ) => setAttributes( … ) }`.
- Sidebar controls go in `<Sidebar><Sidebar.Section title=…>`; inline link editing uses
  `LinkToolBar`; text uses `TextInput`; media uses `MediaInput`; enums use `SelectInput`
  (add a new option set to its `defaultOptions` only if the ticket needs a new enum, or
  pass `options`).
- Editor markup mirrors the `render.php` structure and classes so editor and frontend
  match.
- For new code, type the props (e.g. `BlockEditProps< Attributes >`) rather than leaving
  them implicit.

## 5. `render.php`

```php
<?php
/**
 * Render the <name> block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the <name> block.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     imageId?: int|string,
 *     palette?: string
 * } $attributes
 * @var string $content Rendered inner-block content.
 */

$palette = jmc_html_allowed_value(
	value: $attributes['palette'] ?? null,
	allowed: [
		'default',
		'secondary',
		'inverse',
	],
	fallback_value: 'default'
);

$palette_classes = [
	'default'   => null,
	'secondary' => 'jmc-palette--secondary',
	'inverse'   => 'jmc-palette--inverse',
];

$image_id = jmc_args_int(
	args: $attributes,
	key: 'imageId',
	fallback_value: 0,
	min: 1
);

$section_attributes = [
	'id'      => $attributes['anchor'] ?? null,
	'classes' => [
		'jmc-section',
		'jmc-<name>',
		$palette_classes[ $palette ] ?? null,
		$attributes['className'] ?? null,
	],
];

?>

<section <?php jmc_the_attributes( $section_attributes ); ?>>
	<div class="jmc-section__container">
		<div class="jmc-section__column">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
			echo $content;
			?>
		</div>
	</div>
</section>
```

- Validate first, render last. Every attribute goes through a helper
  (`jmc_html_allowed_value`, `jmc_args_int`, `jmc_args_string`) with an explicit fallback.
- Enumerations → validated value → class map with `'default' => null`; index with
  `?? null`.
- Build wrapper attributes as an array and print with `jmc_the_attributes()`; `classes`
  accepts a nested array and drops nulls.
- Delegate reusable markup to `jmc_component( name: 'heading', args: [ … ] )` — content
  blocks are often just a validated call to a component (see `content/heading/render.php`).
- `return;` early when there's nothing meaningful to render.
- Every `phpcs:ignore` carries a `-- reason`.
- Use named arguments for theme helpers and for WordPress functions where it reads better.

## 6. PHP components (`components/<name>.php`)

```php
<?php
/**
 * Render a <thing> component with <what's configurable>.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Component arguments for the <thing> template.
 *
 * @var array{
 *     text?: string,
 *     classes?: string|string[],
 *     id?: string
 * } $args
 */

$args ??= [];

$text = jmc_args_string(
	args: $args,
	key: 'text'
);

if ( '' === $text ) {
	return;
}

$attributes = [
	'classes' => [
		'jmc-<thing>',
		$args['classes'] ?? null,
	],
	'id'      => $args['id'] ?? null,
];
?>
<div<?php jmc_the_attributes( $attributes ); ?>>
	<?php echo esc_html( $text ); ?>
</div>
```

- Inputs arrive as `$args`, documented with an array shape. Accept `classes` so callers
  can add block-specific classes — the component itself never references a block.
- Snake_case arg keys (`image_id`), unlike camelCase block attributes (`imageId`).

## 7. PHP helpers (`inc/helpers/`)

- One concern per file, grouped by subfolder (`html/attributes.php`).
- `jmc_` prefix, snake_case, full docblock with `@param` types (including array shapes)
  and `@return`.
- Typed signatures, multi-line parameters when there's more than two, `$fallback_value`
  as the name for defaults.
- Pure functions; no rendering of a specific feature. Require new files from
  `functions.php` only if they aren't already loaded there.

## 8. Editor components and utils

- `src/components/PascalCase.tsx`, default export, a `type <Name>Props` above the
  component, defaults in the destructured params.
- Controls take a `{ value, set }` pair so they plug straight into `bindFields`.
- Compound components attach sub-parts as statics (`Sidebar.Section = Section`).
- `src/utils/camelCase.ts`, default export, no React.

## 9. View scripts (`view.ts`)

```ts
export class Thing {
	private readonly root: HTMLElement | null;

	constructor() {
		this.root = document.querySelector< HTMLElement >( '.jmc-thing' );
	}

	init(): void {
		if ( ! this.root ) {
			return;
		}

		this.root.addEventListener( 'click', this.handleClick );
	}

	private handleClick = (): void => {
		// …
	};
}

new Thing().init();
```

- Only add one when interaction requires it; the block must work without JS (use a
  `no-js` class fallback when relevant).
- Export the class so Jest can test it; guard every query result; arrow-function handlers;
  keep ARIA state (`aria-expanded`, `hidden`) in sync and restore focus where expected.

## 10. SCSS (`style.scss`)

```scss
@use "../../../scss/abstracts/index" as *;

@layer blocks {

	.jmc-<name> {
		--jmc-<name>-gap: var(--jmc-padding-xl);

		&__element {
			gap: var(--jmc-<name>-gap);
		}

		&--modifier {
			// …
		}

		@include min($mobile) {
			// container-query breakpoint
		}

		@include min($tablet) {
			// …
		}
	}
}
```

- All rules inside `@layer blocks`. BEM with the `jmc-` prefix.
- Expose layout knobs as block-scoped custom properties and change the properties at
  breakpoints rather than repeating declarations.
- Use existing tokens (`--jmc-*`, `$mobile`/`$tablet`) — check `src/scss/tokens/` before
  adding a value. Palettes come from `jmc-palette--*` classes, not per-block colours.
- `min()` is a container query; `bp()` is a media query. Use `sr-only` mixin for
  visually-hidden text.
- `editor.scss` only for editor-specific fixes.

## 11. Tests

- **PHPUnit** (`tests/phpunit/Unit/<Name>RenderTest.php`): `final class … extends
  TestCase`, docblocked methods, `@dataProvider` covering omitted, default, each supported
  and malformed values; convert warnings to exceptions with `set_error_handler` as in
  `HeroRenderTest.php`.
- **Jest** (`view.test.ts` beside `view.ts`): build fixture markup in `document.body`,
  `beforeEach`/`afterEach` reset, test initial state, interactions, keyboard, and safe
  init when markup is missing.
- **Playwright** (`tests/e2e/*.spec.ts`): only for browser behaviour; restore any
  WordPress state you change.

## 12. Formatting and naming

- Tabs. WordPress spacing: `foo( $bar )`, `[ 'a', 'b' ]`, `array[ $key ]`, short array
  syntax in PHP.
- Yoda conditions in PHP (`'' === $text`, `null !== $min`).
- PHP: `jmc_` functions, snake_case variables. TS: camelCase, PascalCase components.
- CSS classes: `jmc-<block>`, `jmc-<block>__element`, `jmc-<block>--modifier`; shared
  layout classes `jmc-section`, `jmc-section__container`, `jmc-section__column`.
- Text domain `jmc-theme` everywhere. Block names `jmc/<kebab-name>`.
