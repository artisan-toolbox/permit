# Changelog

## Unreleased

- Use a disposable application and a test user without the sample grant when following the Boost assignment example.

- Initialize the unpublished `1.0.0-alpha.1` preview for PHP 8.5, Laravel 13, and Inertia 3.
- Expose package version metadata and a managed README header through Maintainer's `Versionable` and `WithReadmeHead` contracts.
- Add a Composer-delivered frontend entry point with `PERMIT_VERSION`, ES module and CommonJS builds, and TypeScript declarations.
- Validate and rebuild frontend assets before Maintainer prepares a release.
- Configure PHP and frontend quality checks, tests, and CI for the declared compatibility requirements.
- Register Laravel Gates from attribute-based groups, CRUD permissions, and roles, with localized catalog ordering and an optional catalog build step.
- Persist explicit per-user assignments and expose an atomic facade, authenticated management endpoints, and Blueprint rename operations.
- Add revision-checked per-user caching, a `permit` route middleware, and commands to generate groups and inspect assignments.
- Share the actor's Gate decisions through Inertia and expose the typed Vue `Gate`, `$gate`, and `useGate()` APIs.
- Document preview installation and package adoption in the central documentation and bundled Boost skill.
- Add an original SVG identity, light and dark outlined wordmarks, and monochrome and duotone Font Awesome exports.
