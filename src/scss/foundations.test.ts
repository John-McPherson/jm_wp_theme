/**
 * Accessibility foundations in the compiled stylesheets: link underlines,
 * list markers, the focus ring and reduced motion (#59).
 *
 * Each stylesheet is compiled with Sass and parsed by the browser CSSOM, so
 * the checks read the rules as a browser would, layers and media included.
 */
import { resolve } from 'node:path';
import { compile } from 'sass';
import { beforeAll, describe, expect, it } from 'vitest';

const root = resolve( __dirname, '../..' );

type Rule = {
	selector: string;
	style: CSSStyleDeclaration;
	media: string[];
	layer: string | null;
};

/**
 * Compile a stylesheet and flatten its style rules, with the media queries
 * and layer each one sits in.
 *
 * @param path Stylesheet path relative to the theme root.
 */
function rules( path: string ): Rule[] {
	const { css } = compile( resolve( root, path ), {
		loadPaths: [ resolve( root, 'src/scss' ) ],
	} );

	const style = document.createElement( 'style' );
	style.textContent = css;
	document.head.append( style );

	const flat: Rule[] = [];

	const walk = (
		list: CSSRuleList,
		media: string[],
		layer: string | null
	) => {
		for ( const rule of Array.from( list ) ) {
			if ( rule instanceof CSSStyleRule ) {
				flat.push( {
					selector: rule.selectorText,
					style: rule.style,
					media,
					layer,
				} );
			} else if ( rule instanceof CSSMediaRule ) {
				walk(
					rule.cssRules,
					[ ...media, rule.media.mediaText ],
					layer
				);
			} else if ( 'cssRules' in rule ) {
				// `@layer blocks { … }`: browsers name the layer `name`, the
				// jsdom CSSOM `layerName`. Other grouping rules keep the layer.
				const { name, layerName } = rule as CSSRule & {
					name?: string;
					layerName?: string;
				};
				walk(
					( rule as CSSGroupingRule ).cssRules,
					media,
					name ?? layerName ?? layer
				);
			}
		}
	};

	walk( style.sheet.cssRules, [], null );
	style.remove();

	return flat;
}

/**
 * The selectors in a selector list, trimmed.
 *
 * @param selector Selector list.
 */
const selectors = ( selector: string ): string[] =>
	selector.split( ',' ).map( ( part ) => part.trim() );

const isReduce = ( media: string[] ) =>
	media.some( ( query ) => /prefers-reduced-motion:\s*reduce/.test( query ) );

describe( 'global stylesheet foundations', () => {
	let theme: Rule[];

	beforeAll( () => {
		theme = rules( 'src/scss/style.scss' );
	} );

	it( 'underlines links in every interaction state', () => {
		const link = theme.find(
			( rule ) => rule.selector === 'a' && rule.layer === 'base'
		);

		expect( link?.style.getPropertyValue( 'text-decoration-line' ) ).toBe(
			'underline'
		);

		// No global rule turns the underline off on hover or focus.
		const removed = theme.filter(
			( rule ) =>
				selectors( rule.selector ).some( ( part ) =>
					/^a(:[a-z-]+)*$/.test( part )
				) &&
				/^none\b/.test(
					rule.style.getPropertyValue( 'text-decoration' ) ||
						rule.style.getPropertyValue( 'text-decoration-line' )
				)
		);

		expect( removed ).toEqual( [] );
	} );

	it( 'keeps content list markers', () => {
		const reset = theme.filter(
			( rule ) =>
				selectors( rule.selector ).some( ( part ) =>
					/^(ul|ol|li)$/.test( part )
				) &&
				( rule.style.getPropertyValue( 'list-style' ) === 'none' ||
					rule.style.getPropertyValue( 'list-style-type' ) ===
						'none' )
		);

		expect( reset ).toEqual( [] );
	} );

	it( 'draws one focus ring in the section highlight colour', () => {
		const focus = theme.find(
			( rule ) => rule.selector === ':focus-visible'
		);

		expect( focus?.layer ).toBe( 'base' );
		expect( focus?.style.getPropertyValue( 'outline' ) ).toContain(
			'var(--jmc-text-highlight)'
		);
		expect( focus?.style.getPropertyValue( 'outline' ) ).toContain(
			'var(--jmc-focus-width)'
		);
		expect( focus?.style.getPropertyValue( 'outline-offset' ) ).toBe(
			'var(--jmc-focus-offset)'
		);
	} );

	it( 'only scrolls smoothly when motion is not reduced', () => {
		const smooth = theme.filter(
			( rule ) =>
				rule.style.getPropertyValue( 'scroll-behavior' ) === 'smooth'
		);

		expect( smooth.length ).toBeGreaterThan( 0 );

		for ( const rule of smooth ) {
			expect( rule.media ).toContainEqual(
				expect.stringMatching(
					/prefers-reduced-motion:\s*no-preference/
				)
			);
		}
	} );

	it( 'switches off transitions, animations and smooth scrolling for reduced motion', () => {
		const reduce = theme.find(
			( rule ) =>
				isReduce( rule.media ) &&
				selectors( rule.selector ).includes( '*' )
		);

		expect( reduce?.layer ).toBe( 'reset' );

		for ( const property of [
			'transition-duration',
			'transition-delay',
			'animation-duration',
			'animation-delay',
			'animation-iteration-count',
			'scroll-behavior',
		] ) {
			expect( reduce?.style.getPropertyPriority( property ) ).toBe(
				'important'
			);
		}

		expect( reduce?.style.getPropertyValue( 'scroll-behavior' ) ).toBe(
			'auto'
		);
	} );
} );

/*
 * Lists that are navigation or layout, not content, remove their own markers.
 * Add a list here when a block renders a new unmarked `<ul>` or `<ol>`.
 */
const componentLists: Array< [ string, string ] > = [
	[ 'src/blocks/site/navigation/style.scss', '.jmc-navigation__list' ],
	[ 'src/blocks/site/navigation/style.scss', '.jmc-navigation__submenu' ],
	[
		'src/blocks/site/footer-navigation/style.scss',
		'.jmc-footer-navigation__list',
	],
	[ 'src/blocks/site/footer-legal/style.scss', '.jmc-footer-legal__list' ],
	[ 'src/blocks/content/service-list/style.scss', '.jmc-service-list' ],
	[ 'src/blocks/content/credential-list/style.scss', '.jmc-credential-list' ],
	[
		'src/blocks/content/contact-details/style.scss',
		'.jmc-contact-details__hours',
	],
	[ 'src/blocks/content/list/style.scss', '.jmc-list--icon' ],
];

describe( 'component list resets', () => {
	it.each( componentLists )(
		'%s removes the markers from %s',
		( path, selector ) => {
			const reset = rules( path ).find(
				( rule ) =>
					selectors( rule.selector ).some( ( part ) =>
						part.endsWith( selector )
					) && rule.style.getPropertyValue( 'list-style' ) === 'none'
			);

			// Set in the block's own cascade layer, not left to a global reset.
			expect( reset?.layer ).toEqual( expect.any( String ) );
		}
	);

	it( 'keeps bullets and numbers on content lists', () => {
		const list = rules( 'src/blocks/content/list/style.scss' );
		const style = ( selector: string ) =>
			list.find(
				( rule ) =>
					selectors( rule.selector ).includes( selector ) &&
					rule.style.getPropertyValue( 'list-style' ) !== ''
			)?.style;

		expect(
			style( '.jmc-list--bullet' )?.getPropertyValue( 'list-style' )
		).toBe( 'disc' );
		expect(
			style( '.jmc-list--number' )?.getPropertyValue( 'list-style' )
		).toBe( 'decimal' );
	} );
} );

describe( 'component link decoration', () => {
	it( 'buttons opt out of the default link underline', () => {
		const button = rules( 'src/blocks/content/button/style.scss' ).find(
			( rule ) =>
				rule.selector === '.jmc-button' &&
				rule.style.getPropertyValue( 'text-decoration' ) === 'none'
		);

		expect( button?.layer ).toBe( 'components' );
	} );
} );
