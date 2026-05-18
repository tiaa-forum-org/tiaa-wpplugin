# tiaa-wpplugin — Claude Code Context
# Last updated: 2026-05-17

## What This Is

WordPress plugin that bridges WordPress and Discourse for tiaa-forum.org.
Core responsibilities: Discourse API calls (invites), SSO return-URL cookie,
member cookie, SSO callback flash suppression, welcome messages, email screening.

Part of the tiaa-v3 project. See umbrella context at `../CLAUDE.md`.

> **Note:** `AI-Context.txt` in this directory is a legacy context file predating
> Claude Code. This `CLAUDE.md` supersedes it and is the authoritative AI context.

---

## File Structure

```
tiaa-wpplugin/
├── tiaa-wpplugin.php          ← entry point; constants, requires, bootstraps
├── lib/                       ← all plugin logic (OO PHP, namespace TIAAPlugin\lib)
│   ├── Discourse.php          ← Discourse API: ping, invite, REST endpoint
│   ├── TiaaReturnUrlCookie.php ← writes tiaa_wp_return_url cookie before SSO
│   ├── TiaaMemberCookie.php   ← sets tiaa_member cookie; adds body class
│   ├── TiaaLoginRedirect.php  ← suppresses WP SSO callback flash
│   ├── TiaaHooks.php          ← WordPress hook registrations
│   ├── TiaaBase.php           ← base class
│   ├── TiaaSiteSettings.php   ← site settings (cookie domain, Discourse URL)
│   ├── WelcomeUtil.php        ← welcome message logic
│   ├── ScreenEmailsUtil.php   ← screened emails DB logic
│   ├── PluginUtil.php         ← shared utility trait
│   └── options-utilities.php  ← WP options helpers
├── admin/                     ← WP admin UI (tabs pattern)
│   ├── admin.php              ← admin bootstrap
│   ├── admin-menu.php         ← menu registration
│   ├── options-page.php       ← tab router
│   ├── settings-validator.php ← validation helpers
│   ├── ConnectionSettings.php ← Discourse connection tab
│   ├── InviteSettings.php     ← invite settings tab
│   ├── GroupInviteSettings.php
│   ├── WelcomeSettings.php    ← welcome messages tab
│   ├── WelcomeDataHandler.php
│   ├── LogSettings.php        ← logging tab
│   ├── ScreenedEmailsSettings.php
│   ├── ScreenedEmailsHandler.php
│   ├── FormHelper.php
│   └── GeneralFileHandler.php
└── vendor_prefixed/           ← prefixed Composer dependencies (autoloaded)
```

---

## Key Constants (defined in main file)

| Constant | Value |
|---|---|
| `TIAA_WP_OPTION_PREFIX` | `tiaa_` |
| `TIAA_CONNECT_GROUP` | `tiaa_connection` |
| `TIAA_INVITE_GROUP` | `tiaa_invite` |
| `TIAA_WELCOME_GROUP` | `tiaa_welcome` |
| `TIAA_LOGGING_GROUP` | `tiaa_logging` |
| `TIAA_SCREENED_EMAIL_GROUP` | `tiaa_screened-emails` |
| `TIAA_HOOK_NAMESPACE` | `tiaa_wpplugin/v1` |
| `TIAA_SCREENED_EMAILS_TABLE` | `{prefix}tiaa_screened_emails` |

REST API endpoint for invites: `POST /tiaa_wpplugin/v1/invite`

---

## Key Classes and Their Responsibilities

### `TiaaReturnUrlCookie`
Writes a `tiaa_wp_return_url` cookie before the SSO redirect so the Discourse
brand header can link back to the exact WP page the user came from.

- Trigger: JS click listener on `.tiaa-sso-trigger` elements (injected via `wp_footer`)
- Cookie TTL: 1 hour (`COOKIE_TTL = 3600`)
- Cookie domain: from `TiaaSiteSettings::get_cookie_domain()` (must be `.tiaa-forum.org` with leading dot)
- Only runs for logged-out users on non-admin pages
- **Elementor:** Add CSS class `tiaa-sso-trigger` to any Sign In / Join button (Advanced tab → CSS Classes)

### `TiaaMemberCookie`
Sets a long-lived `tiaa_member=1` cookie on first logged-in page load.
Persists after logout — intentional (tracks returning members).
Adds `tiaa-returning-member` body class whenever cookie is present, allowing
Elementor to target returning members with conditional display.

- Cookie TTL: 1 year (`YEAR_IN_SECONDS`)
- Cookie domain: from `TiaaSiteSettings::get_cookie_domain()`
- Requires WP-Discourse → SSO → "Enable SSO Provider Logout" ON for bidirectional logout

### `TiaaLoginRedirect`
Eliminates the flash of the WP SSO callback page by issuing a server-side
redirect to Discourse before any HTML renders.

- Hooks `template_redirect` at **priority 20** (after WP-Discourse at priority 10)
- Detects SSO callback by presence of `?sso=…&sig=…` query params
- Reads Discourse URL from WP-Discourse's own `wpdc_options → url` — no duplicate config
- Does not modify the callback URL or WP-Discourse settings

### `Discourse`
Wraps all Discourse API calls. Uses `Api-Key` and `Api-Username` headers.
Credentials come from the Connection settings tab.

---

## Admin UI Pattern

Tabs rendered via `options-page.php`. Route: `?page=tiaa_wpplugin&tab=<slug>`.

Each tab is its own settings class (`ConnectionSettings`, `InviteSettings`, etc.)
following the WP Settings API pattern (register_setting / add_settings_section /
add_settings_field). Admin design mirrors the WP-Discourse plugin layout intentionally
— anyone maintaining WP-Discourse can navigate this plugin.

---

## Code Style

- Namespace: `TIAAPlugin\lib` for lib classes; admin files use the same namespace
- OO PHP following WordPress coding standards
- Docblock author: `Lew Grothe, TIAA Admin Platform sub-team`
- Docblock email: `info@tiaa-forum.org`
- Docblock URL: `https://tiaa-forum.org/contact`
- Conventional commits: `feat:`, `fix:`, `chore:`
- Dates: YYYY-MM-DD

---

## Known Issues

**WelcomeSettings array coercion**
`WelcomeSettings.php` uses `validate_options` instead of `validate_options_blank_ok`
because `group_list` (an array field) gets coerced to a string under the blank-ok
validator. Side effect: Discourse credentials must be re-entered in WelcomeSettings.
The `validator()` method in `FormHandler` needs a deeper audit.

**Logger unreliability**
`TIAAFile.php` lives under `\Analog` but belongs in the plugin library.
Logger is not reliably initialized for all `\PluginUtil` call paths — needs auditing.

---

## Deployment Notes

- Installed as a standard WP plugin (zip upload or directory copy)
- `vendor_prefixed/` must be present (prefixed Composer deps, committed to repo)
- No build step; no `composer install` needed on the server
- Cookie domain setting in TiaaSiteSettings must match the live domain (`.tiaa-forum.org`)