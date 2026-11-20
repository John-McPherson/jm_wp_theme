import AxeBuilder from '@axe-core/playwright';
import type { Page } from '@playwright/test';

import {
	accessibilityExceptions,
	type AccessibilityException,
} from '../accessibility-exceptions';
import { expect, test } from '../fixtures/test';

type AxeResults = Awaited< ReturnType< AxeBuilder[ 'analyze' ] > >;
type Violation = AxeResults[ 'violations' ][ number ];
type ViolationNode = Violation[ 'nodes' ][ number ];

/** The WCAG 2.2 AA rules, plus axe's best-practice rules. */
export const AXE_TAGS = [
	'wcag2a',
	'wcag2aa',
	'wcag21a',
	'wcag21aa',
	'wcag22aa',
	'best-practice',
];

/** Impacts that fail a scan. Minor and moderate ones are reported only. */
export const BLOCKING_IMPACTS = [ 'serious', 'critical' ];

/**
 * A node's target as one selector string: frames and shadow roots are joined
 * with ` >>> `.
 *
 * @param node Violation node.
 */
export const formatTarget = ( node: ViolationNode ): string =>
	node.target
		.map( ( selector ) =>
			Array.isArray( selector ) ? selector.join( ' >>> ' ) : selector
		)
		.join( ' >>> ' );

/**
 * Whether an exception covers a node of a violation found by a scan.
 *
 * @param exception Exception.
 * @param scan      Scan name.
 * @param rule      Axe rule ID.
 * @param node      Violation node.
 */
const isExcepted = (
	exception: AccessibilityException,
	scan: string,
	rule: string,
	node: ViolationNode
): boolean =>
	exception.rule === rule &&
	exception.targets.includes( formatTarget( node ) ) &&
	( ! exception.scans || exception.scans.includes( scan ) );

/**
 * Violations left once the exceptions are applied, without nodes that an
 * exception covers.
 *
 * @param scan       Scan name.
 * @param violations Violations.
 */
const withoutExceptions = ( scan: string, violations: Violation[] ) =>
	violations
		.map( ( violation ) => ( {
			...violation,
			nodes: violation.nodes.filter(
				( node ) =>
					! accessibilityExceptions.some( ( exception ) =>
						isExcepted( exception, scan, violation.id, node )
					)
			),
		} ) )
		.filter( ( violation ) => violation.nodes.length > 0 );

/**
 * Describe violations for a failure message: the rule, its impact and help
 * page, then each failing element's selector, markup and what to fix.
 *
 * @param violations Violations.
 */
export const formatViolations = ( violations: Violation[] ): string =>
	violations
		.map( ( violation ) =>
			[
				`[${ violation.impact }] ${ violation.id }: ${ violation.help }`,
				`  Help: ${ violation.helpUrl }`,
				'  Targets:',
				...violation.nodes.flatMap( ( node ) => [
					`    - ${ formatTarget( node ) }`,
					`      ${ node.html.replace( /\s+/g, ' ' ).slice( 0, 200 ) }`,
					...( node.failureSummary ?? '' )
						.split( '\n' )
						.map( ( line ) => `      ${ line.trim() }` ),
				] ),
			].join( '\n' )
		)
		.join( '\n\n' );

/**
 * Scan the page as it is now with axe and fail on serious or critical
 * violations that no exception covers.
 *
 * The full results are attached to the test report. Minor and moderate
 * violations are added to the report as annotations without failing.
 *
 * @param page Page to scan, already in the state to check.
 * @param scan Name for the page and state, used in failures and exceptions.
 */
export async function expectNoAxeViolations(
	page: Page,
	scan: string
): Promise< void > {
	const results = await new AxeBuilder( { page } )
		.withTags( AXE_TAGS )
		.analyze();

	await test.info().attach( `axe: ${ scan }`, {
		body: JSON.stringify( results.violations, null, 2 ),
		contentType: 'application/json',
	} );

	const violations = withoutExceptions( scan, results.violations );
	const blocking = violations.filter( ( violation ) =>
		BLOCKING_IMPACTS.includes( violation.impact ?? '' )
	);
	const reported = violations.filter(
		( violation ) => ! blocking.includes( violation )
	);

	if ( reported.length > 0 ) {
		test.info().annotations.push( {
			type: 'axe (minor/moderate)',
			description: `${ scan }:\n${ formatViolations( reported ) }`,
		} );
	}

	// Compare the count, so the failure prints the summary above rather
	// than axe's raw result objects.
	expect(
		blocking.length,
		`${ blocking.length } serious/critical axe violation(s) in "${ scan }" (${ page.url() }):\n\n${ formatViolations(
			blocking
		) }\n\nFix the markup, or record an exception in tests/e2e/accessibility-exceptions.ts (see docs/accessibility.md).\n`
	).toBe( 0 );
}
