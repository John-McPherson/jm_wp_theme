import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, SearchControl, Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';

import {
	filterIconOptions,
	isServicePage,
	SERVICE_ICON_COLLECTION,
	toIconOptions,
	type IconOption,
	type ServicesSettings,
} from './utils';

import './editor.scss';

type Props = {
	settings: ServicesSettings;
};

type IconSvgProps = {
	content: string;
};

// Markup comes from the core icon registry, which validates SVG on registration.
const IconSvg = ( { content }: IconSvgProps ) => (
	<span
		className="jmc-service-icon__svg"
		aria-hidden="true"
		dangerouslySetInnerHTML={ { __html: content } }
	/>
);

export default function ServiceIconPanel( { settings }: Props ) {
	const { pageId, metaKey } = settings;

	const { postType, parentId, icon } = useSelect(
		( select ) => {
			const editor = select( editorStore );
			const meta = editor.getEditedPostAttribute( 'meta' ) ?? {};

			return {
				postType: editor.getCurrentPostType(),
				parentId: editor.getEditedPostAttribute( 'parent' ),
				icon:
					typeof meta[ metaKey ] === 'string' ? meta[ metaKey ] : '',
			};
		},
		[ metaKey ]
	);

	const { editPost } = useDispatch( editorStore );
	const isService = isServicePage( postType, parentId, pageId );

	const [ options, setOptions ] = useState< IconOption[] >( [] );
	const [ status, setStatus ] = useState< 'idle' | 'loading' | 'error' >(
		'idle'
	);
	const [ search, setSearch ] = useState( '' );

	useEffect( () => {
		if ( ! isService || status !== 'idle' ) {
			return;
		}

		setStatus( 'loading' );

		apiFetch( { path: `/wp/v2/icons/${ SERVICE_ICON_COLLECTION }` } )
			.then( ( response ) => {
				setOptions( toIconOptions( response ) );
				setStatus( 'idle' );
			} )
			.catch( () => setStatus( 'error' ) );
		// Fetch once, the first time the page is identified as a service.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ isService ] );

	const visible = useMemo(
		() => filterIconOptions( options, search ),
		[ options, search ]
	);

	if ( ! isService ) {
		return null;
	}

	const selected = options.find( ( option ) => option.value === icon );
	const setIcon = ( value: string ) =>
		editPost( { meta: { [ metaKey ]: value } } );

	return (
		<PluginDocumentSettingPanel
			name="jmc-service-icon"
			title={ __( 'Service icon', 'jmc-theme' ) }
			className="jmc-service-icon"
		>
			<p className="jmc-service-icon__help">
				{ __(
					'Shown on this service’s card in the Service Grid.',
					'jmc-theme'
				) }
			</p>

			{ selected && (
				<div className="jmc-service-icon__current">
					<IconSvg content={ selected.content } />
					<span>{ selected.label }</span>
					<Button
						variant="link"
						isDestructive
						onClick={ () => setIcon( '' ) }
					>
						{ __( 'Remove', 'jmc-theme' ) }
					</Button>
				</div>
			) }

			<SearchControl
				__nextHasNoMarginBottom
				label={ __( 'Search icons', 'jmc-theme' ) }
				value={ search }
				onChange={ ( value: string ) => setSearch( value ) }
			/>

			{ status === 'loading' && <Spinner /> }

			{ status === 'error' && (
				<p>{ __( 'Icons could not be loaded.', 'jmc-theme' ) }</p>
			) }

			{ status === 'idle' && visible.length === 0 && (
				<p>{ __( 'No icons match your search.', 'jmc-theme' ) }</p>
			) }

			{ visible.length > 0 && (
				<div
					className="jmc-service-icon__grid"
					role="group"
					aria-label={ __( 'Icons', 'jmc-theme' ) }
				>
					{ visible.map( ( option ) => (
						<Button
							key={ option.value }
							className="jmc-service-icon__option"
							isPressed={ option.value === icon }
							label={ option.label }
							showTooltip
							onClick={ () => setIcon( option.value ) }
						>
							{ option.content ? (
								<IconSvg content={ option.content } />
							) : (
								<span className="jmc-service-icon__fallback">
									{ option.label }
								</span>
							) }
						</Button>
					) ) }
				</div>
			) }
		</PluginDocumentSettingPanel>
	);
}
