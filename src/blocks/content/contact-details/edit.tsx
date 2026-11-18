import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled, ToggleControl } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';

import Sidebar from '../../../components/Sidebar';

import ContactSettings from './settings';

type Attributes = {
	showPhone: boolean;
	showWhatsApp: boolean;
	showEmail: boolean;
	showHours: boolean;
	showServiceArea: boolean;
	className?: string;
};

type Props = {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
};

// The one core-data selector used here; the store's own types don't expose it.
type CoreSelectors = {
	getEntityRecord: (
		kind: string,
		name: string
	) => Record< string, unknown > | undefined;
};

// Site settings the preview renders from.
const CONTACT_OPTIONS = [
	'jmc_business_phone',
	'jmc_business_whatsapp',
	'jmc_business_email',
	'jmc_business_hours',
	'jmc_business_service_area',
];

type Field =
	| 'showPhone'
	| 'showWhatsApp'
	| 'showEmail'
	| 'showHours'
	| 'showServiceArea';

const FIELDS: { key: Field; label: string }[] = [
	{ key: 'showPhone', label: __( 'Phone', 'jmc-theme' ) },
	{ key: 'showWhatsApp', label: __( 'WhatsApp', 'jmc-theme' ) },
	{ key: 'showEmail', label: __( 'Email', 'jmc-theme' ) },
	{ key: 'showHours', label: __( 'Opening hours', 'jmc-theme' ) },
	{ key: 'showServiceArea', label: __( 'Areas covered', 'jmc-theme' ) },
];

const EmptyDetails = () => (
	<p className="jmc-contact-details__empty">
		{ __(
			'No contact details to show. Add them in Settings → General, or switch on a field in the block settings.',
			'jmc-theme'
		) }
	</p>
);

export default function Edit( { attributes, setAttributes }: Props ) {
	// render.php supplies the .jmc-contact-details wrapper inside the preview.
	const blockProps = useBlockProps();

	// The saved (not draft) settings: re-render the preview once they're saved.
	const savedDetails = useSelect( ( select ) => {
		const { getEntityRecord } = select(
			coreStore
		) as unknown as CoreSelectors;
		const site = getEntityRecord( 'root', 'site' );

		return JSON.stringify(
			CONTACT_OPTIONS.map( ( option ) => site?.[ option ] )
		);
	}, [] );

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Contact details', 'jmc-theme' ) }>
					<p>
						{ __(
							'Details come from Settings → General, so they stay the same everywhere. Choose which to show here.',
							'jmc-theme'
						) }
					</p>
					{ FIELDS.map( ( { key, label } ) => (
						<ToggleControl
							key={ key }
							label={ label }
							checked={ attributes[ key ] }
							onChange={ ( checked: boolean ) =>
								setAttributes( { [ key ]: checked } )
							}
							__nextHasNoMarginBottom
						/>
					) ) }
				</Sidebar.Section>
				<ContactSettings />
			</Sidebar>

			<div { ...blockProps }>
				{ /*
				 * Preview render.php so the details always match the settings.
				 * Disabled keeps its phone, email and WhatsApp links from
				 * opening apps or leaving the editor when clicked.
				 */ }
				<Disabled>
					<ServerSideRender
						key={ savedDetails }
						block="jmc/contact-details"
						attributes={ attributes }
						EmptyResponsePlaceholder={ EmptyDetails }
					/>
				</Disabled>
			</div>
		</>
	);
}
