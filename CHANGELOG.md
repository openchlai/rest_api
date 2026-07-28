# Changelog

All notable changes to `rest_api` are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to
[Semantic Versioning](./VERSIONING.md).

## [Unreleased] — targeting 2.0.0

The **library transition**: `rest_api` becomes a no-code library included by a host application rather than a
standalone deployed app. This is a breaking change against `1.0.0`.

### Added
- `rest_api.php` as the single library entry file (renamed from `lib/rest.php`).
- `examples/` — a minimal `index.php` (include + invoke) and `users.php` (a worked resource config).
- Generic schema globals: `$RESOURCES`, `$MODELS`, `$MODELS_K`, `$JOINS`, `$APIS`, `$SUBS`, `$RIGHTS`.
- `_ks()` — builds `$MODELS_K` (the `column → position` map) at runtime, so it no longer ships as a static file.

### Changed
- Schema is now supplied by the host via a **config-file folder**, not hardcoded model files.
- `$<r>_def` → `$MODELS[<r>]`; static `model_k.php` → dynamic `$MODELS_K`; `$RIGHTS_1/2/3` → `$RIGHTS[level][r]`.

### Removed
- Deployment-specific code: `api/index.php`, `api/model*.php`, `config/*`, and the auth / session / telephony /
  third-party / spreadsheet helpers (`lib/session.php`, `lib/XLSX*.php`, `lib/dialplan.php`, `lib/import.php`,
  `lib/rpc.php`). These belong to the host app, not the library.

### TODO before 2.0.0
- [ ] Implement the `rest_api(...)` entry function (DB connect + models-folder load + request dispatch).
- [ ] `examples/` boot green in CI (smoke test).

---

## [1.0.0] — 2026-07-22

The pre-transition stable baseline: the deployed system, with the live `k2` line reconciled onto `main`.
Tagged `v1.0.0` as the anchor before the library transition. (Retroactive entry — versioning starts here.)

[Unreleased]: https://github.com/BITZ-IT-Consulting-LTD/rest_api/compare/v1.0.0...lib
[1.0.0]: https://github.com/BITZ-IT-Consulting-LTD/rest_api/releases/tag/v1.0.0
