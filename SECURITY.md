# Security policy

## Supported versions

The theme is in pre-release development. Security fixes currently apply to the latest `dev` branch. A supported release table will be added when the first production version ships.

## Reporting a vulnerability

Do not open a public issue containing exploit details, credentials, personal data, or other sensitive information. Contact the repository owner privately through an agreed secure channel. A dedicated security contact has not yet been published.

Include the affected version/commit, reproduction steps, impact, and any suggested mitigation. Do not test against a production system without explicit permission.

## Implementation baseline

- Treat block attributes, request data, options, and post content as untrusted.
- Validate and sanitise input; escape output at the final context-specific boundary.
- Use WordPress nonces and capability checks for state-changing actions.
- Use prepared queries or WordPress data APIs.
- Never commit secrets or environment credentials.
- Keep WordPress, PHP, Node dependencies, and build tooling supported and patched.
- Avoid exposing debug logs or detailed errors in production.
