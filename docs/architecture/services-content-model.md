# Services content model and source of truth

- Status: Accepted
- Decision date: 2026-08-11
- Applies to: Service landing page, individual service pages, service cards, navigation, search, breadcrumbs, and structured data
- Related issue: [#43](https://github.com/John-McPherson/jm_wp_theme/issues/43)
- Amended: 2026-11-17 by [#6](https://github.com/John-McPherson/jm_wp_theme/issues/6): service cards use a service icon instead of the featured image (see [Amendment: service icons](#amendment-service-icons))

## Context

The electrical company needs a modest, stable set of service-focused landing pages. Each service needs an indexable URL, flexible long-form content, consistent presentation, and inclusion in listings and navigation.

A custom post type would add a separate content lifecycle, rewrite and archive configuration, template routing, and migration overhead. The current catalogue does not require independent permissions, complex relationships, faceted filtering, or multiple service taxonomies.

## Decision

Services are governed WordPress pages beneath one canonical Services parent page.

```text
/services/
/services/rewiring/
/services/ev-charger-installation/
/services/emergency-electrician/
```

The page record is the single source of truth. Service names, summaries, images, URLs, ordering, body content, navigation, search results, breadcrumbs, cards, and structured data must derive from that record. Editors must not maintain a second manual list of services.

A `service` custom post type is not introduced.

## Content structure

### Services landing page

The page at `/services/`:

- is the parent of every published service page;
- owns the landing-page introduction, trust content, and calls to action;
- renders its service grid dynamically from direct published children;
- does not contain manually duplicated service cards.

### Service pages

Every service is a direct child of `/services/` and uses the governed service-page template or locked pattern. Deeper descendants are ordinary supporting pages unless the model is deliberately revised.

Each service page uses native WordPress fields wherever possible:

| Concern | Source of truth | Requirement |
| --- | --- | --- |
| Service name | `post_title` | Required |
| Canonical URL | `post_name` plus parent hierarchy | Required and stable after publication |
| Card summary | `post_excerpt` | Required; plain, concise copy |
| Card icon | `jmc_service_icon` page meta | Required; a registered icon chosen in the Service icon panel |
| Main content | `post_content` | Required |
| Service membership | `post_parent` | Must reference the canonical Services page |
| Display order | `menu_order` | Required; lower values appear first |
| Publication state | `post_status` | Only published pages appear publicly |

Custom post meta may be added only for a value that is structured, validated, and cannot be represented reliably by a native field or the page body. Presentation-only values must remain in templates and styles, not content metadata. The service icon meets this rule: it is a single registered icon name, validated against the icon registry on save and on render, and no native page field can hold it.

## Retrieval contract

Service listings query direct published child pages of the configured Services page and order them by `menu_order ASC, post_title ASC`.

Implementations must identify the parent by a stable configured page ID or resolved canonical page, not by hard-coded database IDs. The slug may be used during setup or resolution but should not be repeatedly embedded across blocks and templates.

The query must:

- return only `page` records with `post_status = publish`;
- restrict results to direct children of the Services page;
- ignore unrelated pages;
- avoid a fixed item limit that silently drops valid services;
- preserve deterministic ordering.

A server-rendered block or an appropriately constrained core Query Loop may implement the grid. Whichever mechanism is selected becomes the shared implementation for every service listing.

### Implementation

The grid is the server-rendered `jmc/service-grid` block, whose cards come from `jmc/service-list`; see [Services grid](../blocks.md#services-grid). A core Query Loop was not used because it cannot take its parent from a site-wide setting resolved at render time, so it could not enforce "direct children of the configured Services page" without per-instance configuration.

- The parent is the page chosen under **Settings → Reading → Services page** (the `jmc_services_page_id` option, stored by ID), or, when nothing is chosen, the published page with the slug `services`. A chosen page that is no longer a published page resolves to nothing rather than falling back, so the misconfiguration stays visible.
- The query also excludes password-protected pages, whose excerpts are hidden.
- `jmc_get_services()` in `inc/helpers/services.php` is the one retrieval function; any future service listing must use it.

## Templates and presentation

The theme provides:

- a Services landing-page template or assigned template for the parent;
- a governed service-page template, template part, or locked pattern for children;
- a reusable service-card presentation driven by queried page data.

Templates own layout and visual presentation. Page records own editorial content. Editors must not copy card markup, service titles, excerpts, icons, image URLs, or canonical links into templates.

The service-page structure should consistently support:

1. page title and introductory proposition;
2. service-specific content;
3. trust and qualification evidence;
4. relevant call to action;
5. optional related content where a defined relationship exists.

### Implementation

- `templates/services-landing.html` lays out the Services page and `templates/service.html` its direct children. The theme routes each page to its template from the resolved Services page, ahead of any template assigned in the editor, and never applies either template to deeper descendants or unrelated pages; see [Services templates](../blocks.md#services-templates).
- The templates hold the header, the page content and the footer, and no editorial content. Breadcrumbs belong to the page's hero, which shows them on child pages; see [Hero breadcrumbs](../blocks.md#hero-breadcrumbs).
- The `jmc/services-landing` and `jmc/service-page` starter patterns give each page the structure above, as locked sections whose copy belongs to the page. Only the landing page lists services, through `jmc/service-grid`.
- No related-content section is provided yet, because no relationship between services is defined beyond their shared parent.

## Navigation, search, and breadcrumbs

- Navigation links point to the canonical page URLs.
- Service pages remain included in normal WordPress search unless a separate documented search decision overrides this. Front-end site search is currently turned off for the whole site, so this applies again if search is restored; see [Site search](../blocks.md#site-search).
- Breadcrumbs derive from the native page ancestry: Home → Services → Service. The hero renders them on service pages, with Home as an icon link; the Services page itself is top level, so it shows its label instead.
- Menus may feature selected services, but a menu is not the service catalogue or source of truth.
- Removing a service requires unpublishing or moving the page and reviewing redirects and inbound navigation.

## Structured data

Structured data is generated from the same page record and site-wide organisation data. It must not introduce a parallel editorial form containing duplicated names, descriptions, images, or URLs.

At minimum:

- use the canonical page URL;
- derive the visible name and description from the page;
- represent the electrical company consistently with the site's organisation data;
- ensure markup describes content visible on the page;
- validate the final rendered output.

Any additional schema-specific value must satisfy the custom-meta rule: structured, validated, and not reliably derivable from existing content.

### Implementation

The theme outputs no structured data. Schema is deferred to Yoast SEO, which is planned to own it and builds it from the page record (title, excerpt, permalink and hierarchy) and its site-wide organisation settings, satisfying the rules above without a parallel form. The theme's part is to keep the visible content clean for it: one H1 per page, the excerpt as the page summary, canonical permalinks, and native breadcrumbs. Validate the rendered output once Yoast is configured.

## Editorial governance

Before publication, a service page must have:

- the canonical Services page as its direct parent;
- the governed service template or pattern;
- a unique title and slug;
- a concise excerpt suitable for a card and search preview;
- a service icon;
- complete page content and a clear call to action;
- an intentional `menu_order`;
- reviewed metadata and rendered structured data.

Editorial documentation should identify who may create, publish, rename, move, or retire service pages. Slug or hierarchy changes require a redirect from every previous public URL.

Automated validation should be preferred for enforceable invariants, particularly parent assignment, required excerpt, service icon, and valid ordering.

The step-by-step publishing workflow is in [Publishing a service](../blocks.md#publishing-a-service).

## Migration

Existing service content must be inventoried before implementation. For each service:

1. select one canonical page;
2. place it directly beneath `/services/`;
3. populate the native fields defined above;
4. assign an explicit display order;
5. remove duplicated cards or hard-coded template content;
6. add redirects for replaced URLs;
7. verify navigation, search, breadcrumbs, grid output, and structured data.

Duplicate or overlapping service pages should be consolidated rather than represented as multiple canonical services.

## Rejected alternatives

### Service custom post type

Rejected for the current site because services behave like a small set of hierarchical SEO landing pages and do not need an independent taxonomy, permissions model, workflow, archive lifecycle, or relationship model. The added registration, rewrite, template, and migration complexity would not currently buy meaningful capability.

Reconsider a custom post type if the catalogue grows to dozens or hundreds of records, requires multiple taxonomies or faceted filtering, needs distinct permissions or lifecycle rules, or develops structured relationships to locations, pricing, case studies, or variants.

### Manually maintained service grid

Rejected because it duplicates titles, summaries, images, ordering, and links. Duplicate content inevitably drifts from the canonical service pages and weakens validation.

### Navigation menu as the catalogue

Rejected because menus are presentation and wayfinding structures. They may intentionally contain only a subset of services and do not provide the content contract required by templates, search, or schema.

## Consequences

### Benefits

- Native WordPress hierarchy, URLs, menus, search, revisions, and editor familiarity.
- A single record drives all public representations of a service.
- Fewer theme-specific APIs and less routing complexity than a custom post type.
- Straightforward migration to a custom post type later if documented triggers are reached.

### Trade-offs

- WordPress does not enforce the hierarchy or required fields by default, so governance and validation are necessary.
- Core Query Loop support for a dynamically configured parent may be insufficient; a small server-rendered block may be preferable.
- Pages share the general page permission and lifecycle model.

## Acceptance checks

The decision is implemented correctly when:

- publishing a valid child service causes it to appear once in the Services grid without editing the grid;
- changing a service title, excerpt, icon, order, or URL is reflected everywhere that consumes it;
- unrelated pages never appear in the service catalogue;
- drafts and private pages never appear publicly;
- breadcrumbs and canonical URLs follow the page hierarchy;
- structured data matches the visible canonical page content;
- editors can identify and follow the creation, update, and retirement rules.

## Amendment: service icons

- Date: 2026-11-17
- Issue: [#6](https://github.com/John-McPherson/jm_wp_theme/issues/6)

Service cards show an icon rather than the featured image. Photographs at card size added little to identify a trade service, depended on every page having suitable imagery with good alternative text, and made the grid visually heavy; a consistent outline icon set reads faster and needs no alternative text, because the icon is decorative and the card's link text names the service.

- The icon is stored in the `jmc_service_icon` page meta and chosen in the page's **Service icon** panel, from the theme's `jmc` icon collection (Lucide icons generated at build time; see [Icons](../blocks.md#icons)).
- Featured images are no longer part of the service card contract. Pages may still have one, for example for social sharing, but the grid does not use it.
- A page without an icon still renders a card without one; the editorial requirement to choose an icon stands.
