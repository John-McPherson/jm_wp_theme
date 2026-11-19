# Releasing

## Status

The current package command builds the theme, generates `languages/jmc-theme.pot`, recreates `dist/`, and copies `build/`, `inc/`, `components/`, `templates/`, `languages/`, and required root runtime files. CI runs the command and checks its essential structure.

This is a structural gate, not a complete release test. Packaging remains Unix-specific because it uses `mkdir`, `rm`, and `rsync`; CI does not yet install and activate the package in a clean WordPress environment.

## Versioning and milestones

Use semantic versioning. The active plan is:

| Milestone | Outcome |
| --- | --- |
| `v0.2.0 – Site Foundations` | Header, footer, navigation, templates, patterns, and essential site-building blocks |
| `v0.3.0 – Block Stabilisation` | Consistent block/component contracts and resolved implementation defects |
| `v0.4.0 – Quality & Testing` | Behavioural tests, stricter TypeScript, E2E, and accessibility automation |
| `v0.5.0 – Release Hardening` | Portable deterministic packaging and clean-install verification |
| `v1.0.0 – Production Release` | Complete content, final QA, and supported production release |

See [Roadmap and issue workflow](roadmap.md) for completion criteria.

Keep versions aligned in `package.json`, `style.css`, and block metadata where applicable. Patch releases contain compatible fixes; `1.0.0` is the first stable supported release.

## Branches

- `dev` is the active integration branch and deploys to the development environment.
- Feature and fix pull requests target `dev`.
- `staging` holds the release candidate and deploys to the staging environment. Promote by merging `dev` into `staging`.
- `main` represents versioned, releasable work and deploys to production after approval.
- Formal release pull requests merge `staging` into `main`.

See [Deployment](#deployment).

## Required package contents

The installable theme must include:

- `build/`
- `components/`
- `inc/`
- `templates/`
- `parts/`
- `patterns/`
- `styles/` (colour palette style variations)
- `languages/` (the generated `jmc-theme.pot` translation template)
- `functions.php`
- `style.css`
- `theme.json`
- required licence and runtime documentation

Exclude `node_modules/`, `vendor/` unless runtime dependencies require it, `src/`, `.git/`, local configuration, and test output.

## Release checklist

1. Close or explicitly defer all milestone blockers.
2. Update versions and `CHANGELOG.md` once it exists.
3. Install dependencies from lockfiles with `npm ci` and `composer install`.
4. Run the complete local quality gate in [Testing](testing.md).
5. Run `npm run package` and inspect `dist/`.
6. Install and activate the package in a clean supported WordPress environment.
7. Complete block, template, navigation, accessibility, and compatibility smoke tests.
8. Update `Tested up to` only when supported by evidence, and review the [Platform support](support.md) matrix, recording the review date.
9. Merge `dev` into `staging`, then verify the staging deploy.
10. Open and review a `staging` to `main` release PR.
11. Tag the exact release commit and retain the tested package.
12. Merge the release PR, approve the production deploy in GitHub Actions, and verify production health.

## Rollback

Retain the previous known-good theme package and any required database backup before deployment. If activation, rendering, PHP logs, or primary journeys fail, restore the prior package and investigate outside production.

Exact backup, health-check, and rollback commands are still to be added before `v1.0.0` (#51).

## Deployment

The site is hosted on WP Engine. [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml) builds the theme with `npm run package` and deploys `dist/` on every push to a deployment branch, including merged pull requests:

| Branch | GitHub environment | WP Engine environment | Approval |
| --- | --- | --- | --- |
| `dev` | `development` | Development (`amcesdev`) | None |
| `staging` | `staging` | Staging | None |
| `main` | `production` | Production | Required reviewer |

Each run:

1. Gets the package:
   - `dev` and `staging` install locked npm and Composer dependencies and run `npm run package`.
   - `main` does not build. It downloads the package staging deployed for the same git tree (`git rev-parse HEAD^{tree}`), so production receives exactly the files tested on staging. Merging `staging` into `main` keeps the tree. If staging has not deployed that tree, the production deploy fails.
2. Checks the essential files are in `dist/`.
3. Syncs `dist/` to `wp-content/themes/amc_electrical/` over the WP Engine SSH Gateway with `--delete` (limited to the theme directory), lints PHP, and clears the cache.
4. After a successful deploy, uploads `dist/` as the artifact `theme-<branch>-<tree>` (kept 30 days for `dev`, 90 for `staging` and `main`).

A release must reach `main` through `staging`, and within 90 days of the staging deploy. If the staging package has expired, re-run that staging deploy first.

Deploys for a branch run one at a time and are never cancelled part-way through. The deploy does not activate the theme; activate it once in WP Admin after the first deploy to a new environment.

To redeploy without a new commit, run **Deploy** from the Actions tab on the branch.

### GitHub environment setup

Each GitHub environment (Settings → Environments) needs:

- **Deployment branches:** only its own branch (`dev`, `staging` or `main`).
- **Secret `WPE_SSHG_KEY_PRIVATE`:** the private deploy key.
- **Variable `WPE_ENV`:** the WP Engine install name.
- **Variable `WPE_SITE_URL`:** the environment's URL, shown on the deploy run.
- **Required reviewers (`production` only):** each production deploy waits for approval.

### Deploy key

The deploy key is a passphrase-free ed25519 key used only by GitHub Actions:

```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/wpengine_deploy_dev
```

Add the public key (`.pub`) in the WP Engine User Portal under your profile's **SSH keys**. WP Engine keys belong to a user, so the key reaches every install that user can access; use a user with only the access the deploys need. Store the private key only as the `WPE_SSHG_KEY_PRIVATE` environment secret and never commit it.

Test the key:

```bash
ssh -i ~/.ssh/wpengine_deploy_dev -o IdentitiesOnly=yes <install>@<install>.ssh.wpengine.net "ls wp-content/themes"
```

To revoke or rotate it, delete the key in the WP Engine User Portal, add the new public key, and update the secret in every GitHub environment.

### Before launch

Development, staging, and production stay on their `.wpenginepowered.com` addresses with **Settings → Reading → Discourage search engines** enabled. Staging must not send enquiry email to customers or the client.

## Launch: connecting the custom domain

Connecting the business domain (#180) is part of the `v1.0.0` launch and happens only after the launch checklist is approved. No theme or workflow change is needed: deploys use the WP Engine install name, not the domain.

1. Take an on-demand WP Engine backup of production.
2. In WP Engine, add the domain to the production environment, set it as primary, and issue the SSL certificate.
3. Have the domain owner add the DNS records WP Engine provides. The domain stays registered to the business, independent of the hosting account.
4. Confirm **Settings → General** shows the domain for both the WordPress Address and Site Address.
5. Replace stored URLs over SSH on production:

   ```bash
   wp search-replace 'https://<install>.wpenginepowered.com' 'https://<domain>' --skip-columns=guid
   ```

6. Untick **Discourage search engines** on production only.
7. Add the email provider's SPF and DKIM records if enquiry email is sent from the domain.
8. Update `WPE_SITE_URL` in the GitHub `production` environment.
9. Check HTTPS, redirects from the old address, navigation, and a test enquiry on the live domain.
