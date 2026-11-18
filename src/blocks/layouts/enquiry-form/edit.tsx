import { __ } from '@wordpress/i18n';

import type { BlockEditProps } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import bindFields from '../../../utils/bindFields';

import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';
import TextInput from '../../../components/TextInput';

type Attributes = {
	palette: 'default' | 'secondary' | 'inverse';
	submitText: string;
};

const TEMPLATE = [ [ 'jmc/paragraph' ] ];

const ALLOWED_BLOCKS = [ 'jmc/paragraph' ];

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
};

// A static preview of the fields `render.php` outputs; the real form, with
// the Services list, is rendered on the server.
const PREVIEW_FIELDS = [
	{ label: __( 'Name', 'jmc-theme' ) },
	{ label: __( 'Email address', 'jmc-theme' ) },
	{ label: __( 'Phone number', 'jmc-theme' ), optional: true },
	{ label: __( 'Postcode', 'jmc-theme' ), optional: true, short: true },
	{ label: __( 'Service', 'jmc-theme' ), optional: true },
	{ label: __( 'Message', 'jmc-theme' ), textarea: true },
];

export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< Attributes > ) {
	const bind = bindFields( attributes, setAttributes );

	const { palette } = attributes;

	const blockProps = useBlockProps( {
		className: [
			'jmc-section',
			'jmc-enquiry-form',
			VARIANT_CLASSES[ palette ],
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'jmc-section__column jmc-enquiry-form__intro' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
		}
	);

	return (
		<>
			<Sidebar>
				<Sidebar.Section
					title={ __( 'Palette Settings', 'jmc-theme' ) }
				>
					<SelectInput
						{ ...bind.select( 'palette' ) }
						type="palette"
					/>
				</Sidebar.Section>
			</Sidebar>

			<section { ...blockProps }>
				<div className="jmc-section__container">
					<div { ...innerBlocksProps } />

					<div className="jmc-section__column jmc-enquiry-form__body">
						<div className="jmc-enquiry-form__form">
							{ PREVIEW_FIELDS.map( ( field ) => (
								<div
									className="jmc-enquiry-form__field"
									key={ field.label }
								>
									<span className="jmc-enquiry-form__label">
										{ field.label }
										{ field.optional && (
											<span className="jmc-enquiry-form__optional">
												{ ' ' }
												{ __(
													'(optional)',
													'jmc-theme'
												) }
											</span>
										) }
									</span>
									<span
										className={ [
											'jmc-enquiry-form__input',
											field.short &&
												'jmc-enquiry-form__input--short',
											field.textarea &&
												'jmc-enquiry-form__input--textarea',
										]
											.filter( Boolean )
											.join( ' ' ) }
									/>
								</div>
							) ) }
							<div className="jmc-enquiry-form__field jmc-enquiry-form__field--checkbox">
								<span className="jmc-enquiry-form__checkbox" />
								<span className="jmc-enquiry-form__label">
									{ __(
										'I agree to you using these details to reply to my enquiry.',
										'jmc-theme'
									) }
								</span>
							</div>
						</div>
						<TextInput
							{ ...bind.text( 'submitText' ) }
							tagName="span"
							className="jmc-button jmc-enquiry-form__submit"
							placeholder={ __( 'Send enquiry', 'jmc-theme' ) }
						/>
					</div>
				</div>
			</section>
		</>
	);
}
