# Changelog

## Unreleased

- Include `WithRole` requirements in the permission editor's GET catalog. Use the operator's visible role names and keep hidden role names private. Operators can still assign visible permissions before the selected user meets their requirements.

## [1.0.0-beta.4] - 2026-11-23

### Features

- Add `WithRole` to require a permission grant and all listed roles by default. Pass `RoleMatch::Or` to accept any listed role. Group rules cover generated CRUD permissions and custom permissions.
- Add `ManageableByRoleUser` to require any listed role to view and edit an assignment by default. Pass `RoleMatch::And` to require all listed roles. The editor omits protected assignments and hides groups with no visible permissions. Saving preserves hidden assignments and defaults, even if the request includes a hidden key. A change to the visible keys invalidates an open editor's revision.
- Both attributes accept comma-separated role method names. When a group and a method each declare a rule, both rules must pass. If you use a cached catalog, rebuild it with `php artisan permit:catalog:cache` after upgrading.

### Continuous Integration

- Run PHPStan with one analysis worker on Windows to prevent competing Larastan writes to the Testbench service manifest. Verify that the distributed PHPStan configuration loads the platform settings.

## [1.0.0-beta.3] - 2026-11-20

### Fixes

- **Isolate Testbench bootstrap caches before Laravel boots** (`4924cde`)
  Creates a unique system-temporary bootstrap directory and its `cache` subdirectory for each Testbench test, then configures Laravel to use that path during application resolution. This prevents parallel Pest tests from contending for a shared `services.php` manifest on Windows. The directory is removed even if parent teardown throws. A regression test verifies that the service provider manifest exists under the system temporary directory rather than in the shared Testbench directory. Authorization fixtures now use Laravel’s default bootstrap path while retaining their fixture app path and namespace. No application changes are required.

### Documentation

- **Document version requirements for permission editor icons** (`151f890`)
  Clarifies that displaying catalog icons in the permission editor requires Permit 1.0.0-beta.2 and Front Awesome 1.0.0-beta.6 or later. The reports catalog example still uses Font Awesome icon names in `app/Permit/Reports.php`. This is a documentation-only change.

### Build

- **Upgrade Maintainer and standardize local quality checks** (`d013651`)
  Raises the development minimums to Maintainer `^1.14.3`, Larastan `^3.12.1`, and Pest Type Coverage `^5.1.1`. Removes the direct PHPStan requirement and its `^2.2.8 <2.3.6` constraint so Composer can resolve a compatible version through the remaining tools. `composer test` now runs PHPStan, Pint, and Pest through Maintainer’s strict, non-interactive quality check, then runs the type coverage check separately. Pest defaults to two workers on non-Windows platforms and serial execution on Windows; set `MAINTAINER_PEST_PARALLEL=false` to keep serial runs or use `MAINTAINER_PEST_PROCESSES` to change the worker count. Vitest is capped at two workers, and `npm test` runs once without watching, including when called by `release:build`. Maintainer also gains separate environment-configurable AI models for commit messages, release type suggestions, release notes, and changelog updates, all defaulting to `gpt-6.2-sol`, plus separate local feedback and project locale settings. The project locale defaults to `en`, and the release workflow is set to `tests.yml`. These changes affect development and release tooling, not the runtime API.

### Continuous Integration

- **Add PHP 8.6 to the CI test matrix** (`273e83d`)
  Runs the main test suite on PHP 8.6 alongside PHP 8.5 on Ubuntu and Windows. The authorization integration checks also cover both PHP versions for PostgreSQL with Redis and MariaDB with Memcached. This expands compatibility testing without changing the application API.

- **Use Maintainer to set up CI dependencies** (`ff8f6d2`)
  Consolidates dependency setup for the compatibility matrix and authorization integration jobs through the Maintainer root action, introduced at 1.14.2 and updated to 1.14.4 in the final workflow. The main job retains `prefer-lowest` and `prefer-stable` Composer resolution and Node.js 24 with npm caching, but no longer explicitly overrides the Laravel `13.*` and Testbench `11.*` constraints. CI runs Maintainer quality checks in strict, non-interactive mode. Concurrency is scoped by event and ref so newer pull request runs cancel older ones, while non-pull-request runs are grouped by commit SHA.

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

- Test PHP 8.5 and 8.6 in CI with the Maintainer 1.14.4 setup action.

- Run strict Maintainer 1.14.3 checks in CI. Use two workers for Vitest and for Pest on Unix. Run Pest serially on Windows.

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
