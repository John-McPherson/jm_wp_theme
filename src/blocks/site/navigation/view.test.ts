/**
 * @jest-environment jsdom
 */

import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	jest,
} from '@jest/globals';

import { MainNavigation } from './view';

const renderNavigation = (): void => {
	document.body.innerHTML = `
		<nav class="jmc-navigation no-js">
			<button class="jmc-navigation__toggle" aria-expanded="false"></button>
			<ul class="jmc-navigation__list"><li><a href="/">Home</a></li></ul>
		</nav>
	`;
};

describe( 'MainNavigation', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		renderNavigation();
		Object.defineProperty( window, 'matchMedia', {
			writable: true,
			value: jest.fn().mockReturnValue( { matches: false } ),
		} );
	} );

	afterEach( () => {
		jest.useRealTimers();
		document.body.innerHTML = '';
	} );

	it( 'initialises a closed enhanced mobile menu', () => {
		new MainNavigation().init();
		jest.runAllTimers();

		const navigation = document.querySelector( '.jmc-navigation' );
		const button = document.querySelector< HTMLButtonElement >( '.jmc-navigation__toggle' );
		const list = document.querySelector< HTMLUListElement >( '.jmc-navigation__list' );

		expect( navigation?.classList.contains( 'no-js' ) ).toBe( false );
		expect( button?.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( list?.hidden ).toBe( true );
	} );

	it( 'opens, closes on Escape, and restores focus to the toggle', () => {
		new MainNavigation().init();
		jest.runAllTimers();

		const button = document.querySelector< HTMLButtonElement >( '.jmc-navigation__toggle' );
		const list = document.querySelector< HTMLUListElement >( '.jmc-navigation__list' );

		button?.click();

		expect( button?.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( list?.hidden ).toBe( false );

		document.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape' } ) );

		expect( document.activeElement ).toBe( button );
		expect( button?.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( list?.hidden ).toBe( true );
	} );

	it( 'does nothing when required markup is absent', () => {
		document.body.innerHTML = '';

		expect( () => new MainNavigation().init() ).not.toThrow();
	} );
} );
