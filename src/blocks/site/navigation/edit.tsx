import { __, sprintf } from '@wordpress/i18n';
import { parse } from '@wordpress/block-serialization-default-parser';
import { useEntityRecord, useEntityRecords } from '@wordpress/core-data';
import { Spinner } from '@wordpress/components';
import { useEffect, useId, useMemo } from '@wordpress/element';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import bindFields from '../../../utils/bindFields';
import navigationTree, {
	type NavigationItem,
} from '../../../utils/navigationTree';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

// The call to action renders as the last menu item, so it sits in the mobile
// dropdown and at the end of the desktop menu.
const ALLOWED_BLOCKS = [ 'jmc/header-cta' ];

const TEMPLATE = [ [ 'jmc/header-cta', { lock: { remove: true } } ] ];

type NavigationAttributes = {
	navigationId?: number | string;
};

type EditProps = {
	attributes: NavigationAttributes;
	setAttributes: ( attributes: Partial< NavigationAttributes > ) => void;
};

type NavigationRecord = {
	content?: { raw?: string };
};

/**
 * Make the menu preview inert, so its links and toggles select the block
 * instead of leaving the editor. Set through a ref because React 18 and 19
 * disagree on the `inert` prop's value.
 *
 * @param element The element to disable.
 */
const inert = ( element: HTMLElement | null ): void => {
	element?.setAttribute( 'inert', '' );
};

type MenuItemProps = {
	item: NavigationItem;
};

/**
 * One primary item, with the same markup `render.php` prints once `view.ts`
 * has run: submenus closed behind their toggle.
 *
 * @param props      Component props.
 * @param props.item The navigation item.
 */
function MenuItem( { item }: MenuItemProps ) {
	const submenuId = `jmc-navigation__submenu-${ useId() }`;
	const hasSubmenu = item.children.length > 0;

	return (
		<li
			ref={ inert }
			className={ [
				'jmc-navigation__item',
				hasSubmenu && 'jmc-navigation__item--has-submenu',
			]
				.filter( Boolean )
				.join( ' ' ) }
		>
			{ item.url !== '' ? (
				<a href={ item.url } className="jmc-navigation__link">
					{ item.title }
				</a>
			) : (
				<span className="jmc-navigation__label">{ item.title }</span>
			) }

			{ hasSubmenu && (
				<>
					<button
						type="button"
						className={ [
							'jmc-navigation__submenu-toggle',
							item.url === '' &&
								'jmc-navigation__submenu-toggle--label',
						]
							.filter( Boolean )
							.join( ' ' ) }
						aria-expanded="false"
						aria-controls={ submenuId }
					>
						{ item.url === '' ? (
							item.title
						) : (
							<span className="sro">
								{ sprintf(
									/* translators: %s: Parent navigation item label. */
									__( '%s submenu', 'jmc-theme' ),
									item.title
								) }
							</span>
						) }
						<span
							className="jmc-navigation__submenu-icon"
							aria-hidden="true"
						/>
					</button>
					<ul
						id={ submenuId }
						className="jmc-navigation__submenu"
						hidden
					>
						{ item.children.map( ( child, index ) => (
							<li
								key={ index }
								className="jmc-navigation__submenu-item"
							>
								<a
									href={ child.url }
									className="jmc-navigation__submenu-link"
								>
									{ child.title }
								</a>
							</li>
						) ) }
					</ul>
				</>
			) }
		</li>
	);
}

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const bind = bindFields( attributes, setAttributes );

	const { navigationId } = attributes;

	const listId = `jmc-navigation__list-${ useId() }`;

	// The block's own element is the `<nav>`, as on the frontend, so the
	// header lays it out with the same rules in both places.
	const blockProps = useBlockProps( {
		className: 'jmc-navigation',
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'jmc-navigation__item jmc-navigation__item--cta' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			renderAppender: false,
		}
	);

	const { records: records = [], isResolving } = useEntityRecords(
		'postType',
		'wp_navigation',
		{ per_page: 100, status: 'publish' }
	);

	const menus = useMemo( () => records ?? [], [ records ] );

	const { record: menu, isResolving: isResolvingMenu } = useEntityRecord(
		'postType',
		'wp_navigation',
		navigationId,
		{ enabled: !! navigationId }
	) as { record?: NavigationRecord; isResolving: boolean };

	// Parsed as `render.php` does, from the saved menu.
	const items = useMemo(
		() => navigationTree( parse( menu?.content?.raw ?? '' ) ),
		[ menu?.content?.raw ]
	);

	useEffect( () => {
		if ( isResolving || navigationId || 0 === menus.length ) {
			return;
		}

		setAttributes( { navigationId: menus[ 0 ].id } );
	}, [ navigationId, isResolving, menus, setAttributes ] );

	let navSelect = <Spinner />;

	if ( ! isResolving ) {
		const navigationOptions = menus.map( ( item ) => {
			return { label: item.title.rendered, value: item.id };
		} );

		if ( navigationOptions.length === 0 ) {
			navigationOptions.push( {
				label: __( 'No navigation menus found', 'jmc-theme' ),
				value: '',
			} );
		}

		navSelect = (
			<SelectInput
				{ ...bind.select( 'navigationId' ) }
				type="navigation"
				options={ navigationOptions }
			/>
		);
	}

	return (
		<>
			<Sidebar>
				<Sidebar.Section
					title={ __( 'Navigation Settings', 'jmc-theme' ) }
				>
					{ navSelect }
				</Sidebar.Section>
			</Sidebar>

			{ /* The JavaScript-enhanced state: no `no-js`, the menu toggle
			   shown on mobile and submenus closed on desktop. */ }
			<nav { ...blockProps }>
				<button
					ref={ inert }
					type="button"
					className="jmc-navigation__toggle"
					aria-expanded="false"
					aria-controls={ listId }
				>
					<span
						className="jmc-navigation__toggle-open"
						aria-hidden="true"
					>
						{ __( 'menu', 'jmc-theme' ) }
					</span>
					<span
						className="jmc-navigation__toggle-close"
						aria-hidden="true"
					>
						{ __( 'close', 'jmc-theme' ) }
					</span>
					<span className="sro">
						{ __( 'Toggle navigation menu', 'jmc-theme' ) }
					</span>
				</button>
				<ul id={ listId } className="jmc-navigation__list">
					{ isResolvingMenu && (
						<li className="jmc-navigation__item">
							<Spinner />
						</li>
					) }
					{ items.map( ( item, index ) => (
						<MenuItem key={ index } item={ item } />
					) ) }
					<li { ...innerBlocksProps } />
				</ul>
			</nav>
		</>
	);
}
