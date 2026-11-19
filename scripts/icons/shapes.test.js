import { describe, expect, it } from 'vitest';
import { iconToSvg, nodeToPathData } from './shapes';

describe( 'nodeToPathData', () => {
	it( 'keeps path data', () => {
		expect( nodeToPathData( [ 'path', { d: 'M1 1L2 2' } ] ) ).toBe(
			'M1 1L2 2'
		);
	} );

	it( 'converts square rects', () => {
		expect(
			nodeToPathData( [
				'rect',
				{ x: '2', y: '3', width: '4', height: '5' },
			] )
		).toBe( 'M2 3h4v5h-4Z' );
	} );

	it( 'converts rounded rects with arcs', () => {
		expect(
			nodeToPathData( [
				'rect',
				{ x: '2', y: '15', width: '20', height: '4', rx: '1' },
			] )
		).toBe(
			'M3 15h18a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1Z'
		);
	} );

	it( 'clamps oversized radii', () => {
		expect(
			nodeToPathData( [
				'rect',
				{ x: '0', y: '0', width: '4', height: '2', rx: '9' },
			] )
		).toBe(
			'M2 0h0a2 1 0 0 1 2 1v0a2 1 0 0 1 -2 1h0a2 1 0 0 1 -2 -1v0a2 1 0 0 1 2 -1Z'
		);
	} );

	it( 'converts circles', () => {
		expect(
			nodeToPathData( [ 'circle', { cx: '9', cy: '9', r: '2' } ] )
		).toBe( 'M7 9a2 2 0 1 0 4 0a2 2 0 1 0 -4 0Z' );
	} );

	it( 'converts lines, polylines and polygons', () => {
		expect(
			nodeToPathData( [ 'line', { x1: '1', y1: '2', x2: '3', y2: '4' } ] )
		).toBe( 'M1 2L3 4' );
		expect(
			nodeToPathData( [ 'polyline', { points: '1 2 3 4 5 6' } ] )
		).toBe( 'M1 2L3 4L5 6' );
		expect(
			nodeToPathData( [ 'polygon', { points: '1,2 3,4 5,6' } ] )
		).toBe( 'M1 2L3 4L5 6Z' );
	} );

	it( 'rejects unsupported or malformed nodes', () => {
		expect( nodeToPathData( [ 'text', {} ] ) ).toBe( '' );
		expect( nodeToPathData( [ 'polyline', { points: '1 2 3' } ] ) ).toBe(
			''
		);
	} );
} );

describe( 'iconToSvg', () => {
	it( 'builds path-only SVG with the lucide class', () => {
		const svg = iconToSvg( 'hard-hat', [
			[ 'path', { d: 'M1 1' } ],
			[ 'circle', { cx: '2', cy: '2', r: '1' } ],
		] );

		expect( svg ).toContain( 'class="lucide lucide-hard-hat"' );
		expect( svg ).toContain( 'viewBox="0 0 24 24"' );
		expect( svg.match( /<path /g ) ).toHaveLength( 2 );
		expect( svg ).not.toMatch( /<(rect|circle|line|polyline)/ );
	} );

	it( 'fails loudly on unsupported shapes', () => {
		expect( () => iconToSvg( 'x', [ [ 'text', {} ] ] ) ).toThrow(
			'unsupported shapes'
		);
	} );
} );
