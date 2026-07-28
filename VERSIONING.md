# Versioning

`rest_api` follows [Semantic Versioning 2.0.0](https://semver.org/). This document defines **what the
version number protects** — because semver is only meaningful once "the public API" is pinned down.

## Release lines

| Version | What it is | Branch |
|---------|-----------|--------|
| **`1.0.0`** | The pre-transition stable system — the deployed application, as reconciled onto `main`. Tagged baseline. | `main` |
| **`2.0.0`** (in progress) | The **library** conversion: `rest_api` becomes a no-code library included by a host app, driven by config files. A deliberate breaking change. | `lib` |

`2.x` development carries the version `2.0.0-dev` in `composer.json` until the first `2.0.0` release is tagged.

## The public contract (what a MAJOR bump protects)

A consumer of this library depends on three surfaces. A **backward-incompatible** change to any of them is a
**MAJOR** bump:

1. **The entry function** — the signature of `rest_api(...)` (db credentials + host + models folder + user
   identity). See `examples/index.php`.
2. **The config-file schema** — the shape of the arrays a site author writes: `$RESOURCES`, `$MODELS`,
   `$RIGHTS`, `$APIS` (and `$JOINS`/`$SUBS`). See `examples/users.php`.
3. **The HTTP response envelope** — the response layout the frontend decodes: the per-resource rows, the
   runtime `_k` schema (`field → [index, key, filterEcho, label, format]`), `_ctx`, and `_no_data`.

## Bump rules

- **MAJOR** (`x.0.0`) — a breaking change to any of the three surfaces above: removing/renaming a config key,
  changing the entry signature, altering the response envelope shape or the `_k` slot meanings.
- **MINOR** (`x.y.0`) — backward-compatible additions: a new optional config feature, a new `$APIS` verb, a
  new optional field in the envelope that old clients can ignore.
- **PATCH** (`x.y.z`) — internal fixes with no contract change: query bugs, error handling, performance.

## Process

- The version of record is the **git tag** (`vX.Y.Z`, annotated), mirrored in `composer.json` and `CHANGELOG.md`.
- Any PR that changes one of the three surfaces above **must** bump the version and add a `CHANGELOG.md` entry.
- `2.0.0` ships when: the `rest_api()` entry function is implemented, the `examples/` boot green in CI, and the
  `LICENSE`/docs are in place.

## Open questions (for review)

- [ ] Is this the right definition of the public contract, or is there a fourth surface?
- [ ] Is the `1.0.0 → 2.0.0` jump agreed (skipping intermediate versions on the old line)?
- [ ] Do we want pre-release tags on the road to 2.0 (`2.0.0-alpha.1`, `-beta.1`) or just tag `2.0.0` at the end?
