import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

// The theme's icon collection (see inc/icons.php).
const ICON_COLLECTION = 'jmc';

export type IconContent = Record< string, string >;

/**
 * Fetch the registered SVG for each theme icon, keyed by name (`jmc/<name>`),
 * so the editor shows the same markup the frontend renders with wp_get_icon().
 */
export default function useThemeIcons(): IconContent {
	const [ icons, setIcons ] = useState< IconContent >( {} );

	useEffect( () => {
		let isMounted = true;

		apiFetch( { path: `/wp/v2/icons/${ ICON_COLLECTION }` } )
			.then( ( response ) => {
				if ( ! isMounted || ! Array.isArray( response ) ) {
					return;
				}

				const content: IconContent = {};

				response.forEach(
					( icon: { name?: unknown; content?: unknown } | null ) => {
						if (
							typeof icon?.name === 'string' &&
							typeof icon?.content === 'string'
						) {
							content[ icon.name ] = icon.content;
						}
					}
				);

				setIcons( content );
			} )
			// Icons are decorative: everything still works without them.
			.catch( () => undefined );

		return () => {
			isMounted = false;
		};
	}, [] );

	return icons;
}
