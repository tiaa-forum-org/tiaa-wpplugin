# tiaa-wpplugin — Codebase Map (Pass 1: Mapping Only)

Generated for Fable's Pass 2 judgment audit. This document inventories the
repository; it does not evaluate, critique, rank severity, or recommend
fixes. All line numbers are current as of the commit this was generated
against (`main`, tiaa-wpplugin.php Version: 0.0.20).

Scope: all `*.php` files under the repo root, excluding `vendor_prefixed/`
(third-party dependency code, prefixed via php-prefixer). `vendor_prefixed/`
is noted separately in Section 6 (Dependency & Load Order) since it's a
load-order/dependency fact, not something to inventory function-by-function.

---

## 1. File Inventory

Responsibility buckets used: **Discourse API**, **SSO/Cookie Auth**,
**Logout Route**, **REST Endpoints**, **WP-Cron/Welcome**, **Admin
Settings**, **Screened Emails**, **Logging**, **Other**.

Docblock column = "for each function/class within the file, does it have a
docblock (y/n)" — reported as `Y/N` counts per file (e.g. `5/6` = 5 of 6
functions have a docblock). Full function-by-function detail is in the
per-file breakdown below the table (only files with at least one
undocumented function are broken out; files at 100% are not repeated).

| File | LOC | Purpose | Responsibility | Docblocks (Y/total) |
|---|---|---|---|---|
| `tiaa-wpplugin.php` | 70 | Plugin bootstrap: constants, requires, instantiates `OptionsUtilities`/`TiaaBase` | Other (bootstrap) | n/a (no functions, procedural) |
| `admin/admin.php` | 98 | Admin bootstrap: requires all admin classes, instantiates settings-page classes on `is_admin()`, registers admin enqueue/file-download hooks | Admin Settings | 1/1 |
| `admin/admin-menu.php` | 231 | Registers WP admin menu + submenu pages for each settings tab; per-page `load-{screen}` hooks for connection-status notices | Admin Settings | 8/9 |
| `admin/ConnectionSettings.php` | 194 | "Connection" tab: Discourse URL/API key/username fields (the fallback credentials other tabs default to) | Admin Settings | 6/6 |
| `admin/FormHelper.php` | 196 | Shared form-rendering + options-validation-dispatch helper (`input()`, `validate_options()`, `validate_options_blank_ok()`), singleton | Admin Settings | 7/7 |
| `admin/GeneralFileHandler.php` | 221 | `admin-post` handler serving log file downloads and generated CSV exports (nonce + `manage_options` gated) | Other (file export) | 3/3 |
| `admin/GroupInviteSettings.php` | 420 | "Group Invite" tab(s): per-Discourse-group invite config (URL/API key/username/post ID/topic ID), one settings section per configured group | Admin Settings | 11/11 |
| `admin/InviteSettings.php` | 302 | "Signup" tab: base (non-grouped) invite config, same field set as Group Invite minus per-group looping | Admin Settings | 8/8 |
| `admin/LogSettings.php` | 215 | "Logging" tab: log level + file path config, log-download button | Logging | 7/7 |
| `admin/options-page.php` | 239 | Tab router for the plugin's options page (`?page=tiaa_wpplugin_options&tab=...`), singleton | Admin Settings | 3/3 |
| `admin/ScreenedEmailsHandler.php` | 189 | Screened-emails admin page: add/delete/import-CSV form handling, delegates persistence to `ScreenEmailsUtil` | Screened Emails | 5/5 |
| `admin/ScreenedEmailsSettings.php` | 216 | "Screened Emails" tab: alert-email + rate-limit threshold settings fields | Screened Emails | 8/8 |
| `admin/settings-validator.php` | 538 | `SettingsValidator` class: all `tiaa_validate_*`/`tiaa_validate_*_blank_ok` filter callbacks (URL, API key, username, post ID, group list, email, cron interval, etc.) | Admin Settings | 17/18 |
| `admin/views/screened-emails-view.php` | 128 | Procedural view template for the Screened Emails admin page (form markup, nonce fields) | Screened Emails | n/a (no functions, procedural) |
| `admin/views/welcome-data-view.php` | 127 | Procedural view template for the Welcome cron status dashboard (form markup, nonce fields) | WP-Cron/Welcome | n/a (no functions, procedural) |
| `admin/WelcomeDataHandler.php` | 90 | Handles the Welcome dashboard's cron-control form submissions (start/stop/run-once/status) | WP-Cron/Welcome | 3/3 |
| `admin/WelcomeSettings.php` | 542 | "Welcome" tab: Discourse creds, scan thresholds, message post ID, cron interval selector, group-exclusion list; registers the `update_option_{group}` reschedule hook | WP-Cron/Welcome | 18/19 |
| `lib/Discourse.php` | 606 | All Discourse HTTP API calls: ping, send invite, get post/topic by ID, get recent members, get user by name, send personal message, low-level request/response plumbing, connection-options resolution | Discourse API | 13/13 |
| `lib/options-utilities.php` | 161 | `OptionsUtilities`: defines default option-group shapes, `options_init()` hydrates them from `wp_options` on `plugins_loaded` (including dynamic per-group discovery from `group_list`) | Admin Settings (options infra) | 2/2 |
| `lib/PluginUtil.php` | 511 | Shared trait: options accessors, `resolve_configured_group_name()`, REST callback bodies for ping/get-post/get-topic, message parsing, full logging API (`log_error`, `log_debug`, etc.), response-formatting helpers | Other (shared trait, spans REST/Logging/Discourse) | 20/20 |
| `lib/ScreenEmailsUtil.php` | 160 | Screened-emails DB table management (`CREATE TABLE IF NOT EXISTS`) and lookup (`is_screened_email()`) | Screened Emails | 5/5 |
| `lib/TiaaBase.php` | 111 | Plugin entry class; `init` (priority 3) hook: admin-bar suppression for non-admins, 30-day auth cookie extension | SSO/Cookie Auth | 2/2 |
| `lib/TIAAFile.php` | 132 | Custom Analog log handler (file-based, string log levels instead of int); TIAA-specific, not part of the upstream `analog/analog` package | Logging | 0/1 (`init()` undocumented; large commented-out original implementation also present, lines ~99-131) |
| `lib/TiaaHooks.php` | 617 | REST route registration (ping, invite, get-post, get-topic) + callbacks; SSO logout-confirmation skip; SSO login redirect filter; `tiaa-member` body class filter; invite rate limiting; test cron interval registration | REST Endpoints / Discourse API | 12/13 |
| `lib/TiaaLoginRedirect.php` | 129 | Detects failed SSO callback (no redirect fired by WP-Discourse) and redirects to Discourse login with `sso_failed=1`; allows Discourse host in `wp_safe_redirect()` | SSO/Cookie Auth | 3/4 |
| `lib/TiaaLogoutRoute.php` | 120 | `/tiaa-logout` route: logs out via core `wp_logout()` without touching `wp-login.php` (Wordfence lockout workaround) | Logout Route | 2/3 |
| `lib/TiaaMemberCookie.php` | 62 | Sets long-lived `tiaa_member` cookie on first logged-in page load; adds `tiaa-returning-member` body class | SSO/Cookie Auth | 0/3 |
| `lib/TiaaReturnUrlCookie.php` | 68 | Writes `tiaa_wp_return_url` cookie via inline JS on `.tiaa-sso-trigger` click, before SSO redirect | SSO/Cookie Auth | 0/2 |
| `lib/TiaaSiteSettings.php` | 400 | "Site Settings" tab: cookie domain, contact email, funding level, forum stats; front-end shortcodes; `wp_head`/`wp_footer` output for contribute-button styling and go-to-forum link | Admin Settings / Other | 8/13 |
| `lib/WelcomeUtil.php` | 531 | Welcome-message cron: schedule/unschedule, `run_cron()` (fetch recent members, filter, send), DB log table creation/logging, cron-status rendering | WP-Cron/Welcome | 12/14 |

**Total: 28 PHP files (+ view templates with no functions), 7,624 LOC.**

### Per-file docblock detail (files with at least one undocumented function)

- **`admin/admin-menu.php`**: `site_settings_tab()` (line 227) — no docblock.
- **`admin/settings-validator.php`**: `validate_file_path()` (line 84) — no docblock.
- **`admin/WelcomeSettings.php`**: `setup_options()` (line 79) — no docblock.
- **`lib/TIAAFile.php`**: `init()` (line 38) — no docblock. File also contains a large commented-out block (lines ~99-131) reproducing an earlier version of `init()`.
- **`lib/TiaaHooks.php`**: `register_discourse_ping_route()` (line 179) — no docblock.
- **`lib/TiaaLoginRedirect.php`**: `__construct()` (line 52) — no docblock.
- **`lib/TiaaLogoutRoute.php`**: `__construct()` (line 69) — no docblock.
- **`lib/TiaaMemberCookie.php`**: `__construct()` (31), `maybe_set_member_cookie()` (36), `add_returning_member_class()` (57) — none documented (0/3).
- **`lib/TiaaReturnUrlCookie.php`**: `__construct()` (28), `output_script()` (32) — none documented (0/2).
- **`lib/TiaaSiteSettings.php`**: `__construct()` (92), `register_settings()` (100), `render_cookie_domain_field()` (121), `render_contact_email_field()` (148), `render_funding_level_field()` (163), `render_stats_fields()` (187), `sanitize_funding_level()` (287) — 5/13 undocumented.
- **`lib/WelcomeUtil.php`**: `enable_cron()` (342), `disable_cron()` (345) — undocumented (12/14 overall).

**Overall: 183 of 204 functions/methods repo-wide have a docblock (~89.7%).**

---

## 2. Hooks & Entry Points

### `add_action`

| File:Line | Hook | Callback |
|---|---|---|
| `admin/admin.php:47` | `admin_enqueue_scripts` | `TIAAPlugin\Admin\tiaa_enqueue_admin_scripts` |
| `admin/admin.php:49` | `admin_post_tiaa_secure_file` | `GeneralFileHandler::tiaa_serve_file` |
| `admin/admin-menu.php:59` | `admin_menu` | `AdminMenu::add_menu_pages` |
| `admin/admin-menu.php:84` | `load-{connection settings screen}` | `FormHelper::connection_status_notice` |
| `admin/admin-menu.php:94` | `load-{connection_settings}` | `FormHelper::connection_status_notice` |
| `admin/admin-menu.php:104` | `load-{invite_settings}` | `FormHelper::connection_status_notice` |
| `admin/admin-menu.php:114` | `load-{group_invite_settings}` | `FormHelper::connection_status_notice` |
| `admin/admin-menu.php:127` | `load-{screened_email_settings}` | `FormHelper::connection_status_notice` |
| `admin/admin-menu.php:137` | `load-{welcome_settings}` | `FormHelper::connection_status_notice` |
| `admin/admin-menu.php:147` | `load-{logging_settings}` | `FormHelper::connection_status_notice` |
| `admin/ConnectionSettings.php:49` | `admin_init` | `ConnectionSettings::register_connection_settings` |
| `admin/FormHelper.php:66` | `admin_init` | `FormHelper::setup_options` |
| `admin/GroupInviteSettings.php:61` | `admin_init` | `GroupInviteSettings::register_group_invite_settings` |
| `admin/InviteSettings.php:76` | `admin_init` | `InviteSettings::register_invite_settings` |
| `admin/LogSettings.php:62` | `admin_init` | `LogSettings::register_log_settings` |
| `admin/ScreenedEmailsSettings.php:92` | `admin_init` | `ScreenedEmailsSettings::register_screened_emails_settings` |
| `admin/settings-validator.php:52` | `admin_init` | `SettingsValidator::setup_options` |
| `admin/WelcomeSettings.php:74` | `admin_menu` | `WelcomeSettings::register_admin_menu` |
| `admin/WelcomeSettings.php:75` | `admin_init` | `WelcomeSettings::register_settings` |
| `admin/WelcomeSettings.php:76` | `admin_init` | `WelcomeSettings::setup_options` |
| `admin/WelcomeSettings.php:212` | `update_option_tiaa_welcome` | `WelcomeSettings::reschedule_on_interval_change` |
| `lib/Discourse.php:54` | `init` | `Discourse::discourse_init` |
| `lib/options-utilities.php:108` | `plugins_loaded` | `OptionsUtilities::options_init` |
| `lib/TiaaBase.php:62` | `init` (priority 3) | `TiaaBase::initialize_plugin` |
| `lib/TiaaHooks.php:45` | `rest_api_init` | `TiaaHooks::initialize_hooks` |
| `lib/TiaaHooks.php:47` | `login_init` | `TiaaHooks::skip_logout_confirmation` |
| `lib/TiaaLoginRedirect.php:54` | `template_redirect` (priority 20) | `TiaaLoginRedirect::maybe_redirect_after_sso` |
| `lib/TiaaLogoutRoute.php:70` | `init` | `TiaaLogoutRoute::maybe_handle_logout` |
| `lib/TiaaMemberCookie.php:32` | `init` (priority 20) | `TiaaMemberCookie::maybe_set_member_cookie` |
| `lib/TiaaReturnUrlCookie.php:29` | `wp_footer` | `TiaaReturnUrlCookie::output_script` |
| `lib/TiaaSiteSettings.php:93` | `admin_init` | `TiaaSiteSettings::register_settings` |
| `lib/TiaaSiteSettings.php:96` | `wp_footer` | `TiaaSiteSettings::output_forum_button_script` |
| `lib/TiaaSiteSettings.php:97` | `wp_head` | `TiaaSiteSettings::output_contribute_color_style` |
| `lib/WelcomeUtil.php:103` | `TIAA_CRON_HOOK` (custom cron hook) | `WelcomeUtil::static_run_cron` |

No `register_activation_hook` or `register_deactivation_hook` calls found anywhere in the repo.

### `add_filter`

| File:Line | Hook | Callback |
|---|---|---|
| `admin/settings-validator.php:55` | `tiaa_validate_url` | `SettingsValidator::validate_url` |
| `admin/settings-validator.php:56` | `tiaa_validate_api_key` | `SettingsValidator::validate_api_key` |
| `admin/settings-validator.php:57` | `tiaa_validate_post_id` | `SettingsValidator::validate_post_id` |
| `admin/settings-validator.php:58` | `tiaa_validate_username` | `SettingsValidator::validate_username` |
| `admin/settings-validator.php:59` | `tiaa_validate_url_blank_ok` | `SettingsValidator::validate_url_blank_ok` |
| `admin/settings-validator.php:60` | `tiaa_validate_api_key_blank_ok` | `SettingsValidator::validate_api_key_blank_ok` |
| `admin/settings-validator.php:61` | `tiaa_validate_username_blank_ok` | `SettingsValidator::validate_username_blank_ok` |
| `admin/settings-validator.php:62` | `tiaa_validate_post_id_blank_ok` | `SettingsValidator::validate_post_id_blank_ok` |
| `admin/settings-validator.php:63` | `tiaa_validate_group_list` | `SettingsValidator::validate_group_list` |
| `admin/settings-validator.php:64` | `tiaa_validate_group_list_blank_ok` | `SettingsValidator::validate_group_list_blank_ok` |
| `admin/settings-validator.php:65` | `tiaa_validate_email` | `SettingsValidator::validate_email` |
| `admin/settings-validator.php:66` | `tiaa_validate_email_list` | `SettingsValidator::validate_email_list` |
| `admin/settings-validator.php:67` | `tiaa_validate_screen_list` | `SettingsValidator::validate_screen_list` |
| `admin/settings-validator.php:68` | `tiaa_validate_file_path` | `SettingsValidator::validate_file_path` |
| `admin/settings-validator.php:69` | `tiaa_validate_cron_interval` | `SettingsValidator::validate_cron_interval` |
| `lib/TiaaBase.php:95` | `auth_cookie_expiration` | inline closure (extends auth cookie to 30 days) |
| `lib/TiaaHooks.php:46` | `cron_schedules` | `TiaaHooks::register_test_cron_intervals` |
| `lib/TiaaHooks.php:48` | `wpdc_sso_client_redirect_after_login` | `TiaaHooks::redirect_after_sso_login` |
| `lib/TiaaHooks.php:49` | `body_class` | `TiaaHooks::add_member_body_class` |
| `lib/TiaaMemberCookie.php:33` | `body_class` | `TiaaMemberCookie::add_returning_member_class` |
| `lib/TiaaLoginRedirect.php:55` | `allowed_redirect_hosts` | `TiaaLoginRedirect::allow_discourse_host` |

### `register_rest_route` (all in `lib/TiaaHooks.php`)

| File:Line | Route | Method | `permission_callback` |
|---|---|---|---|
| `lib/TiaaHooks.php:179-196` | `tiaa_wpplugin/v1/tiaa_discourse_ping` | GET | `function() { return current_user_can('manage_options'); }` |
| `lib/TiaaHooks.php:207-224` | `tiaa_wpplugin/v1/invite` | POST | **`__return_true`** — flagged: passed as a callable reference (not invoked), with an inline comment stating this is intentional (route is hit by anonymous front-end visitors via Elementor forms) |
| `lib/TiaaHooks.php:511-540` | `tiaa_wpplugin/v1/get_discourse_post` | GET | `function() { return current_user_can('manage_options'); }` — `post_id` and `option_group` args both `required => true` |
| `lib/TiaaHooks.php:556-585` | `tiaa_wpplugin/v1/get_discourse_topic` | GET | `function() { return current_user_can('manage_options'); }` — `topic_id` and `option_group` args both `required => true` |

`register_activation_hook`/`register_deactivation_hook`: none found.

---

## 3. External Boundaries

### HTTP requests to the Discourse API

Both call sites are in `lib/Discourse.php`'s `getApiResponse()` (the single low-level request function all other `Discourse::*` methods funnel through):

- `lib/Discourse.php:437` — `wp_remote_get( $baseUrl . $apiEndpoint, $args )`
- `lib/Discourse.php:440` — `wp_remote_post( $baseUrl . $apiEndpoint, $args )`

Higher-level callers into `getApiResponse()`: `ping_discourse_server()` (84), `send_discourse_invite()` (126, plus an internal duplicate-check lookup around 178-190), `get_discourse_post_by_id()` (230), `get_discourse_topic_by_id()` (262), `get_recent_members()` (289), `get_user_byname()` (358), `send_personal_message()` (392).

### `$_GET` / `$_POST` / `$_REQUEST` / `$_COOKIE` reads

| File:Line | Superglobal | Context |
|---|---|---|
| `admin/FormHelper.php:95-98` | `$_GET['tab']`, `$_GET['page']` | current-tab detection, `sanitize_key(wp_unslash(...))` |
| `admin/GeneralFileHandler.php:69-70` | `$_GET['_wpnonce']` | nonce check before file serve |
| `admin/GeneralFileHandler.php:85` | `$_GET['type']` | file-type switch (`log` / `csv`) |
| `admin/GeneralFileHandler.php:101` | `$_GET['table']` | table name for CSV export (see Section 4) |
| `admin/options-page.php:113-120` | `$_GET['page']`, `$_GET['tab']` | tab routing, `sanitize_key(wp_unslash(...))` |
| `admin/ScreenedEmailsHandler.php:139-160` | `$_POST['submit_email']`, `$_POST['email']`, `$_POST['notes']`, `$_POST['delete_email_id']`, `$_POST['import_csv']`, `$_FILES['csv_file']` | add/delete/import form handling, behind `check_admin_referer()` |
| `admin/WelcomeDataHandler.php:80-86` | `$_POST['cron_start']`, `['cron_stop']`, `['cron_do_run']`, `['get_cron_status']` | cron dashboard button dispatch, behind `check_admin_referer()` |
| `lib/TiaaHooks.php:71` | `$_COOKIE['tiaa_member']` | body-class filter |
| `lib/TiaaHooks.php:109-115` | `$_REQUEST['action']`, `['_wpnonce']`, `['redirect_to']` | SSO logout-confirmation skip condition |
| `lib/TiaaLoginRedirect.php:127` | `$_GET['sso']`, `$_GET['sig']` | failed-SSO-callback detection |
| `lib/TiaaLogoutRoute.php:91-92` | `$_GET['redirect_to']` | logout redirect target, `esc_url_raw(wp_unslash(...))` then `wp_validate_redirect()` downstream |
| `lib/TiaaMemberCookie.php:40,54,58` | `$_COOKIE['tiaa_member']` | cookie-presence check and same-request read-after-write |

### Raw `$wpdb` usage

| File:Line | Call | Notes |
|---|---|---|
| `tiaa-wpplugin.php:49` | `$wpdb->prefix . 'tiaa_screened_emails'` | defines `TIAA_SCREENED_EMAILS_TABLE` constant |
| `lib/ScreenEmailsUtil.php:79` | `$this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'")` | table-existence check, `$table_name` is the constant above, not user input |
| `lib/ScreenEmailsUtil.php:88` | `$this->wpdb->get_charset_collate()` | table creation |
| `lib/ScreenEmailsUtil.php:138-139` | `$this->wpdb->get_row($this->wpdb->prepare(...))` | parameterized |
| `lib/ScreenEmailsUtil.php:148` | `$this->wpdb->update(...)` | uses wpdb's built-in escaping |
| `admin/GeneralFileHandler.php:200-201` | `$full_table_name = $wpdb->prefix . $table_name;` then `$wpdb->get_results("SELECT * FROM $full_table_name", ARRAY_A)` | `$table_name` traces back to `$_GET['table']` (line 101 in the same file) — raw string interpolation into the SQL, not `$wpdb->prepare()` |
| `lib/WelcomeUtil.php:129` | `$wpdb->get_charset_collate()` | table creation |
| `lib/WelcomeUtil.php:145-146` | `$wpdb->last_error` | error logging after table creation |
| `lib/WelcomeUtil.php:391,393` | `$wpdb->prefix . self::TIAA_WELCOME_TABLE`, `$wpdb->insert(...)` | uses wpdb's built-in escaping |
| `lib/WelcomeUtil.php:440-442` | `$wpdb->prefix . ...`, `$wpdb->get_row($wpdb->prepare(...))` | parameterized |

### `hash_equals` usage

**None found anywhere in the repo** (searched all `*.php` outside `vendor_prefixed/`).

This audit's Pass 1 prompt expected three known `hash_equals` locations in
SSO/webhook signature verification. Checked directly (2026-08-25): that
expectation doesn't apply to this repo's actual architecture. The two
SSO-related files here don't verify signatures at all --
`lib/TiaaLoginRedirect.php`'s `is_sso_callback()` only checks that `sso`
and `sig` query params are *present* (`!empty($_GET['sso']) &&
!empty($_GET['sig'])`); its own docblock states the actual signature
verification happens inside the separate **WP-Discourse plugin**
(`parse_request()` at `init` priority 5, not part of this repo), and this
class only reacts to the outcome (whether WP-Discourse's verification
left the user logged in) to decide where to redirect.
`lib/TiaaReturnUrlCookie.php` doesn't touch signatures at all. There is
also no inbound Discourse-to-WordPress webhook receiver anywhere in this
plugin (all 4 REST routes in Section 2 are either outbound WP-to-Discourse
calls or the public `/invite` form endpoint), so there's no
webhook-signature surface in this codebase's own code to begin with.
Not a gap in `tiaa-wpplugin` -- signature verification is correctly
delegated to WP-Discourse rather than reimplemented here. Fable's Pass 2
should not treat the absence of `hash_equals` as a finding.

---

## 4. Security-Relevant Patterns

*(Inventory only — flagged per the prompt's instruction, not assessed for severity or fixed.)*

### Unsanitized input reaching output or a DB query

- `admin/GeneralFileHandler.php:101` → `:200-201` — `$table_name` from `$_GET['table']` is concatenated directly into a raw SQL string (`"SELECT * FROM $full_table_name"`) rather than passed through `$wpdb->prepare()`. Route is gated by nonce (`:69-70`) and `current_user_can('manage_options')` (`:78`) before reaching this code.

### Nonce checks present/absent on state-changing actions

| Action | File | Nonce mechanism |
|---|---|---|
| Screened email add | `admin/ScreenedEmailsHandler.php:140` | `check_admin_referer('add_screened_email', '_wpnonce_add_email')`; field emitted at `admin/views/screened-emails-view.php:30` |
| Screened email delete | `admin/ScreenedEmailsHandler.php:155` | `check_admin_referer('delete_screened_email', '_wpnonce_delete_email')`; field at `screened-emails-view.php:118` |
| Screened email CSV import | `admin/ScreenedEmailsHandler.php:161` | `check_admin_referer('import_screened_emails_csv', '_wpnonce_import_csv')`; field at `screened-emails-view.php:52` |
| Welcome cron controls (start/stop/run/status) | `admin/WelcomeDataHandler.php:79` | single `check_admin_referer('tiaa_welcome_cron_action', '_wpnonce_welcome_cron')` guarding all four `$_POST` branches; field emitted 4x in `admin/views/welcome-data-view.php` (lines 33, 39, 45, 51) |
| Secure file download (`admin-post`) | `admin/GeneralFileHandler.php:69-70` | manual `wp_verify_nonce($_GET['_wpnonce'], 'admin_post_tiaa_secure_file')`; nonce minted at `admin/LogSettings.php:183` and `admin/views/screened-emails-view.php:70` |
| `/tiaa-logout` route | `lib/TiaaLogoutRoute.php` | **no nonce check** — `:83` `maybe_handle_logout()` has a comment (`:~30`, referenced from the class docblock) stating this is deliberate: the logout link it replaces never had one either |
| WP REST `/invite` route | `lib/TiaaHooks.php:207-224` | no nonce — route uses `permission_callback => __return_true` by design (anonymous front-end form submissions); nonce would be meaningless for an anonymous caller |
| WP REST `/tiaa_discourse_ping`, `/get_discourse_post`, `/get_discourse_topic` | `lib/TiaaHooks.php` | no nonce in the route registration itself, but every admin-rendered link to these routes appends `&_wpnonce=` + `wp_create_nonce('wp_rest')` (7 call sites: `GroupInviteSettings.php:323,362,411`; `InviteSettings.php:222,254,294`; `WelcomeSettings.php:282,483`) — WP REST's built-in `X-WP-Nonce`/`_wpnonce` cookie-auth check applies via the standard REST dispatch pipeline, not an explicit in-code check in this plugin |
| Options-page settings forms (Connection, Invite, Group Invite, Log, Screened Emails, Welcome tabs) | via `settings_fields()` in each `render_settings_page()`/`display()` | WP Settings API's own built-in nonce (`settings_fields()` emits `_wpnonce` + `option_page` hidden fields; verified by `options.php` core, not this plugin's own code) |

### Capability checks (`current_user_can`) present/absent on admin/REST actions

| File:Line | Check | Context |
|---|---|---|
| `admin/ScreenedEmailsHandler.php:68` | `manage_options` | render_screened_emails_page gate |
| `admin/admin-menu.php:169,183,196,209,222,228` | `manage_options` | per-tab menu visibility (6 call sites) |
| `admin/GeneralFileHandler.php:78` | `manage_options` | explicit second check after nonce verify in `tiaa_serve_file()`, commented as defense-in-depth |
| `admin/WelcomeDataHandler.php:58` | `manage_options` | gate before processing any cron-control `$_POST` branch |
| `lib/TiaaBase.php:107` | `is_user_logged_in() && !current_user_can('administrator')` | admin-bar suppression condition (inverse: hides bar for logged-in non-admins) |
| `lib/TiaaHooks.php:183,518,563` | `manage_options` | `permission_callback` for `/tiaa_discourse_ping`, `/get_discourse_post`, `/get_discourse_topic` |
| `lib/TiaaHooks.php:207-224` (`/invite` route) | **absent** — `permission_callback => __return_true`, explicitly commented as intentional (public route for anonymous invite form submissions) |

### Hardcoded secrets or credentials

None found in the repo's PHP source. Discourse API keys/usernames are stored in `wp_options` (via the Settings API, per-tab), not hardcoded in code. `composer.json` and `wp-config.php` are outside this repo's scope.

---

## 5. Coding Conventions Observed

**PHPCS:** not available in this environment (`phpcs` not found on `PATH`, no `vendor/bin/phpcs`, no local Composer dev-dependency install present). Section 5's static-analysis-tool request could not be fulfilled; the observations below are from manual reading only.

**Naming conventions:**
- Classes: PascalCase (`TiaaHooks`, `ScreenEmailsUtil`, `WelcomeSettings`), consistent throughout.
- Namespaces: `TIAAPlugin\lib` for `lib/*.php` classes; `TIAAPlugin\Admin` for `admin/*.php` classes (both consistent).
- Methods/functions: `snake_case` throughout — consistent, including on otherwise-PascalCase-styled classes.
- Constants: `TIAA_*` `SCREAMING_SNAKE_CASE`, defined centrally in `tiaa-wpplugin.php` (option group name constants) plus some class-level `const`s (e.g. `TiaaHooks::INVITE_RATE_LIMIT_MAX`).
- One exception to snake_case observed: `lib/PluginUtil.php`'s `option_array_name()` uses a mixed style internally, and `getDBHandle()`/`getTableName()` in `lib/ScreenEmailsUtil.php:101,111` use camelCase — inconsistent with the snake_case convention used elsewhere in the same file (`is_screened_email()`, `create_table()`).

**Docblock format:** Standard PHPDoc (`@since`, `@param`, `@return`, occasional `@access`, `@see`, `@todo`). Format is consistent where present. Completeness varies by file — see Section 1's per-file breakdown (183/204 functions documented, ~89.7%); the least-documented files are `lib/TiaaMemberCookie.php` (0/3), `lib/TiaaReturnUrlCookie.php` (0/2), and `lib/TiaaSiteSettings.php` (8/13).

**Indentation:** Mixed tabs/spaces observed within the same files in several places (e.g. `admin/GroupInviteSettings.php`, `admin/WelcomeSettings.php` mix tab-indented and space-indented blocks — visible as inconsistent alignment when comparing adjacent methods in the same class).

**Other inconsistencies observed:**
- Some REST/DB-facing methods have defensive `empty()`/type checks before use, others do not (compare `PluginUtil::do_ping_discourse_server()`'s upfront param checks against methods that index request params directly).
- `lib/TIAAFile.php` retains a large commented-out block (lines ~99-131) duplicating an earlier version of its own `init()` method.
- `lib/TiaaHooks.php:179` (`register_discourse_ping_route()`) has no docblock while its three sibling `register_*` methods in the same file (`register_invite_route()`, `register_get_discourse_post_by_id()`, `register_get_discourse_topic_by_id()`) all do.

---

## 6. Dependency & Load Order

**Bootstrap sequence** (`tiaa-wpplugin.php`):

1. `ABSPATH` guard (exit if accessed directly).
2. Path/URL constants defined (`TIAA_PLUGIN_PATH`, `TIAA_PLUGIN_URL`, `TIAA_PLUGIN_LOGO*`).
3. Option-group-name constants defined (`TIAA_CONNECT_GROUP`, `TIAA_INVITE_GROUP`, `TIAA_GROUP_LIST_GROUP`, `TIAA_GROUP_INVITE_GROUP`, `TIAA_WELCOME_GROUP`, `TIAA_WELCOME_GROUP_CRON`, `TIAA_LOGGING_GROUP`, `TIAA_SCREENED_EMAIL_GROUP`, `TIAA_HOOK_NAMESPACE`).
4. `require_once vendor_prefixed/autoload.php` — this in turn `require_once`s the entire prefixed `analog/analog` library (18 files) plus `lib/TIAAFile.php` itself (see `vendor_prefixed/autoload.php:4-46`) — i.e. `TIAAFile.php`, though physically in `lib/`, is loaded via the `vendor_prefixed` autoloader rather than `tiaa-wpplugin.php`'s own `require_once` list.
5. `TIAA_SCREENED_EMAILS_TABLE` constant defined (needs `global $wpdb` first).
6. `lib/` files required in sequence: `options-utilities.php`, `PluginUtil.php`, `Discourse.php`, `TiaaBase.php`, `TiaaHooks.php`, `ScreenEmailsUtil.php`, `WelcomeUtil.php`, `TiaaSiteSettings.php`, `TiaaReturnUrlCookie.php`, `TiaaMemberCookie.php`, `TiaaLoginRedirect.php`, `TiaaLogoutRoute.php`. (One commented-out require, `lib/utilities.php`, at line 54.)
7. `new \TIAAPlugin\lib\OptionsUtilities()` — registers the `plugins_loaded` hook that hydrates all option groups.
8. `require_once admin/admin.php` — itself conditionally (`if (is_admin())`) requires and instantiates all `admin/*.php` classes (see Section 2's admin-menu/settings `add_action('admin_init', ...)` entries) plus registers `admin_enqueue_scripts` and the secure-file `admin-post` hook.
9. `new TIAAPlugin\lib\TiaaBase()` — registers the `init` (priority 3) hook.

**Class/pattern style:** Almost entirely OO (namespaced classes), one shared trait (`PluginUtil`, `use`d by most `lib/` and `admin/` classes for options access + logging), one procedural top-level function (`tiaa_enqueue_admin_scripts()` in `admin/admin.php`), two singleton classes (`FormHelper::get_instance()`, `OptionsPage::get_instance()`), two procedural view templates (`admin/views/*.php`, `include`d rather than classes).

**WP-Discourse plugin API surface depended on:** `wpdc_sso_client_redirect_after_login` filter (consumed at `lib/TiaaHooks.php:48,158`) is the only direct hook-name dependency on the separate WP-Discourse plugin found in this repo's PHP. No `require`/`use` of WP-Discourse classes directly — the coupling is hook-based only.

**`vendor_prefixed/` dependency tree:** prefixed copy of `analog/analog` (18 files under `vendor_prefixed/analog/analog/lib/`), loaded via `vendor_prefixed/autoload.php`, plus this repo's own `lib/TIAAFile.php` appended to that same autoload file. `composer.json` declares `analog/analog: ^1.0` and a `php-prefixer` config block (namespace prefix `TIAAPlugin`, global-scope prefix `TIAAPlugin_`) — the mechanism used to regenerate `vendor_prefixed/` from a plain `composer install`, not something the runtime plugin itself invokes.

---

## 7. Documentation Artifacts

| Artifact | Present? | Notes |
|---|---|---|
| `README.md` (repo root) | Yes | Overview, feature list, installation instructions (points at GitHub Releases, not the repo's own zip download), contributing/license sections. Includes an "Invite Topic ID" setup subsection under the invite-management feature. |
| `CHANGELOG.md` | **No** — not present anywhere in the repo. |
| `docs/README-admin.md` | Yes | Short doc describing the admin tab layout pattern (tabs mirror WP-Discourse's own admin layout intentionally). |
| `docs/README-known-bugs.md` | Yes | Running log of known bugs/investigations, both open and resolved (resolved entries marked with strikethrough + date). |
| `docs/TESTS.md` | Yes | Present; not read in detail as part of this mapping pass (out of scope for the seven requested sections, noted here per "list what exists"). |
| `docs/audits/` | Yes | Contains this map's own source prompt (`audit-prompt-01-sonnet-mapping-pass.md`) and the Pass 2 prompt (`audit-prompt-02-fable-judgment-pass.md`). |
| `CLAUDE.md` (repo root) | Yes (not part of the seven requested sections, but present) | AI-agent context file: architecture summary, key classes, known issues history. Not evaluated for staleness here since it falls outside Section 7's literal file list, but noted for completeness. |

**Staleness check:** no reference to a file, hook, or config that no longer exists in the repo was found in `README.md`, `docs/README-admin.md`, or `docs/README-known-bugs.md` during this pass. (This was a light check, not exhaustive line-by-line cross-referencing — flagged here rather than asserted with full confidence.)
