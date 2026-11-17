---
name: dev
description: Developer for the AMC Electrical / JMC theme. Implements the current GitHub ticket in the house style — block-first, reusing PHP components, React editor components and helpers/utils — and keeps every change scoped to that ticket. Use whenever the user asks to build, implement, fix, add, refactor or write code in this theme (a block, render.php, edit.tsx, view script, SCSS, a component, a helper, a test), says "work on #NN", "pick up this ticket", "let's build X", or continues implementation on the current branch — even if they don't mention the ticket or style explicitly.
argument-hint: "[issue number | task description]"
---

# Developer

You implement work in this theme the way its author would: block-first, composed from
existing components and helpers, and confined to the ticket in hand. The `pm` skill decides
*what* to work on; this skill decides *how* the code gets written.

## 1. Pin down the ticket before touching code

Everything you do is measured against one GitHub issue, so find it first.

1. If `$ARGUMENTS` has an issue number, use it.
2. Otherwise read the branch: `git branch --show-current`. Branches are usually
   `<issue-number>-<slug>` (e.g. `22-implement-accessible-primary-and-mobile-navigation`),
   sometimes `type/slug`. A leading number is the issue.
3. Otherwise check for a linked PR: `gh pr view --json number,title,closingIssuesReferences`.
4. If you still can't identify it, ask the user which issue this is for. Don't guess — the
   whole point is that scope comes from the ticket.

Then read it: `gh issue view <n> --json number,title,body,labels,milestone,comments`
(repo `John-McPherson/jm_wp_theme`; if `gh` fails with a TLS/network error in the sandbox,
rerun outside it; on auth failure tell the user to run `! gh auth login`).

Extract and state back in one short block before you start:
- **Outcome** — what the ticket wants.
- **In scope** — the files/blocks/systems it names or clearly implies.
- **Acceptance criteria** — the checklist you'll be judged against.
- **Out of scope** — anything the issue explicitly defers.

Also check what's already been done on the branch (`git log dev..HEAD --oneline`,
`git diff dev...HEAD --stat`, `git status --short`) so you continue rather than redo.

If the user's request goes beyond the ticket, say so and ask whether to widen the ticket,
open a new one (`/pm suggest`), or drop it. Don't silently expand.

## 2. Keep the change scoped to the ticket

The author reviews diffs against the ticket. Unrelated edits make that review slower and
mix concerns across PRs, so:

- Change only what the acceptance criteria need. Touching a shared component, helper or
  util is fine **when the ticket's work requires it** — keep that edit minimal and
  backwards-compatible for the other blocks that use it.
- No drive-by refactors, renames, reformatting, lint fixes in untouched code, dependency
  bumps, or "while I'm here" cleanups.
- When you notice something wrong outside scope (a bug, legacy attribute, naming
  inconsistency, missing test), don't fix it — record it as a follow-up in your final
  report with a `file:line`, so it can become its own ticket.
- Docs are in scope when the ticket changes a public contract: update `docs/blocks.md`
  (block inventory/contract), `docs/testing.md` (new tests/commands), and similar.
- If the ticket turns out to be wrong or under-specified (e.g. the criteria conflict with
  the code), stop and raise it rather than improvising scope.

## 3. Block-first design

Before writing anything, decide where the code belongs. Work through this in order:

1. **Is it a block?** User-facing content and sections are dynamic blocks under
   `src/blocks/<group>/<name>/` — `layouts/` for insertable sections (category
   `jmc-section`), `content/` for composition blocks used inside sections
   (`supports.inserter: false`), `site/` for site chrome (category `jmc-site`). Prefer
   composing with existing content blocks (`jmc/heading`, `jmc/paragraph`, `jmc/button`,
   `jmc/column`) via `InnerBlocks` over adding new text attributes.
2. **Does a component already render this markup?** Check `components/*.php` (heading,
   image, paragraph, button-link) and call it with `jmc_component( name:, args: )` instead of
   writing markup inline.
3. **Does an editor component already exist?** Check `src/components/` (`Sidebar`,
   `SelectInput`, `TextInput`, `MediaInput`, `LinkToolBar`, `ButtonLink`) and wire it with
   `bindFields` from `src/utils/`.
4. **Does a helper already exist?** Check `inc/helpers/` — `jmc_args_int`,
   `jmc_args_string`, `jmc_html_allowed_value`, `jmc_html_allowed_tokens`,
   `jmc_html_classes`, `jmc_the_attributes` / `jmc_get_the_attributes`, `jmc_html_styles`.
   Never hand-concatenate attributes or classes.
5. **Only then write new code.** If the markup is needed by more than one block, make it a
   PHP component in `components/`; if logic is repeated, make a helper in `inc/helpers/` or
   a util in `src/utils/`. A component must not know about a specific block; a helper must
   not render a specific feature. Don't extract speculatively for a single use.

Run `ls components src/components src/utils inc/helpers inc/helpers/html` at the start —
the inventory above may have grown.

## 4. Write it in the house style

Read `references/conventions.md` before writing code — it has the annotated patterns for
`block.json`, `render.php`, `edit.tsx`, `index.ts`, `view.ts`, SCSS, components, helpers
and tests, taken from this codebase. When in doubt, open the closest existing block
(`src/blocks/layouts/text-with-image/` and `hero/` are good references) and match it.

The essentials, so you can't miss them:
- PHP: `declare(strict_types=1);`, file docblock with `@package JMC_Theme`, an `@var` array
  shape for `$attributes`/`$args`, named arguments, `jmc_` prefix, validate every attribute
  against an allowlist with a fallback, escape at output, return early instead of emitting
  empty wrappers.
- Enumerations (palette, order, variant) use the **same** values in `block.json` `enum`,
  the editor options, the PHP allowlist and the SCSS selectors.
- TS/TSX: WordPress formatting (tabs, spaces inside parens), `__( '…', 'jmc-theme' )` for
  every string, typed props, no new `any`.
- SCSS: `@use` the abstracts, wrap rules in `@layer blocks`, `jmc-` BEM classes, consume
  existing `--jmc-*` tokens, use the `min()` container mixin for breakpoints.

## 5. Verify against the acceptance criteria

Run the gates relevant to what you touched (full list in `docs/testing.md`):

```bash
npm run typecheck
npm run lint:js && npm run lint:styles
npm run lint:php && npm run lint:phpcs
composer test                          # PHP render/helper changes
npm run test:unit -- --runInBand       # view script changes
npm run build                          # always, before review
```

Add or extend tests when the ticket changes behaviour: PHPUnit render tests under
`tests/phpunit/Unit/` covering omitted, default, supported and malformed attributes; Jest
tests beside the view script (`view.test.ts`); Playwright only when the ticket is about
browser behaviour. If a gate fails on code you didn't touch, report it — don't fix it.

## 6. Report

End with:

- **Ticket** — `#NN Title`.
- **Changes** — files touched, each with one line on why.
- **Acceptance criteria** — each one, marked met / not met, with the evidence.
- **Verification** — commands run and results (paste failures in full).
- **Manual checks still needed** — editor/frontend parity, palettes, keyboard, responsive.
- **Out-of-scope follow-ups** — things you noticed and deliberately left, with `file:line`.
  Suggest `/pm suggest` if there are any worth ticketing.

Commit only when the user asks. Use the conventional prefixes seen in history (`feat:`,
`fix:`, `test:`, `docs:`, `chore:`), one focused change per commit, and target PRs at `dev`.
