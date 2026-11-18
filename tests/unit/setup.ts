/**
 * Browser APIs jsdom lacks, stubbed for every unit test.
 *
 * Replaces the globals the retired `@wordpress/jest-preset-default` provided.
 * Tests that need a specific result override these locally.
 */
if ( typeof window !== 'undefined' && ! window.matchMedia ) {
	Object.defineProperty( window, 'matchMedia', {
		writable: true,
		value: ( query: string ): MediaQueryList => ( {
			matches: false,
			media: query,
			onchange: null,
			addListener: () => {},
			removeListener: () => {},
			addEventListener: () => {},
			removeEventListener: () => {},
			dispatchEvent: () => false,
		} ),
	} );
}
