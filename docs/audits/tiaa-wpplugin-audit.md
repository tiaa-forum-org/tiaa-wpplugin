# tiaa-wpplugin — Audit (Pass 2: Fable Judgment Pass)

Judgment-level audit built on Pass 1's map (`docs/audits/tiaa-wpplugin-map.md`).
All line numbers verified directly against the repo at `Version: 0.0.20`
(`tiaa-wpplugin.php:6`) on 2026-08-25. Where the Pass 1 map was stale, that is
noted inline rather than repeated.

**Headline:** The July 2026 incident pattern — missing/weak `permission_callback`
on state-changing REST routes — has been genuinely closed. All four routes in
`lib/TiaaHooks.php` now carry a real capability gate, and the one deliberately
public route (`/invite`) is documented, rate-limited, and screened. No Critical
findings. The remaining risk in that family is **abuse of the public `/invite`
endpoint as an email relay** (S1), not an access-control hole.

**Finding count:** 0 Critical / 1 High / 3 Medium / 7 Low.

---

## 1. Bugs / Security Issues

Ordered per the prompt: incident-pattern items (REST access control, unauthenticated
state change) first, then correctness bugs.

### S1 — Public `/invite` endpoint is an anonymous email-relay surface — **High** — RESOLVED 2026-08-27 (`1508731`, honeypot + per-email throttle; live-verified in production)

`lib/TiaaHooks.php:207-224` registers `POST /tiaa_wpplugin/v1/invite` with
`permission_callback => '__return_true'`. This is intentional (anonymous Elementor
form submissions) and is correctly passed as a callable reference — the inline
comment at `:213-218` is accurate. The handler (`invite_to_discourse()`,
`:303-491`) then calls Discourse's `/invites.json` using the site's **stored admin
API credentials** (`lib/Discourse.php:156`), causing Discourse to email an
arbitrary address supplied in the request body.

The only brake is `invite_rate_limit_exceeded()` (`lib/TiaaHooks.php:264-277`):
5 requests per 60 seconds keyed on `md5(REMOTE_ADDR)` via a transient. Concrete
abuse scenario: an attacker scripts the endpoint from a pool of IPs (or a single
IP at 5/min, ~7,200/day) and drives the forum into sending branded invite emails
to arbitrary recipients — a spam relay that burns the domain's sending
reputation and, because the mail carries the real forum's branding, is a
plausible phishing vector. The limiter also **fails open** when `REMOTE_ADDR` is
empty (`:266-268`) and, as its own docblock warns (`:252-259`), collapses to one
shared bucket the moment a proxy/CDN is placed in front of PHP.

Why this is High and not accepted-as-designed: unlike the login/logout CSRF items
below, this endpoint causes the server to take an outbound, reputation-bearing
action (sending mail to attacker-chosen addresses) with no human in the loop.

**Suggested fix (volunteer-appropriate):** keep the route public but add a cheap
second factor that a real Elementor form has and a script does not — the simplest
being a required, server-checked honeypot field plus a per-*email* transient
throttle (not just per-IP) so the same target can't be mailed repeatedly. A
CAPTCHA/Turnstile token check is stronger but is more than this team should take
on inline with the REST audit. Do **not** add a WP nonce here — an anonymous
caller can't hold one, and the existing comment correctly says so.

### S2 — `/tiaa-logout` accepts cross-site logout with no origin check — **Low**

`lib/TiaaLogoutRoute.php:83-99` logs the current user out on any GET to
`/tiaa-logout`, with no nonce and no Referer/origin check. Any third-party page
can embed `<img src="https://tiaa-forum.org/tiaa-logout">` and force-log-out a
visiting member. This is **documented and deliberately accepted** (`:32-46`):
logout-CSRF is a nuisance, not a compromise, and the route must stay
friction-free to survive a Wordfence `wp-login.php` lockout. The docblock even
names the fix if it's ever revisited (gate on
`TiaaHooks::referred_from_discourse()`, `:131-143`, rather than a nonce). Left as
Low only because a forced logout during an active session is mildly disruptive;
no action needed unless it's being weaponized.

### S3 — Screened-email probe: timing side channel — **Low**

`lib/TiaaHooks.php:358-380` returns a synthetic success body for a screened
email so an anonymous caller can't distinguish it from a real invite by response
shape — this hardening (F6/N2) is real and correct. The residual, already
called out honestly in the comment at `:366-371`, is timing: the screened branch
returns immediately while a genuine invite makes a network round-trip to
Discourse. An attacker can still learn whether an address is on the screen list
by measuring latency. Accepted residual; documenting it here for completeness.
If ever fixed, the cheap approach is to route the screened branch through the
same code path length (or a small random delay), not to remove the uniform body.

### S4 — Screened-email thresholds and alert address are configured but never enforced — **Medium** — RESOLVED 2026-08-28 (`e886cf3`/`8bc2314`, deleted the dead fields rather than implementing the missing alert)

`admin/ScreenedEmailsSettings.php:114-150` renders admin fields for
`email_list` ("Email(s) for Warnings"), `max_hits_per_email`, `max_total_hits`,
and `max_hits_per_day`, and they persist to `wp_options` (defaults in
`lib/options-utilities.php:57-63`). **Nothing reads them.** A repo-wide grep for
these keys finds only the definition, the admin render, and the validator wiring
— no consumer. `lib/ScreenEmailsUtil.php:130-160` (`is_screened_email()`)
increments `hit_count` but never compares it to any threshold and never emails
the alert address.

Why this matters for this audience: an admin who fills in "Max hits per day" and
a warning email reasonably believes the site will throttle or alert on a
hammering address. It will not. That's a false sense of security around exactly
the abuse surface S1 describes. Concrete failure: the S1 attacker probes screened
addresses thousands of times; `hit_count` climbs; no warning email is ever sent
because the feature that would send it doesn't exist.

**Suggested fix:** either (a) implement one consumer — when `is_screened_email()`
crosses `max_hits_per_day`, `wp_mail()` the `email_list` — which is a small,
readable addition; or (b) if the team doesn't want the feature, delete the four
dead fields so the admin UI doesn't promise something absent. Given volunteer
maintainability, (b) is the lower-risk call unless the alert is actually wanted.

### B1 — Screened-emails settings registered with an *invoked* validator, firing a spurious error every page load — **Medium** — RESOLVED 2026-08-25 (`effe9be`)

`admin/ScreenedEmailsHandler.php:91-97`:

```php
register_setting(
    TIAA_SCREENED_EMAIL_GROUP,
    TIAA_SCREENED_EMAIL_GROUP,
    array( $this->validate_options() ),   // ← called, not referenced
);
```

`$this->validate_options()` (`:109-117`) is **called immediately** on every
render of the Screened Emails page. It unconditionally runs
`add_settings_error(..., 'The Discourse URL needs to be set to a valid URL...')`
and returns `false`. Two consequences: (1) a misleading red "Discourse URL needs
to be set" notice appears on the Screened Emails screen every time it loads, even
when the connection is fine; (2) the sanitize callback handed to
`register_setting()` is `array( false )`, which is not callable, so no real
sanitization is attached for that option group. Note this `register_setting()`
also duplicates the one already made by `ScreenedEmailsSettings` for the same
group — the handler shouldn't be registering settings at all.

**Suggested fix:** drop the `register_setting()` call here entirely (the
`ScreenedEmailsSettings` class already owns it), and delete the misleading
`validate_options()` method. Small, no deployment risk.

### B2 — Welcome cron fatals if "Welcome Post ID" is blanked — **Medium** — RESOLVED 2026-08-25 (`effe9be`)

`lib/WelcomeUtil.php:260` reads `$post_id = $this->options['post_id'];` with no
cast, then `:273` passes it to
`Discourse::get_discourse_post_by_id( $post_id, ... )`, whose signature is
`int $post_id` (`lib/Discourse.php:230`). The `min`/`max` day fields immediately
above (`:253-257`) were defensively cast precisely because a blanked settings
field saves as `''` and throws a `TypeError` — but `post_id` was left out of that
fix. If an admin clears the Welcome Post ID field, the next cron run throws
`TypeError: ...must be of type int, string given` and dies. The default is `19`
(`lib/options-utilities.php:73`), so this is latent until someone blanks the
field.

**Suggested fix:** mirror the `min`/`max` treatment —
`$post_id = is_numeric( $this->options['post_id'] ?? '' ) ? (int) $this->options['post_id'] : 0;`
and bail with a logged error when it's 0. Small.

### B3 — `WP_Error` constructed with the message in the code slot — **Low** — RESOLVED 2026-08-28 (`c875105`)

`lib/Discourse.php:233` `return new WP_Error( "Missing post ID." );` and `:265`
`return new WP_Error( "Missing topic ID." );`. `WP_Error::__construct()` takes
`$code` first, `$message` second — so these produce an error whose *code* is the
human sentence and whose *message* is empty. Any caller that logs
`get_error_message()` (e.g. `PluginUtil::log_wp_error()`, `:452`) logs a blank
message. Cosmetic but actively unhelpful during debugging. Fix: pass a short code
plus the message, e.g. `new WP_Error( 'missing_post_id', 'Missing post ID.' )`.

### B4 — Unescaped output in admin ping/preview links and group headings — **Low** — RESOLVED 2026-08-28 (`c875105`)

Most `$hook_url` echoes use `esc_url()`, but three do not:
`admin/ConnectionSettings.php:190` (`echo $hook_url` in the Ping test anchor),
`admin/GroupInviteSettings.php:326` (Ping test anchor), and
`admin/GroupInviteSettings.php:239` (`echo $args['group_name']` in the section
description). `admin/options-page.php:215` also echoes a raw group name into an
`<h3>`. The values are admin-entered (URLs the plugin builds, group slugs typed
in "List of Groups"), so exploitability is low, but it's inconsistent with the
rest of the file and a WPCS escaping violation. Fix: `esc_url()` the hrefs,
`esc_html()` the group names.

### B5 — Dead null-check on `$this` — **Low** — RESOLVED 2026-08-28 (`c875105`)

`lib/WelcomeUtil.php:487` `if ( $this === null ) { return null; }` inside the
non-static instance method `get_recent_log_entries()`. `$this` can never be null
in that context; the branch is unreachable and misleads a reader into thinking a
null-instance path exists. Delete it.

### B6 — `dbDelta()` runs on every front-end page load — **Low** — RESOLVED 2026-08-28 (`c875105`)

`WelcomeUtil` is instantiated unconditionally in `lib/TiaaBase.php:92` (on `init`,
front-end included), and its constructor calls `create_log_table()`
(`lib/WelcomeUtil.php:112`), which runs `CREATE TABLE IF NOT EXISTS ...` through
`dbDelta()` (`:142-143`) on **every** request. `dbDelta()` does a schema
comparison each time — needless work on every anonymous page view. (Contrast
`ScreenEmailsUtil::create_table()`, `lib/ScreenEmailsUtil.php:79`, which at least
guards with a `SHOW TABLES` check first.) The repo has no
`register_activation_hook` anywhere (confirmed — Pass 1 §2), which is *why* table
creation is done lazily on construct. Fix without adding an activation hook: gate
the `dbDelta()` call behind a `SHOW TABLES` check like `ScreenEmailsUtil` does,
or behind an `is_admin() || wp_doing_cron()` condition since the table is only
written by cron and read by admin.

### B7 — API-key validation regex is ReDoS-shaped and rejects uppercase — **Low** — RESOLVED 2026-08-28 (`c875105`)

`admin/settings-validator.php:164` (and its `_blank_ok` twin, `:257`):
`'/^\s*([0-9]*[a-z]*|[a-z]*[0-9]*)*\s*$/'`. The nested-quantifier group
`( ... )*` over alternation of `*`-quantified classes is the classic
catastrophic-backtracking shape; a long non-matching input can spike CPU. Input
is admin-only (low exploitability), but the pattern also silently rejects any
API key containing uppercase letters or symbols — Discourse keys are lowercase
hex today, so it works, but it's a fragile, surprising constraint. Fix: replace
with a simple, anchored, linear check, e.g. `/^[a-z0-9]+$/i` (or just
`sanitize_text_field()` + a length bound), which is both safe and readable.

---

## 2. Overall Quality & Structure

**Organization is sound and consistent.** The `lib/` (runtime) vs `admin/`
(settings UI, loaded only under `is_admin()`, `admin/admin.php:17`) split is
clean, and the reasoning for the one deliberate exception —
`TiaaSiteSettings` living in `lib/` despite being settings-shaped because it
also carries front-end hooks and static helpers — is documented well at
`lib/TiaaSiteSettings.php:46-58`. The admin tabs deliberately mirror
WP-Discourse's own layout (`docs/README-admin.md`) so a WP-Discourse maintainer
can navigate this plugin; that's a genuinely good volunteer-maintainability call.

**Error handling is mostly consistent** thanks to everything funnelling through
`Discourse::handle_discourse_response()` (`lib/Discourse.php:510-572`), which
normalizes both WP_Error and non-200 into a uniform `WP_REST_Response` shape
`{success,status,response,body_response}`. The logging helpers in `PluginUtil`
(`:307-406`) are used pervasively. This is above the norm for a volunteer plugin.

**Things that work but will bite a future maintainer:**

- **Testing-only code paths are wired into production and gated only by a
  comment.** `TiaaHooks::register_test_cron_intervals()`
  (`lib/TiaaHooks.php:611-617`) adds an `every_five_minutes` schedule on *every*
  page load, `WelcomeSettings::render_cron_interval_field()`
  (`admin/WelcomeSettings.php:362-400`) offers Hourly / Every-5-min options, and
  `WelcomeUtil::get_interval_seconds()` (`lib/WelcomeUtil.php:524-531`) scales
  the day thresholds accordingly. All three carry `@todo Remove before
  production` notes — but the removal is manual, spread across three files, and
  easy to forget. A maintainer who picks "Every 5 minutes" not realizing it
  reinterprets "days since joined" as 5-minute periods will silently message the
  wrong members. The in-UI warning at `:378-398` is a good mitigation, but the
  cleaner fix is a single `define('TIAA_ENABLE_TEST_CRON', false)` guard so all
  three sites key off one flag.

- **Tables created lazily on construct, no activation hook** (see B6). Works, but
  a maintainer expecting the usual `register_activation_hook` pattern won't find
  it and may not realize the tables self-heal on load.

- **`get_connection_options_by_group()` fallback logic** (`lib/Discourse.php:583-606`)
  quietly falls back to the Connection tab's credentials for any blank per-group
  field. Correct and intended, but the behavior is invisible unless you read this
  method — a maintainer debugging "why is this invite coming from the wrong
  account" needs to know the blank-means-inherit rule.

- **`OptionsUtilities::options_init()`** (`lib/options-utilities.php:122-161`)
  mixes a by-reference merge (`&$optary`) with a separate raw `$optaryx`
  iteration for the group-list special case. It works, but the reference plus the
  nested dynamic-group discovery is the densest logic in the plugin and has no
  worked example in the docblock.

Procedural/class mixing is minimal (two view templates, one enqueue function, two
singletons) and doesn't create real maintenance risk.

---

## 3. Documentation

**Overall rating: Adequate**, trending Good. This plugin is markedly better
documented than a typical volunteer WP plugin, but there are specific gaps and a
few instances of comment rot.

**Docblocks — mostly present, a few notable holes.** Pass 1 measured ~89.7%
coverage; that matches. The security-relevant gaps are the ones worth fixing
first because these files are exactly where a maintainer needs the "why":

- `lib/TiaaMemberCookie.php` — all three methods undocumented (`:31`, `:36`,
  `:57`). The class header is good, but `maybe_set_member_cookie()` does a
  security-relevant thing (`httponly => false`, `:49`, deliberately, so JS can
  read it) with no method-level note.
- `lib/TiaaReturnUrlCookie.php:32` — `output_script()` undocumented; it emits
  inline JS that writes a cookie, worth a line.
- `lib/TiaaLogoutRoute.php:69` and `lib/TiaaLoginRedirect.php:52` — constructors
  undocumented, though both class headers are excellent (see below).
- `lib/TiaaHooks.php:179` — `register_discourse_ping_route()` alone among the four
  `register_*` route methods has no docblock (the other three do, and two of them
  carry strong security rationale at `:499-508` and `:542-554`).
- `admin/WelcomeSettings.php:79` — `setup_options()` undocumented.

**Inline comments on non-obvious/security logic — strong where it matters.** The
prompt flagged SSO/logout/Discourse-API/regex spots specifically; these are in
good shape:
- `/tiaa-logout` (`lib/TiaaLogoutRoute.php:8-56`) — one of the best-commented
  files in the repo: why it exists, why no nonce, and the *deliberate*
  inconsistency with F5 all spelled out.
- SSO redirect (`lib/TiaaLoginRedirect.php:2-42`) success/failure paths, the
  `allowed_redirect_hosts` requirement, and hook timing are all explained.
- `skip_logout_confirmation()` (`lib/TiaaHooks.php:77-101`) explains the Referer
  scoping and why a forged Referer can't defeat it.
- The rate-limiter's proxy assumption (`lib/TiaaHooks.php:246-259`) and the
  screened-email uniform-response reasoning (`:360-371`) are documented honestly,
  residual side channels and all.
- **HMAC/`hash_equals`:** Pass 1 correctly determined (map §3) there is *no*
  signature-verification surface in this repo — it's delegated to WP-Discourse.
  Verified: `lib/TiaaLoginRedirect.php:126-128` only checks *presence* of `sso`/`sig`.
  Not a documentation gap; the delegation is stated in the class header.

The one **regex that is under-commented** is the API-key pattern
(`admin/settings-validator.php:164`, `:257`) — see B7; it does something
non-obvious (and slightly dangerous) with zero explanation of intent.

**File-header comments — Good.** Nearly every file opens with a header explaining
its role; the `lib/` classes with behavioral subtlety
(`TiaaLogoutRoute`, `TiaaLoginRedirect`, `TiaaSiteSettings`, `TiaaMemberCookie`)
have especially thorough ones. A maintainer can orient from headers alone.

**README / setup docs — present and current.** `README.md` covers purpose,
features, the Wordfence-safe logout route in real depth (`:84-124`), the Invite
Topic ID setup gotcha (`:18-52`), and install-from-Releases guidance. No stale
references to removed files/hooks were found. Two mismatches worth a quick fix:
- **License contradiction:** the plugin header says `GPL v2 or later`
  (`tiaa-wpplugin.php:11`) and most file docblocks say GPL-2.0-or-later, but
  `README.md:166` says "MIT License" and there is **no `LICENSE` file** in the
  repo. Pick one and add the file.
- **README §4 "Web Hook & API Integrations"** (`README.md:74-77`) describes
  "webhooks for real-time processing" — there is no inbound webhook receiver in
  this plugin (Pass 1 §3 confirms; all four routes are outbound-to-Discourse or
  the public invite form). This is aspirational/legacy wording that will mislead
  a maintainer hunting for a webhook handler that doesn't exist.

**Comment rot — a few concrete instances (worse than missing docs, called out
per the prompt):**
- `docs/TESTS.md:1` opens `# NOT ACCURATE AS OF 2/24/24` and then describes
  WP-Discourse's test setup, not this plugin's. It's stale by its own admission
  and should be trimmed to what's true or removed.
- `lib/Discourse.php:96-125` — the `send_discourse_invite()` docblock is
  **duplicated verbatim** (two identical blocks back-to-back), a sign of a
  copy/paste edit that wasn't cleaned up.
- `lib/Discourse.php:431` `@throws Exception` on `getApiResponse()` — the method
  never throws; it returns an error `WP_REST_Response` instead (`:442-451`).
  The return type is also declared `: WP_REST_Response` while the docblock says
  `WP_REST_Response|WP_Error`.
- `lib/TIAAFile.php:99-129` — a large commented-out copy of the previous `init()`
  implementation is retained below the live one. Dead code masquerading as
  reference; delete it (git history has it).
- `admin/InviteSettings.php:32-42` — a malformed/orphaned docblock fragment
  (a stray `* /` at `:32` then a second class description) sits above the real
  `class InviteSettings` declaration; it reads like a merge artifact.
- The `AI-Context.txt` legacy file is flagged as superseded in `CLAUDE.md`, which
  is the right way to handle it — noted as *not* rot since it's explicitly marked.

---

## 4. WordPress Style Consistency

PHPCS could not be run (Pass 1 §5: `phpcs` not installed, no dev-composer). The
following are from manual reading against WPCS / plugin conventions.

- **Escaping — mostly good, isolated misses.** `esc_attr`/`esc_html`/`esc_url`
  are used widely and correctly (e.g. throughout `TiaaSiteSettings` render
  methods). The misses are B4 (`ConnectionSettings.php:190`,
  `GroupInviteSettings.php:326`, `:239`, `options-page.php:215`). One more:
  `admin/FormHelper.php:142` echoes attribute *names* (`$key`) unescaped into the
  input tag — developer-controlled, low risk, but not WPCS-clean.
- **Sanitization — good.** Superglobal reads are sanitized at the boundary
  (`sanitize_key(wp_unslash(...))` in `FormHelper.php:95-98`,
  `options-page.php:113-120`; `esc_url_raw(wp_unslash(...))` in
  `TiaaLogoutRoute.php:92`). `$wpdb` usage is parameterized or allowlisted
  everywhere — note the Pass 1 map is **stale** on `GeneralFileHandler`: it flags
  raw `$_GET['table']` interpolation, but `output_csv_from_database()` now checks
  against a fixed allowlist (`admin/GeneralFileHandler.php:192-197`) *before* the
  interpolated query at `:200-201`. That gap is closed.
- **i18n readiness — inconsistent.** `Requires PHP` etc. aside, the plugin
  declares a `tiaa-wpplugin` text domain in `__()`/`esc_html__()` calls in a few
  places (`LogSettings.php:78`, `TiaaHooks.php:614`, `TIAAFile.php:52`) but the
  overwhelming majority of admin-facing strings are hardcoded English with no
  wrapping (all section descriptions, field labels, button text, the entire
  `options-page.php` tab bar). There is **no `load_plugin_textdomain()` call**
  anywhere and no `Text Domain:`/`Domain Path:` header. So i18n is neither
  consistently applied nor actually wired up. For a single-community
  English-language site this is defensible — but it should be a *decision*, not
  a half-applied pattern. Recommend either committing (add the header + domain
  loader + wrap strings over time) or dropping the stray `__()` wrappers for
  consistency. Given volunteer time, "leave English, stop pretending" is the
  honest low-effort path.
- **Hook naming — consistent.** Custom filters use the `tiaa_validate_*` prefix
  (`settings-validator.php:55-69`); the REST namespace is centralized
  (`TIAA_HOOK_NAMESPACE`, `tiaa-wpplugin.php:44`).
- **Plugin header — mostly complete** (`tiaa-wpplugin.php:2-14`): Name, URI,
  Description, Version, Requires at least, Requires PHP, Author, License, License
  URI all present. Missing: `Text Domain`, `Domain Path` (see i18n above), and
  there's a stray trailing comma in the Description (`:5`).
- **Naming — one documented camelCase island.** `snake_case` methods throughout,
  except `getDBHandle()`/`getTableName()` (`ScreenEmailsUtil.php:101,111`) — Pass
  1 §5 already noted this; not worth churn.
- **Indentation** mixes tabs and spaces within several files
  (`GroupInviteSettings.php`, `WelcomeSettings.php`) — cosmetic, a formatter pass
  would fix it.

---

## 5. Suggested Improvements (Prioritized)

Effort: small (<1hr) / medium (a few hrs + testing) / large (design + testing).
"Native" = has an Elementor/WP-core alternative to custom code, per team
preference.

### Do now — low-risk, high-value; fits alongside the ongoing REST audit
| Item | Ref | Effort | Notes |
|---|---|---|---|
| Fix invoked-validator + duplicate `register_setting` on Screened Emails | B1 | small | Custom code; removes a visible spurious admin error. No deploy risk. |
| Cast/guard `post_id` in Welcome cron | B2 | small | Custom code; prevents a latent cron fatal. |
| Add per-email throttle + honeypot to `/invite` | S1 | medium | Custom code; the one item with real security upside. Honeypot is trivial; per-email transient is small. |
| Decide screened-email thresholds: implement one alert consumer **or** delete the dead fields | S4 | small–medium | Deleting is small + native-ish (just remove fields); implementing the `wp_mail` alert is small custom code. |
| Delete commented-out `init()` block and duplicate/`@throws` docblocks | rot | small | `TIAAFile.php:99-129`, `Discourse.php:96-125,431`. Zero risk. |
| Resolve README license↔header contradiction; add `LICENSE` file | §3 | small | Native (docs). |
| Fix README §4 "webhooks" wording (no receiver exists) | §3 | small | Prevents a maintainer chasing a phantom handler. |
| Add docblocks to the cookie/route methods flagged in §3 | §3 | small | Documentation; cheap, no deploy risk. Write for a WP-comfortable volunteer, not a senior eng. |
| `esc_url`/`esc_html` the four unescaped admin echoes | B4 | small | WPCS cleanliness. |
| Replace ReDoS-shaped API-key regex with linear check | B7 | small | Also stops silently rejecting uppercase keys. |

### Next release — real improvements needing test/design time
| Item | Ref | Effort | Notes |
|---|---|---|---|
| Single `TIAA_ENABLE_TEST_CRON` flag gating all three test-interval sites | §2 | medium | Custom code; removes the "forgot to strip test code" foot-gun before it ships. |
| Gate `dbDelta()` behind a table-existence check or admin/cron context | B6 | small–medium | Custom; removes a per-request schema comparison on the front end. |
| Decide i18n posture (wire it up, or drop the stray `__()` wrappers) | §4 | medium | Native-leaning; consistency either way. |
| Fix mislabeled `WP_Error` code/message pairs | B3 | small | Better logs. |

### Someday / nice-to-have — correct ideas, not worth volunteer time yet
| Item | Ref | Effort | Notes |
|---|---|---|---|
| Add a real `register_activation_hook` for table creation | §2/B6 | medium | Would let table creation stop running on every load, but the lazy pattern works today. |
| Refactor `OptionsUtilities::options_init()` reference-merge for readability | §2 | medium | Works; risk of introducing a regression outweighs the readability gain for now. |
| Formatter pass to normalize tabs/spaces | §4 | small | Cosmetic; do it opportunistically, not as its own task. |
| Remove the camelCase `getDBHandle`/`getTableName` island | §4 | small | Churn for little gain. |
| Trim/rewrite `docs/TESTS.md` (self-admittedly stale) | §3 | small | Or delete it. |

---

## 6. Auditor's Notes

Things this scan could not fully cover, or that the *next* scan should include,
with rationale and rough importance:

1. **No dynamic/runtime testing was possible — this is a static read only.**
   Importance: **high.** S1 (invite relay), S3 (timing side channel), and B2
   (cron fatal) are all best confirmed by actually hitting the endpoints on
   staging. In particular, the real-world exploitability of S1 depends on how the
   fronting infrastructure sets `REMOTE_ADDR` — the very assumption the code's own
   comment (`TiaaHooks.php:252-259`) says can't be verified from PHP. Recommend a
   staging run: curl `/invite` from two source IPs and confirm the rate limiter's
   actual behavior, and blank the Welcome Post ID and fire the cron to confirm B2.

2. **PHPCS / static analysis was unavailable** (no `phpcs`, no dev-composer).
   Importance: **medium.** Section 4 is hand-graded. A real `phpcs
   --standard=WordPress` pass plus PHPStan would likely surface more escaping/i18n
   nits than I found by eye, and would give the team a repeatable gate. Worth
   adding to `bin/` as a dev-only script.

3. **`vendor_prefixed/` (analog/analog) was treated as out of scope** per Pass 1.
   Importance: **medium.** It's third-party code, but it's committed to the repo
   and loaded on every request (`tiaa-wpplugin.php:46`); if that library ever had
   a known CVE, this plugin ships it. A future scan should at least record the
   pinned version against a vulnerability feed. Low likelihood, but it's a supply-
   chain surface the current audit brief excluded.

4. **The WP-Discourse coupling was not audited from the other side.**
   Importance: **medium.** This plugin correctly delegates SSO signature
   verification to WP-Discourse (§3) and depends on the
   `wpdc_sso_client_redirect_after_login` filter and `clear_auth_cookie`
   teardown. The security of the *whole* login/logout story therefore depends on
   WP-Discourse's config (SSO secret, "Sync Logout" on). That's out of this
   repo, but a maintainer reading only this audit might conclude auth is fully
   covered here — it isn't; half of it lives in a plugin this audit didn't look
   at.

5. **`wp-config.php` constants were out of scope** (`TIAA_COOKIE_DOMAIN`,
   `TIAA_ENABLE_TEST_CRON` if adopted). Importance: **low–medium.** Several
   behaviors (cookie domain locking, and the test-cron flag I recommend) are
   config-driven; a config review on each environment would catch a staging value
   accidentally shipped to prod. The plugin can't verify these at runtime.

6. **CSV import trusts row structure loosely.** Importance: **low.**
   `ScreenedEmailsHandler::handle_form_submissions()` (`:160-188`) reads
   arbitrary columns from an uploaded CSV; it's nonce- and `manage_options`-gated
   so the trust boundary is fine, but there's no row-count/size cap — a
   malformed or huge file just loops. Not worth fixing now; noting it so it isn't
   mistaken for an oversight.

---

## If I could only fix three things before the next deploy

1. **Harden `/invite` against relay abuse (S1)** — it's the only finding where an
   *anonymous* request makes the server send attacker-addressed, forum-branded
   email, and the current per-IP/fail-open rate limit is thin. A honeypot field
   plus a per-email throttle is small, readable, and closes the real risk.

2. **Fix the Screened Emails invoked-validator bug (B1)** — every load of that
   admin page currently throws a misleading "Discourse URL needs to be set" error
   and attaches a non-callable sanitizer. It's a one-line deletion, it's visibly
   wrong to any admin using the page, and it's the kind of thing that erodes trust
   in the whole settings screen.

3. **Guard the Welcome-cron `post_id` cast (B2)** — a blank settings field turns
   into a fatal `TypeError` on the next cron run. Volunteers *will* eventually
   clear that field; the fix is the same defensive cast already applied to the
   fields right next to it, so leaving it out is an inconsistency waiting to page
   someone.
