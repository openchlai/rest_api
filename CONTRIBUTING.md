# Contributing to rest_api

## Branches
- **`main`** — stable, released (`v1.0.0`). Protected; changes land via PR.
- **`lib`** — the library (`2.0.0`) line under active development.
- Work on a topic branch (`<name>/<topic>` or `feat/…`, `fix/…`, `chore/…`) and open a PR into the right base.

## Pull requests
1. Keep PRs focused. A draft PR is welcome for early discussion.
2. **CI must pass** — PHP syntax lint (`php -l`) and CodeQL run on every PR (see `.github/workflows/ci.yml`).
3. **`examples/` must still boot.** If you change behavior, update the examples.
4. **Contract changes bump the version.** If your change touches any of the three public surfaces in
   [`VERSIONING.md`](./VERSIONING.md) — the `rest_api()` entry signature, the config-file schema, or the HTTP
   response envelope — you **must** update `composer.json` `version` and add a `CHANGELOG.md` entry.
5. Respect [`CODEOWNERS`](./CODEOWNERS): engine changes to `rest_api.php` need the engine owner's review;
   packaging/licensing/CI changes need the maintenance owner's review.

## Coding style
See [`STANDARDS.md`](./STANDARDS.md). Short version: match the surrounding code — this is a deliberately terse,
procedural, tab-indented codebase. We do **not** run an enforced style linter; readability and consistency with
neighbours is the bar.

## Running the examples locally (smoke test)
```bash
# 1. a scratch MySQL with a table matching examples/users.php
# 2. point the include path in examples/index.php at this checkout
# 3. serve it and hit the generated endpoints, e.g.:
curl "http://localhost/myapp/api/users/"
```
(A scripted MySQL-backed smoke test is being wired into CI — see the TODO in `.github/workflows/ci.yml`.)

## Releases
Tag `vX.Y.Z` (annotated) on `main`, mirror the version in `composer.json`, and move the `CHANGELOG.md`
`[Unreleased]` section into the new version.
