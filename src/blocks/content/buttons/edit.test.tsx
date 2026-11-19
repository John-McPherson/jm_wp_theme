import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/blocks', () => ( {
	createBlock: ( name: string, attributes: object ) => ( {
		name,
		attributes,
	} ),
} ) );

const editor = vi.hoisted( () => ( {
	count: 0,
	hasSelectedInnerBlock: false,
	insertBlock: vi.fn(),
	select: vi.fn(),
} ) );

vi.mock( '@wordpress/data', () => ( {
	useSelect: ( callback: ( select: unknown ) => unknown ) =>
		callback( editor.select ),
	useDispatch: () => ( { insertBlock: editor.insertBlock } ),
} ) );

const innerBlocks = () =>
	JSON.parse( screen.getByTestId( 'inner-blocks' ).dataset.settings ?? '{}' );

const addButton = () => screen.queryByRole( 'button', { name: 'Add button' } );

describe( 'Buttons edit', () => {
	beforeEach( () => {
		editor.count = 2;
		editor.hasSelectedInnerBlock = false;
		editor.select.mockImplementation( () => ( {
			getBlockCount: () => editor.count,
			hasSelectedInnerBlock: () => editor.hasSelectedInnerBlock,
		} ) );
	} );

	afterEach( () => {
		cleanup();
		editor.insertBlock.mockReset();
		editor.select.mockReset();
	} );

	it( 'holds unlocked, horizontal buttons seeded with one button', () => {
		const { container } = render(
			<Edit clientId="buttons-1" isSelected={ false } />
		);

		expect( container.firstElementChild?.className ).toBe( 'jmc-buttons' );
		expect( innerBlocks() ).toEqual( {
			allowedBlocks: [ 'jmc/button' ],
			template: [ [ 'jmc/button' ] ],
			templateLock: false,
			orientation: 'horizontal',
		} );
	} );

	it( 'hides the add button while neither the block nor a button is selected', () => {
		render( <Edit clientId="buttons-1" isSelected={ false } /> );

		expect( addButton() ).toBeNull();
	} );

	it.each( [
		[ 'the block is selected', true, false ],
		[ 'one of its buttons is selected', false, true ],
	] )(
		'shows the add button when %s',
		( _label, isSelected, hasSelected ) => {
			editor.hasSelectedInnerBlock = hasSelected;

			render( <Edit clientId="buttons-1" isSelected={ isSelected } /> );

			expect( addButton() ).toBeTruthy();
		}
	);

	it.each( [
		[ 'Button', 'link', '' ],
		[ 'Phone button', 'phone', 'Call us' ],
		[ 'Email button', 'email', 'Email us' ],
		[ 'WhatsApp button', 'whatsapp', 'Message us on WhatsApp' ],
	] )(
		'adds a %s with its link type and label at the end',
		( label, linkType, text ) => {
			render( <Edit clientId="buttons-1" isSelected /> );

			fireEvent.click( addButton() as HTMLElement );
			fireEvent.click( screen.getByRole( 'menuitem', { name: label } ) );

			expect( editor.insertBlock ).toHaveBeenCalledWith(
				{ name: 'jmc/button', attributes: { linkType, text } },
				2,
				'buttons-1'
			);
		}
	);
} );
