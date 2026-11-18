export type RegisteredIcon = {
	name: string;
	label?: string;
	content?: string;
};

export type IconOption = {
	label: string;
	value: string;
	content: string;
};

/**
 * Theme icon collection offered as service icons.
 */
export const SERVICE_ICON_COLLECTION = 'jmc';

/**
 * Theme icons used by the interface rather than as service icons: the card
 * arrow, and the footer and contact details icons.
 */
const INTERFACE_ICONS = new Set( [
	'jmc/arrow-right',
	'jmc/clock',
	'jmc/house',
	'jmc/mail',
	'jmc/map-pin',
	'jmc/phone',
	'jmc/whatsapp',
] );

export type ServicesSettings = {
	pageId: number;
	metaKey: string;
};

/**
 * Whether the edited record is a service: a page directly beneath the
 * resolved Services page.
 *
 * @param postType       Edited post type.
 * @param parentId       Edited parent page ID.
 * @param servicesPageId Resolved Services page ID (0 when unresolved).
 */
export const isServicePage = (
	postType: unknown,
	parentId: unknown,
	servicesPageId: number
): boolean =>
	postType === 'page' &&
	Number.isInteger( servicesPageId ) &&
	servicesPageId > 0 &&
	parentId === servicesPageId;

/**
 * Map registered icons to sorted, de-duplicated select options.
 *
 * @param icons Icons returned by the `/wp/v2/icons` endpoint.
 */
export const toIconOptions = ( icons: unknown ): IconOption[] => {
	if ( ! Array.isArray( icons ) ) {
		return [];
	}

	const options = new Map< string, IconOption >();

	icons.forEach( ( icon: Partial< RegisteredIcon > ) => {
		if (
			typeof icon?.name !== 'string' ||
			icon.name === '' ||
			INTERFACE_ICONS.has( icon.name )
		) {
			return;
		}

		const label =
			typeof icon.label === 'string' && icon.label.trim() !== ''
				? icon.label.trim()
				: icon.name;

		options.set( icon.name, {
			label,
			value: icon.name,
			content: typeof icon.content === 'string' ? icon.content : '',
		} );
	} );

	return [ ...options.values() ].sort( ( a, b ) =>
		a.label.localeCompare( b.label )
	);
};

/**
 * Filter icon options by a case-insensitive match on label or name.
 *
 * @param options Icon options.
 * @param search  Search term.
 */
export const filterIconOptions = (
	options: IconOption[],
	search: string
): IconOption[] => {
	const term = search.trim().toLowerCase();

	if ( term === '' ) {
		return options;
	}

	return options.filter(
		( option ) =>
			option.label.toLowerCase().includes( term ) ||
			option.value.toLowerCase().includes( term )
	);
};

/**
 * Read the services settings localised by the theme.
 *
 * @param value Candidate settings object.
 */
export const readServicesSettings = ( value: unknown ): ServicesSettings => {
	const settings = ( value ?? {} ) as Partial< ServicesSettings >;

	return {
		pageId:
			Number.isInteger( settings.pageId ) && settings.pageId > 0
				? settings.pageId
				: 0,
		metaKey:
			typeof settings.metaKey === 'string' && settings.metaKey !== ''
				? settings.metaKey
				: 'jmc_service_icon',
	};
};
