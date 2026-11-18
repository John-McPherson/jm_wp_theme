import { ComboboxControl } from '@wordpress/components';

import usePageOptions from '../utils/usePageOptions';

type PageSelectProps = {
	value: number;
	set: ( value: number ) => void;
	label: string;
	help?: string;
	exclude?: readonly number[];
};

/**
 * Searchable picker for one published page, stored by ID (0 when cleared).
 *
 * @param props         Component props.
 * @param props.value   Selected page ID; 0 for none.
 * @param props.set     Receives the chosen page ID, or 0 when cleared.
 * @param props.label   Control label.
 * @param props.help    Help text.
 * @param props.exclude Page IDs left out of the options.
 */
const PageSelect = ( {
	value,
	set,
	label,
	help,
	exclude = [],
}: PageSelectProps ) => {
	const { options } = usePageOptions();

	return (
		<ComboboxControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			help={ help }
			value={ value > 0 ? String( value ) : null }
			options={ options.filter(
				( option ) => ! exclude.includes( Number( option.value ) )
			) }
			onChange={ ( next?: string | null ) => set( Number( next ) || 0 ) }
		/>
	);
};

export default PageSelect;
