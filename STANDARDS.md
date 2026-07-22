# Coding standards

This is a **descriptive** guide, not an enforced linter. Its job is to let a newcomer (or an AI agent) read and
extend the codebase without surprises. The bar is: **match the surrounding code and keep it readable.**

## The idiom of this codebase
`rest_api.php` is deliberately **procedural and terse**. That is the house style — do not "modernize" it into
classes/PSR-12 as a drive-by; large reformatting hurts reviewability for zero user benefit.

- **Procedural functions**, not classes. Shared state lives in `$GLOBALS` (`$RESOURCES`, `$MODELS`, `$RIGHTS`, …).
- **Tabs** for indentation (see `.editorconfig`).
- **Positional arrays** are the schema/data representation. Column definitions, `_k`, and rows are all
  index-addressed arrays — meaning comes from position, not keys. When you touch these, preserve slot order and
  document any new slot in `VERSIONING.md`.
- **Short function names** (`_val`, `_upd`, `_add`, `ctx`, `rpt`, `k_ch`). Prefix `_` marks internal helpers.
- Errors accumulate in `$ERRORS`; DB access goes through `qryp(...)`.

## Objective rules (the only hard ones)
- Code must pass **`php -l`** (no syntax errors) — enforced in CI.
- Do not introduce **PHP notices/warnings** in the `examples/` happy path.
- No secrets, credentials, or environment-specific paths committed to the library. Those belong to the host app
  and its config folder.
- New public behavior ships with an update to `examples/` demonstrating it.

## When adding a feature
1. Decide which public surface it touches (`VERSIONING.md`) and bump/changelog accordingly.
2. Keep the config-file contract declarative — a site author writes arrays, not code.
3. Prefer extending an existing `$APIS` verb pattern over a new bespoke code path.
