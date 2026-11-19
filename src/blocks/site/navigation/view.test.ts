import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

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
		vi.useFakeTimers();
		renderNavigation();
		Object.defineProperty( window, 'matchMedia', {
			writable: true,
			value: vi.fn().mockReturnValue( { matches: false } ),
		} );
	} );

	afterEach( () => {
		vi.useRealTimers();
		document.body.innerHTML = '';
	} );

	it( 'initialises a closed enhanced mobile menu', () => {
		new MainNavigation().init();
		vi.runAllTimers();

		const navigation = document.querySelector( '.jmc-navigation' );
		const button = document.querySelector< HTMLButtonElement >(
			'.jmc-navigation__toggle'
		);
		const list = document.querySelector< HTMLUListElement >(
			'.jmc-navigation__list'
		);

		expect( navigation?.classList.contains( 'no-js' ) ).toBe( false );
		expect( button?.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( list?.hidden ).toBe( true );
	} );

	it( 'opens, closes on Escape, and restores focus to the toggle', () => {
		new MainNavigation().init();
		vi.runAllTimers();

		const button = document.querySelector< HTMLButtonElement >(
			'.jmc-navigation__toggle'
		);
		const list = document.querySelector< HTMLUListElement >(
			'.jmc-navigation__list'
		);

		button?.click();

		expect( button?.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( list?.hidden ).toBe( false );

		document.dispatchEvent(
			new KeyboardEvent( 'keydown', { key: 'Escape' } )
		);

		expect( button?.ownerDocument.activeElement ).toBe( button );
		expect( button?.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( list?.hidden ).toBe( true );
	} );

	it( 'lets Escape close an open submenu before the primary menu', () => {
		document.body.innerHTML = `
			<nav class="jmc-navigation no-js">
				<button class="jmc-navigation__toggle" aria-expanded="false"></button>
				<ul class="jmc-navigation__list">
					<li>
						<a href="/services/">Services</a>
						<button class="jmc-navigation__submenu-toggle" aria-expanded="false" aria-controls="submenu-1"></button>
						<ul id="submenu-1"><li><a href="/a/">A</a></li></ul>
					</li>
				</ul>
			</nav>
		`;
		window.matchMedia = vi.fn().mockReturnValue( { matches: true } );

		new MainNavigation().init();

		const submenuToggle = document.querySelector< HTMLButtonElement >(
			'.jmc-navigation__submenu-toggle'
		);
		const submenu = document.getElementById( 'submenu-1' );

		submenuToggle?.click();
		expect( submenu?.hidden ).toBe( false );

		document.dispatchEvent(
			new KeyboardEvent( 'keydown', { key: 'Escape' } )
		);

		expect( submenu?.hidden ).toBe( true );
		expect( submenuToggle?.getAttribute( 'aria-expanded' ) ).toBe(
			'false'
		);
	} );

	it( 'shows submenus expanded inside the mobile menu', () => {
		document.body.innerHTML = `
			<nav class="jmc-navigation no-js">
				<button class="jmc-navigation__toggle" aria-expanded="false"></button>
				<ul class="jmc-navigation__list">
					<li>
						<button class="jmc-navigation__submenu-toggle" aria-expanded="false" aria-controls="submenu-1">About</button>
						<ul id="submenu-1"><li><a href="/team/">Team</a></li></ul>
					</li>
				</ul>
			</nav>
		`;

		new MainNavigation().init();

		expect( document.getElementById( 'submenu-1' )?.hidden ).toBe( false );
	} );

	it( 'does nothing when required markup is absent', () => {
		document.body.innerHTML = '';

		expect( () => new MainNavigation().init() ).not.toThrow();
	} );
} );
