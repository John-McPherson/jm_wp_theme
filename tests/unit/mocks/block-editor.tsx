/**
 * Stand-ins for `@wordpress/block-editor`, for editor component tests.
 *
 * The real components need a block editor store and registered blocks. These
 * render plain, queryable markup instead:
 *
 * - `InspectorControls` and `BlockControls` wrap their children in regions
 *   named "Settings" and "Block toolbar".
 * - `RichText` is a text box named by its placeholder, carrying its tag name
 *   and class names as data attributes, that reports typed text to `onChange`.
 * - `LinkControl` is a URL field and a "Remove link" button.
 * - `MediaUploadCheck` renders its children, and `MediaUpload` renders its
 *   `render` callback, wrapped in an element exposing the allowed types. Its
 *   `open()` selects `mediaLibrary.selection` straight away; tests may change it.
 * - `InnerBlocks.Content` is an empty marker, for blocks that save inner blocks.
 * - `useBlockProps` and `useInnerBlocksProps` return the given props, joining a
 *   class name list as core does, and inner blocks render as a marker element
 *   exposing the settings passed to them.
 *
 * Use with `vi.mock( '@wordpress/block-editor', () => import( '<path>/tests/unit/mocks/block-editor' ) )`.
 */
import type { ChangeEvent, ReactNode } from 'react';

type ChildrenProps = { children?: ReactNode };

export const store = 'core/block-editor';

export function InspectorControls( { children }: ChildrenProps ) {
	return (
		<div role="region" aria-label="Settings">
			{ children }
		</div>
	);
}

export function BlockControls( { children }: ChildrenProps ) {
	return (
		<div role="region" aria-label="Block toolbar">
			{ children }
		</div>
	);
}

type RichTextProps = {
	tagName?: string;
	value?: string;
	onChange?: ( value: string ) => void;
	placeholder?: string;
	className?: string;
};

export function RichText( {
	tagName = 'div',
	value,
	onChange,
	placeholder,
	className,
}: RichTextProps ) {
	return (
		<input
			aria-label={ placeholder }
			placeholder={ placeholder }
			value={ value ?? '' }
			data-tag-name={ tagName }
			data-class-name={ className ?? '' }
			onChange={ ( event: ChangeEvent< HTMLInputElement > ) =>
				onChange?.( event.target.value )
			}
		/>
	);
}

type LinkValue = { url: string; opensInNewTab: boolean };

type LinkControlProps = {
	value?: LinkValue;
	onChange?: ( value: LinkValue ) => void;
	onRemove?: () => void;
};

export function LinkControl( { value, onChange, onRemove }: LinkControlProps ) {
	return (
		<div>
			<input
				aria-label="URL"
				value={ value?.url ?? '' }
				onChange={ ( event: ChangeEvent< HTMLInputElement > ) =>
					onChange?.( {
						url: event.target.value,
						opensInNewTab: value?.opensInNewTab ?? false,
					} )
				}
			/>
			<button type="button" onClick={ onRemove }>
				Remove link
			</button>
		</div>
	);
}

function InnerBlocksContent() {
	return <div data-testid="inner-blocks-content" />;
}

export const InnerBlocks = { Content: InnerBlocksContent };

export const mediaLibrary = {
	selection: { id: 101, url: 'https://example.com/logo.png' },
};

export function MediaUploadCheck( { children }: ChildrenProps ) {
	return <>{ children }</>;
}

type MediaUploadProps = {
	onSelect: ( media: { id: number; url: string } ) => void;
	allowedTypes?: string[];
	render: ( props: { open: () => void } ) => ReactNode;
};

export function MediaUpload( {
	onSelect,
	allowedTypes = [],
	render,
}: MediaUploadProps ) {
	return (
		<span data-allowed-types={ allowedTypes.join( ',' ) }>
			{ render( { open: () => onSelect( mediaLibrary.selection ) } ) }
		</span>
	);
}

type ClassNameProps = { className?: unknown; [ key: string ]: unknown };

// Core passes class names through `clsx`, so blocks may give a list.
const withClassName = ( props: ClassNameProps = {} ) =>
	Array.isArray( props.className )
		? {
				...props,
				className: props.className.filter( Boolean ).join( ' ' ),
			}
		: { ...props };

export function useBlockProps( props?: ClassNameProps ) {
	return withClassName( props );
}

type InnerBlocksSettings = {
	allowedBlocks?: string[];
	template?: unknown;
	templateLock?: unknown;
	renderAppender?: false | ( () => ReactNode );
	[ key: string ]: unknown;
};

export function useInnerBlocksProps(
	props?: ClassNameProps,
	settings: InnerBlocksSettings = {}
) {
	const { renderAppender, ...rest } = settings;

	return {
		...withClassName( props ),
		children: (
			<>
				<div
					data-testid="inner-blocks"
					data-settings={ JSON.stringify( rest ) }
				/>
				{ typeof renderAppender === 'function'
					? renderAppender()
					: null }
			</>
		),
	};
}
