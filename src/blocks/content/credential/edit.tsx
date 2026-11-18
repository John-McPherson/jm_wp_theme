import { __ } from '@wordpress/i18n';

import {
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import { Button, Icon, Notice } from '@wordpress/components';
import { useEntityRecord } from '@wordpress/core-data';

import bindFields from '../../../utils/bindFields';

import LinkToolBar from '../../../components/LinkToolBar';
import MediaInput from '../../../components/MediaInput';
import Sidebar from '../../../components/Sidebar';

type CredentialLink = {
	url: string;
	opensInNewTab: boolean;
};

type Attributes = {
	imageId: number;
	link: CredentialLink;
	className?: string;
};

type Props = {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
};

type Attachment = {
	source_url?: string;
	alt_text?: string;
	media_details?: {
		sizes?: Record< string, { source_url?: string } >;
	};
};

const ALLOWED_TYPES = [ 'image' ];

type CredentialImageProps = {
	url: string;
	alt: string;
	href: string;
};

/**
 * The logo, wrapped in its link when one is set, as on the frontend.
 * The link does not navigate in the editor; use the toolbar to change it.
 *
 * @param props      Component props.
 * @param props.url  Image source.
 * @param props.alt  Alternative text.
 * @param props.href Link destination, or empty for no link.
 */
const CredentialImage = ( { url, alt, href }: CredentialImageProps ) => {
	const image = (
		<img className="jmc-credential__image" src={ url } alt={ alt } />
	);

	if ( '' === href ) {
		return image;
	}

	return (
		<a
			className="jmc-credential__link"
			href={ href }
			onClick={ ( event ) => event.preventDefault() }
		>
			{ image }
		</a>
	);
};

export default function Edit( { attributes, setAttributes }: Props ) {
	const bind = bindFields( attributes, setAttributes );

	const { imageId, link } = attributes;

	const { record } = useEntityRecord( 'root', 'media', imageId, {
		enabled: imageId > 0,
	} );
	const image = record as Attachment | undefined;

	const imageUrl =
		image?.media_details?.sizes?.medium?.source_url ?? image?.source_url;
	const imageAlt = image?.alt_text?.trim() ?? '';

	const blockProps = useBlockProps( {
		className: 'jmc-credential',
	} );

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Image', 'jmc-theme' ) }>
					<MediaInput { ...bind.media( 'imageId' ) } />

					{ imageId > 0 && (
						<Button
							variant="link"
							isDestructive
							onClick={ () => setAttributes( { imageId: 0 } ) }
						>
							{ __( 'Remove image', 'jmc-theme' ) }
						</Button>
					) }

					{ imageId > 0 && '' === imageAlt && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'This logo has no alternative text, so screen readers will announce its file title instead. Add alternative text in the Media Library that names the credential.',
								'jmc-theme'
							) }
						</Notice>
					) }
				</Sidebar.Section>
			</Sidebar>

			<LinkToolBar { ...bind.link( 'link' ) } />

			<li { ...blockProps }>
				{ imageId > 0 && imageUrl ? (
					<CredentialImage
						url={ imageUrl }
						alt={ imageAlt }
						href={ link?.url ?? '' }
					/>
				) : (
					// An empty tile the size of a logo; the media modal handles upload too.
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ bind.media( 'imageId' ).set }
							allowedTypes={ ALLOWED_TYPES }
							render={ ( { open } ) => (
								<button
									type="button"
									className="jmc-credential__add"
									onClick={ open }
								>
									<Icon icon="awards" />
									<span>
										{ __( 'Add logo', 'jmc-theme' ) }
									</span>
								</button>
							) }
						/>
					</MediaUploadCheck>
				) }
			</li>
		</>
	);
}
