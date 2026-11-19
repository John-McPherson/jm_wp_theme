import { __ } from '@wordpress/i18n';

// Values match the `enum`s in block.json and the allowlists in render.php.
export const LIST_STYLE_OPTIONS = [
	{ label: __( 'Icons', 'jmc-theme' ), value: 'icon' },
	{ label: __( 'Bullets', 'jmc-theme' ), value: 'bullet' },
	{ label: __( 'Numbers', 'jmc-theme' ), value: 'number' },
] as const;

// Lucide icons from the theme's `jmc` icon collection (src/icons/lucide.json).
export const LIST_ICON_OPTIONS = [
	{ label: __( 'Tick', 'jmc-theme' ), value: 'check' },
	{ label: __( 'Tick in a circle', 'jmc-theme' ), value: 'circle-check' },
	{ label: __( 'Shield with tick', 'jmc-theme' ), value: 'shield-check' },
	{ label: __( 'Plus', 'jmc-theme' ), value: 'plus' },
	{ label: __( 'Lightning bolt', 'jmc-theme' ), value: 'zap' },
	{ label: __( 'Arrow', 'jmc-theme' ), value: 'arrow-right' },
] as const;

export type ListStyle = ( typeof LIST_STYLE_OPTIONS )[ number ][ 'value' ];
export type ListIcon = ( typeof LIST_ICON_OPTIONS )[ number ][ 'value' ];

export const DEFAULT_LIST_STYLE: ListStyle = 'icon';
export const DEFAULT_LIST_ICON: ListIcon = 'check';

/**
 * The list style, or the default when the value is not a supported style.
 *
 * @param value Saved or inherited list style.
 */
export function toListStyle( value: unknown ): ListStyle {
	return (
		LIST_STYLE_OPTIONS.find( ( option ) => option.value === value )
			?.value ?? DEFAULT_LIST_STYLE
	);
}

/**
 * The registered theme icon name (`jmc/<icon>`), falling back to the default icon.
 *
 * @param value Saved or inherited icon.
 */
export function toListIconName( value: unknown ): string {
	const icon =
		LIST_ICON_OPTIONS.find( ( option ) => option.value === value )?.value ??
		DEFAULT_LIST_ICON;

	return `jmc/${ icon }`;
}

/**
 * Numbered lists are ordered; icon and bulleted lists are not.
 *
 * @param style List style.
 */
export function listTagName( style: ListStyle ): 'ol' | 'ul' {
	return 'number' === style ? 'ol' : 'ul';
}
