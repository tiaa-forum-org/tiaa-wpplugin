# Audit Prompt — Pass 1: Sonnet Mapping Pass

**Target:** `tiaa-wpplugin` repo (github.com/tiaa-forum-org/tiaa-wpplugin)
**Run in:** Claude Code
**Model:** Sonnet
**Purpose:** Build an accurate, structured map of the codebase for Fable's Pass 2 judgment audit. This pass does not evaluate or recommend — it inventories.

---

## Prompt

```
You are doing PASS 1 of a two-pass security/quality audit of the tiaa-wpplugin 
repository (github.com/tiaa-forum-org/tiaa-wpplugin). Your job is NOT to judge, 
critique, or recommend — it's to build an accurate, structured map of the codebase 
that a more capable model (Fable) will use to do the actual audit. Do not skip 
files to save time; completeness here determines whether Fable's audit is grounded 
or guessing.

Produce a single markdown file at docs/audits/tiaa-wpplugin-map.md with these 
sections:

## 1. File Inventory
For every PHP file in the repo: path, approximate LOC, one-line purpose, which of 
the plugin's known responsibilities it belongs to (Discourse API integration, 
SSO/cookie auth, /tiaa-logout route, REST endpoints, WP-Cron welcome messages, 
admin settings, other), and — for each function/class within the file — whether 
it has a docblock (y/n). This last column feeds Fable's documentation review, so 
don't skip it.

## 2. Hooks & Entry Points
List every `add_action`, `add_filter`, `register_rest_route`, and 
`register_activation_hook` call found in the repo. For each: file:line, 
hook/route name, callback function, and — for REST routes specifically — the 
exact `permission_callback` value (flag clearly if it's missing, `__return_true`, 
or otherwise looks permissive).

## 3. External Boundaries
Every place the plugin: makes an HTTP request to the Discourse API, reads 
`$_GET`/`$_POST`/`$_REQUEST`/`$_COOKIE`, writes to the database directly (raw 
`$wpdb` queries), or verifies a signature/HMAC (note `hash_equals` usage 
specifically, including the three known locations in SSO/webhook verification).

## 4. Security-Relevant Patterns
Flag (don't fix) every instance of: unsanitized input reaching output or a DB 
query, nonce checks present/absent on state-changing actions, capability checks 
(`current_user_can`) present/absent on admin/REST actions, and any hardcoded 
secrets or credentials.

## 5. Coding Conventions Observed
Naming conventions, docblock format/completeness patterns, WP Coding Standards 
adherence (PHPCS if available — run `phpcs --standard=WordPress` if the tool is 
installed and include a summary of violation counts by file), and any 
inconsistency between files (e.g. some functions namespaced/prefixed, others not; 
some fully documented, others not).

## 6. Dependency & Load Order
How the plugin bootstraps: main plugin file, load order of includes, any 
singleton/class patterns vs procedural style, WP-Discourse plugin API surface it 
depends on.

## 7. Documentation Artifacts
Does the repo have a README? A CHANGELOG? Any docs/ folder? List what exists, 
note what's missing, and note if anything present looks stale (e.g. references 
files, hooks, or config that no longer exist in the repo).

Do not draw conclusions, rank severity, or suggest fixes anywhere in this 
document — that's Fable's job in Pass 2. Just map what exists, precisely, with 
file:line references throughout so Fable can jump straight to the relevant code.

When done, confirm the file was written and give a one-paragraph summary of 
repo size/shape (file count, total LOC, rough REST endpoint count, rough % of 
functions with docblocks) — nothing more.
```
