/**
 * Accepted axe violations.
 *
 * A serious or critical axe violation fails the build. Add an entry here only
 * when a violation cannot be fixed yet, and never to get a pull request green:
 * fix the markup instead wherever you can. Every entry is reviewed in the pull
 * request that adds it, and `tests/e2e/axe.spec.ts` fails once its review
 * date has passed, so it is looked at again rather than forgotten.
 *
 * See "Accessibility exceptions" in docs/accessibility.md.
 */
export type AccessibilityException = {
	/** The axe rule ID, as printed in the failure (for example `color-contrast`). */
	rule: string;
	/**
	 * The element selectors the exception covers, exactly as the failure
	 * prints them under "Targets". Other elements failing the rule still fail.
	 */
	targets: string[];
	/** Scan names the exception applies to; omit to apply it to every scan. */
	scans?: string[];
	/** Why the violation is accepted for now, and what fixing it needs. */
	rationale: string;
	/** GitHub username responsible for resolving it. */
	owner: string;
	/** Date to review the exception by, as `YYYY-MM-DD`. */
	reviewBy: string;
	/** Issue tracking the fix. */
	issue: string;
};

export const accessibilityExceptions: AccessibilityException[] = [];
