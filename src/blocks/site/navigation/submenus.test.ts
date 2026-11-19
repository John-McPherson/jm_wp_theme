import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { NavigationSubmenus } from './submenus';

const renderNavigation = (): HTMLElement => {
	document.body.innerHTML = `
		<nav class="jmc-navigation">
			<ul class="jmc-navigation__list">
				<li class="jmc-navigation__item"><a href="/">Home</a></li>
				<li class="jmc-navigation__item" id="services">
					<a href="/services/">Services</a>
					<button type="button" class="jmc-navigation__submenu-toggle" aria-expanded="false" aria-controls="submenu-1"></button>
					<ul id="submenu-1" class="jmc-navigation__submenu">
						<li><a href="/services/domestic/">Domestic</a></li>
					</ul>
				</li>
				<li class="jmc-navigation__item" id="about">
					<button type="button" class="jmc-navigation__submenu-toggle" aria-expanded="false" aria-controls="submenu-2">About</button>
					<ul id="submenu-2" class="jmc-navigation__submenu">
						<li><a href="/team/">Team</a></li>
					</ul>
				</li>
			</ul>
		</nav>
		<a href="/elsewhere/" id="outside">Outside</a>
	`;

	return document.querySelector< HTMLElement >( '.jmc-navigation' )!;
};

const query = ( matches: boolean ): MediaQueryList =>
	( { matches } ) as MediaQueryList;

const get = < T extends HTMLElement >( selector: string ): T =>
	document.querySelector< T >( selector )!;

const toggle = ( n: number ): HTMLButtonElement =>
	document.querySelectorAll< HTMLButtonElement >(
		'.jmc-navigation__submenu-toggle'
	)[ n ];

const list = ( n: number ): HTMLUListElement =>
	get< HTMLUListElement >( `#submenu-${ n + 1 }` );

const pointer = (
	element: HTMLElement,
	type: 'pointerenter' | 'pointerleave',
	pointerType = 'mouse'
): void => {
	const event = new Event( type );
	Object.defineProperty( event, 'pointerType', { value: pointerType } );
	element.dispatchEvent( event );
};

const expectState = ( n: number, open: boolean ): void => {
	expect( toggle( n ).getAttribute( 'aria-expanded' ) ).toBe(
		String( open )
	);
	expect( list( n ).hidden ).toBe( ! open );
};

describe( 'NavigationSubmenus on desktop', () => {
	let desktop: MediaQueryList;
	let submenus: NavigationSubmenus;

	beforeEach( () => {
		vi.useFakeTimers();
		desktop = query( true );
		submenus = new NavigationSubmenus( renderNavigation(), desktop );
		submenus.init();
	} );

	afterEach( () => {
		vi.useRealTimers();
		document.body.innerHTML = '';
	} );

	it( 'starts with every submenu closed', () => {
		expectState( 0, false );
		expectState( 1, false );
	} );

	it( 'opens and closes from the toggle, repeatedly', () => {
		toggle( 0 ).click();
		expectState( 0, true );

		toggle( 0 ).click();
		expectState( 0, false );

		toggle( 0 ).click();
		expectState( 0, true );
	} );

	it( 'keeps only one sibling open at a time', () => {
		toggle( 0 ).click();
		toggle( 1 ).click();

		expectState( 0, false );
		expectState( 1, true );
	} );

	it( 'opens on mouse hover and closes after a delay when the mouse leaves', () => {
		const item = get( '#services' );

		pointer( item, 'pointerenter' );
		expectState( 0, true );

		pointer( item, 'pointerleave' );
		vi.advanceTimersByTime( NavigationSubmenus.hoverCloseDelay - 1 );
		expectState( 0, true );

		vi.advanceTimersByTime( 1 );
		expectState( 0, false );
	} );

	it( 'stays open when the mouse returns before the delay ends', () => {
		const item = get( '#services' );

		pointer( item, 'pointerenter' );
		pointer( item, 'pointerleave' );
		vi.advanceTimersByTime( 100 );
		pointer( item, 'pointerenter' );
		vi.runAllTimers();

		expectState( 0, true );
	} );

	it( 'switches straight to the hovered sibling', () => {
		pointer( get( '#services' ), 'pointerenter' );
		pointer( get( '#services' ), 'pointerleave' );
		pointer( get( '#about' ), 'pointerenter' );

		expectState( 0, false );
		expectState( 1, true );

		vi.runAllTimers();
		expectState( 1, true );
	} );

	it( 'ignores touch and pen pointers, so a tap only toggles once', () => {
		pointer( get( '#services' ), 'pointerenter', 'touch' );
		expectState( 0, false );

		toggle( 0 ).click();
		expectState( 0, true );

		pointer( get( '#services' ), 'pointerleave', 'touch' );
		vi.runAllTimers();
		expectState( 0, true );
	} );

	it( 'keeps a toggle-opened submenu open when the mouse leaves', () => {
		toggle( 0 ).click();
		pointer( get( '#services' ), 'pointerleave' );
		vi.runAllTimers();

		expectState( 0, true );
	} );

	it( 'closes on Escape and returns focus to the toggle', () => {
		toggle( 0 ).click();
		get< HTMLAnchorElement >( '#submenu-1 a' ).focus();

		expect( submenus.closeOnEscape() ).toBe( true );
		expectState( 0, false );
		expect( toggle( 0 ).ownerDocument.activeElement ).toBe( toggle( 0 ) );
	} );

	it( 'does not steal focus on Escape when focus was elsewhere', () => {
		pointer( get( '#services' ), 'pointerenter' );
		get< HTMLAnchorElement >( '#outside' ).focus();

		expect( submenus.closeOnEscape() ).toBe( true );
		expectState( 0, false );
		expect( get( '#outside' ).ownerDocument.activeElement ).toBe(
			get( '#outside' )
		);
	} );

	it( 'reports nothing to close on Escape when all are closed', () => {
		expect( submenus.closeOnEscape() ).toBe( false );
	} );

	it( 'closes when focus leaves the item', () => {
		toggle( 0 ).click();

		get( '#services' ).dispatchEvent(
			new FocusEvent( 'focusout', {
				relatedTarget: get( '#submenu-1 a' ),
			} )
		);
		expectState( 0, true );

		get( '#services' ).dispatchEvent(
			new FocusEvent( 'focusout', { relatedTarget: get( '#outside' ) } )
		);
		expectState( 0, false );
	} );

	it( 'closes on a click outside the open item', () => {
		toggle( 0 ).click();

		get( '#submenu-1 a' ).dispatchEvent(
			new MouseEvent( 'click', { bubbles: true } )
		);
		expectState( 0, true );

		get( '#outside' ).dispatchEvent(
			new MouseEvent( 'click', { bubbles: true } )
		);
		expectState( 0, false );
	} );
} );

describe( 'NavigationSubmenus on mobile', () => {
	afterEach( () => {
		vi.useRealTimers();
		document.body.innerHTML = '';
	} );

	it( 'shows every submenu expanded and ignores the toggles', () => {
		const submenus = new NavigationSubmenus(
			renderNavigation(),
			query( false )
		);
		submenus.init();

		expectState( 0, true );
		expectState( 1, true );

		toggle( 0 ).click();
		pointer( get( '#services' ), 'pointerenter' );
		expectState( 0, true );
		expect( submenus.closeOnEscape() ).toBe( false );
	} );

	it( 'resets state when the breakpoint is crossed', () => {
		const media = { matches: true };
		const submenus = new NavigationSubmenus(
			renderNavigation(),
			media as MediaQueryList
		);
		submenus.init();

		toggle( 0 ).click();
		expectState( 0, true );

		media.matches = false;
		submenus.sync();
		expectState( 0, true );
		expectState( 1, true );

		media.matches = true;
		submenus.sync();
		expectState( 0, false );
		expectState( 1, false );
	} );
} );

describe( 'NavigationSubmenus with missing markup', () => {
	it( 'skips toggles whose submenu or item is missing', () => {
		document.body.innerHTML = `
			<nav class="jmc-navigation">
				<button class="jmc-navigation__submenu-toggle" aria-controls="missing"></button>
				<button class="jmc-navigation__submenu-toggle"></button>
			</nav>
		`;

		const submenus = new NavigationSubmenus(
			get( '.jmc-navigation' ),
			query( true )
		);

		expect( () => submenus.init() ).not.toThrow();
		expect( submenus.closeOnEscape() ).toBe( false );
		document.body.innerHTML = '';
	} );
} );
