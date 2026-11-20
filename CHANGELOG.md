# Changelog

## [1.0.0-beta.2] - 2026-11-19

### Features

- **Add Font Awesome icons support to permission editor catalog** (`ed90b31`)
  Adds Font Awesome icon support to the permission editor’s catalog. This includes wiring icon data into the management UI so permissions/groups/abilities can optionally render icons in the catalog/editor experience.

User impact: maintainers and end-users gain the ability to display icons within the permission editor catalog, improving clarity and UX.

Compatibility/migration: icon rendering is introduced as optional behavior; existing configurations and attribute usage should continue to work. To enable icons, configure the appropriate icon settings (e.g., via the added config block) and provide `icon` values where supported.

### Fixes

- **Persist catalog icons with translated caching** (`eb3bc91`)
  Ensures that catalog icon data is retained correctly when catalogs are loaded from translated caches. This fixes an issue where icon-related information could be lost or mismatched after caching/translation, so the presented permission/group catalog remains consistent across locale-aware loading.

User impact: users using the permission catalog with optional icons should no longer see icons disappear or be incorrect when cached translated catalogs are reused.

Compatibility/migration: this is a bug fix; no public API changes are required. If you rely on cached catalogs, you should see corrected icon persistence automatically after updating.

### Documentation

- **Clarify badge and UI reuse standards** (`47e9a90`)
  Updates documentation guidelines regarding badge usage and UI reuse standards. This clarifies how shared documentation/UI patterns should be applied across the project, including standards that affect how README badge rows and related UI elements should be structured.

User impact: documentation-only change that helps maintainers produce consistent package READMEs and reduces release-process inconsistencies.

Compatibility/migration: no runtime or API changes.

### Maintenance

- **Update README badges for beta release** (`9ba1f4e`)
  Refreshes documentation badges and README badge metadata for the beta release cycle. Ensures the project/package README status indicators and related badge rows reflect the correct beta/pre-release context for `1.0.0-beta.2`.

User impact: documentation-only change; helps consumers and maintainers identify the correct release state and installation guidance.

Compatibility/migration: no runtime/package API changes.

## Unreleased

- Isolate the Testbench bootstrap cache before Laravel starts each test, preventing parallel Pest tests from competing for `services.php` on Windows.
- Use the Maintainer 1.14.2 root action for dependency setup in the compatibility and authorization integration jobs.
- Require Larastan 3.12.1 and Pest Type Coverage 5.1.1 as development minimums. Composer resolves a compatible PHPStan version through those tools for static analysis and type coverage.
- Install the published Permit beta from Packagist in the README and Boost skill.
- Add optional Font Awesome icons to permission groups and abilities in the management editor. Configure the icon family, generated CRUD icons, and built-in management icons in `config/permit.php`.
- Fix catalog type analysis with PHPStan 2.2.8 in the lowest-dependency CI lane. Verify that translated labels and optional icons remain intact when the editor loads a cached catalog.
- Run every GitHub Actions matrix entry independently and check the frontend build and committed bundles in separate steps.

## [1.0.0-beta.1] - 2026-11-18

### Features

- **Add user permissions management with integration tests** (`6ecd8c6`)
  Introduces user permissions management functionality along with corresponding integration tests. This adds the core behavior needed to create, assign, and enforce permissions for users, and validates it end-to-end through integration coverage. User impact is new/expanded authorization capabilities backed by automated verification. Compatibility considerations: existing installations may need to align their data/seed/state with the new permissions model (depending on how migrations are handled in the full diff), but this is described as a feature addition and is not inherently a breaking runtime change; migrations or configuration changes (if any) would be required to use the capability.

### Fixes

- **Use input argument getArgument in InspectAssignments command** (`1c620ce`)
  Updates the InspectAssignments command implementation to read the provided value via the input argument’s getArgument accessor rather than the previous mechanism. This corrects argument handling so the command reliably receives the user-supplied data it needs to run. User impact is improved CLI correctness/robustness; compatibility is improved for existing command usage patterns, with no intended runtime API changes beyond fixing how the command consumes its inputs.

### Tests

- **Add memcached server weight to auth integration tests** (`893dd81`)
  Extends the auth integration test suite to include memcached server weighting. This improves test fidelity for environments where memcached distribution/weighting affects caching or session-related behavior, reducing the chance of false positives/negatives in integration coverage. User impact is higher confidence in auth-related behavior under realistic caching topology; no public APIs are changed and no breaking compatibility implications are expected.

### Build

- **Initial commit** (`f1646ec`)
  Establishes the initial repository baseline. This includes the first introduction of the project structure and core files that subsequent commits build on. User impact is the creation of the project itself; migration/compatibility concerns are not applicable beyond adopting the repository as a whole at its initial state.

### Maintenance

- **Add agent skills standards and update release assets** (`0dd41a6`)
  Performs repository maintenance to support the agent/skills documentation and standards workflow by adding/updating agent skills standards and related release assets. This is process and documentation-system work: it adds new skill/standards guidance and updates assets used during release preparation. User impact is indirect (improved documentation/agent tooling consistency) and there are no intended runtime behavior or public API changes stemming from this chore.

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
