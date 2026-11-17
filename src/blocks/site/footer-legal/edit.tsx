import { useBlockProps } from '@wordpress/block-editor';
import { parse } from '@wordpress/blocks';
import { useEntityProp, useEntityRecords } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import TextInput from '../../../components/TextInput';

type FooterLegalAttributes = {
	copyrightHolder: string;
	className?: string;
};

type EditProps = {
	attributes: FooterLegalAttributes;
	setAttributes: ( attributes: Partial< FooterLegalAttributes > ) => void;
};

type NavigationRecord = {
	id: number;
	content: { raw: string };
};

type ParsedBlock = {
	name: string;
	attributes: Record< string, unknown >;
	innerBlocks: ParsedBlock[];
};

// The menu slug the theme registers for legal links (see inc/navigation.php).
const MENU_SLUG = 'legal';

// Mirrors the theme author's credit in render.php.
const CREDIT_NAME = 'John McPherson';

const LINK_BLOCKS = [ 'core/navigation-link', 'core/navigation-submenu' ];

/**
 * Flatten the menu's link labels in order, as get_navigation_links() does.
 *
 * @param blocks Parsed menu blocks.
 */
const getLinkLabels = ( blocks: ParsedBlock[] ): string[] =>
	blocks.flatMap( ( block ) => {
		const { label, url } = block.attributes;
		const own =
			LINK_BLOCKS.includes( block.name ) &&
			typeof label === 'string' &&
			typeof url === 'string' &&
			label.trim() &&
			url.trim()
				? [ label.trim() ]
				: [];

		return [ ...own, ...getLinkLabels( block.innerBlocks ?? [] ) ];
	} );

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps( { className: 'jmc-footer-legal' } );

	const bind = bindFields( attributes, setAttributes );

	const [ siteTitle ] = useEntityProp( 'root', 'site', 'title' );

	const { records } = useEntityRecords( 'postType', 'wp_navigation', {
		slug: MENU_SLUG,
		status: 'publish',
		context: 'edit',
	} );

	const menu = ( ( records ?? [] ) as NavigationRecord[] )[ 0 ];
	const labels = menu
		? getLinkLabels( parse( menu.content.raw ) as ParsedBlock[] )
		: [];

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Legal', 'jmc-theme' ) }>
					<p>
						{ __(
							'The copyright year updates automatically. When the copyright holder is empty, the site title is used.',
							'jmc-theme'
						) }
					</p>
					<p>
						{ __(
							'The links come from the "Legal" navigation menu. Edit it in Appearance → Editor → Navigation; the links are hidden until it has at least one.',
							'jmc-theme'
						) }
					</p>
				</Sidebar.Section>
			</Sidebar>

			<div { ...blockProps }>
				<div className="jmc-footer-legal__notices">
					<p className="jmc-footer-legal__credit">
						{ __( 'Site created by', 'jmc-theme' ) }{ ' ' }
						<span>{ CREDIT_NAME }</span>
					</p>
					<p className="jmc-footer-legal__copyright">
						{ `© ${ new Date().getFullYear() } ` }
						<TextInput
							{ ...bind.text( 'copyrightHolder' ) }
							tagName="span"
							placeholder={
								( siteTitle as string ) ||
								__( 'Copyright holder', 'jmc-theme' )
							}
						/>
					</p>
				</div>

				{ labels.length > 0 && (
					<nav
						className="jmc-footer-legal__navigation"
						aria-label={ __( 'Legal', 'jmc-theme' ) }
					>
						<ul className="jmc-footer-legal__list">
							{ labels.map( ( label, index ) => (
								<li
									key={ `${ index }-${ label }` }
									className="jmc-footer-legal__item"
								>
									<span className="jmc-footer-legal__link">
										{ label }
									</span>
								</li>
							) ) }
						</ul>
					</nav>
				) }
			</div>
		</>
	);
}
