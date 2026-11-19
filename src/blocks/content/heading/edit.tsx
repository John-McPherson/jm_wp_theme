import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

import TextInput from '../../../components/TextInput';
import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

const HEADING_TAGS = {
	'1': 'h1',
	'2': 'h2',
	'3': 'h3',
	'4': 'h4',
	'5': 'h5',
	'6': 'h6',
} as const;

type HeadingLevel = keyof typeof HEADING_TAGS;

// H1 belongs to the page title or the hero heading, which seeds it with
// `lockLevel`, so authors pick from H2 down.
const HEADING_LEVELS = [
	{ label: 'H2', value: '2' },
	{ label: 'H3', value: '3' },
	{ label: 'H4', value: '4' },
	{ label: 'H5', value: '5' },
	{ label: 'H6', value: '6' },
] as const;

// An existing unlocked H1 keeps its option so the control shows the real
// level; authors can move it down but not back up.
const levelOptions = ( level: string ) =>
	'1' === level
		? [ { label: 'H1', value: '1' }, ...HEADING_LEVELS ]
		: HEADING_LEVELS;

const VARIANT_CLASSES = {
	default: '',
	hero: 'jmc-hero__text-heading',
};

export default function Edit( { attributes, setAttributes, context } ) {
	const { className } = attributes;
	const variant = context[ 'jmc/variant' ] ?? 'default';

	const blockProps = useBlockProps( {
		className: [ className, 'jmc-heading', VARIANT_CLASSES[ variant ] ],
	} );
	const bind = bindFields( attributes, setAttributes );

	const { level, lockLevel } = attributes;

	const sideBar = (
		<Sidebar>
			<Sidebar.Section title={ __( 'Heading level', 'jmc-theme' ) }>
				<SelectInput
					value={ String( level ) }
					type="heading_level"
					options={ levelOptions( String( level ) ) }
					set={ ( value: string ) =>
						setAttributes( { level: value as HeadingLevel } )
					}
				/>
			</Sidebar.Section>
		</Sidebar>
	);

	return (
		<>
			{ ! lockLevel && sideBar }
			<div { ...blockProps }>
				<TextInput
					{ ...bind.text( 'text' ) }
					tagName={ HEADING_TAGS[ level ] }
				/>
			</div>
		</>
	);
}
