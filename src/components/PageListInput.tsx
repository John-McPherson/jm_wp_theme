import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { chevronDown, chevronUp, close } from '@wordpress/icons';

import moveItem from '../utils/moveItem';
import usePageOptions from '../utils/usePageOptions';
import PageSelect from './PageSelect';

type PageListInputProps = {
	value: readonly number[];
	set: ( value: number[] ) => void;
	label: string;
	help?: string;
};

/**
 * Ordered list of chosen pages, stored by ID, with controls to add, reorder
 * and remove them.
 *
 * Chosen pages that are no longer published stay listed by ID so editors can
 * see and remove them; the server skips them when rendering.
 *
 * @param props       Component props.
 * @param props.value Chosen page IDs, in display order.
 * @param props.set   Receives the updated page IDs.
 * @param props.label Label for the control that adds a page.
 * @param props.help  Help text for the control that adds a page.
 */
const PageListInput = ( { value, set, label, help }: PageListInputProps ) => {
	const { options, isResolving } = usePageOptions();

	const titleOf = ( id: number ) =>
		options.find( ( option ) => option.value === String( id ) )?.label ??
		( isResolving
			? __( 'Loading…', 'jmc-theme' )
			: sprintf(
					/* translators: %d: page ID. */
					__( 'Unavailable page #%d', 'jmc-theme' ),
					id
				) );

	return (
		<div className="jmc-page-list-input">
			{ value.length > 0 && (
				<ol className="jmc-page-list-input__items">
					{ value.map( ( id, index ) => {
						const title = titleOf( id );

						return (
							<li
								key={ id }
								className="jmc-page-list-input__item"
							>
								<span className="jmc-page-list-input__title">
									{ title }
								</span>
								<Button
									size="small"
									icon={ chevronUp }
									label={ sprintf(
										/* translators: %s: page title. */
										__( 'Move %s up', 'jmc-theme' ),
										title
									) }
									disabled={ index === 0 }
									accessibleWhenDisabled
									onClick={ () =>
										set( moveItem( value, index, -1 ) )
									}
								/>
								<Button
									size="small"
									icon={ chevronDown }
									label={ sprintf(
										/* translators: %s: page title. */
										__( 'Move %s down', 'jmc-theme' ),
										title
									) }
									disabled={ index === value.length - 1 }
									accessibleWhenDisabled
									onClick={ () =>
										set( moveItem( value, index, 1 ) )
									}
								/>
								<Button
									size="small"
									icon={ close }
									label={ sprintf(
										/* translators: %s: page title. */
										__( 'Remove %s', 'jmc-theme' ),
										title
									) }
									onClick={ () =>
										set(
											value.filter(
												( item ) => item !== id
											)
										)
									}
								/>
							</li>
						);
					} ) }
				</ol>
			) }

			<PageSelect
				value={ 0 }
				set={ ( id ) => {
					if ( id > 0 && ! value.includes( id ) ) {
						set( [ ...value, id ] );
					}
				} }
				label={ label }
				help={ help }
				exclude={ value }
			/>
		</div>
	);
};

export default PageListInput;
