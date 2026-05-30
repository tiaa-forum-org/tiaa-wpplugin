# tiaa-wpplugin — Claude Code Context
# Last updated: 2026-05-30

## What This Is

WordPress plugin that bridges WordPress and Discourse for tiaa-forum.org.
Core responsibilities: Discourse API calls (invites), SSO return-URL cookie,
member cookie, welcome messages, email screening.

**SSO model:** Discourse is the identity provider; WP is a client.
WP-Discourse runs in SSO Client mode. Users authenticate on Discourse; the
wp-discourse plugin logs them into WP automatically via the SSO callback.

Part of the tiaa-v3 project. Umbrella WP environment context:
`../tiaa-wpsite-v3/docs/wp-env-context.md` (tracked in tiaa-wpsite-v3).

> **Note:** `AI-Context.txt` in this directory is a legacy context file predating
> Claude Code. This `CLAUDE.md` supersedes it and is the authoritative AI context.

---

## File Structure

```
tiaa-wpplugin/
├── tiaa-wpplugin.php          ← entry point; constants, requires, bootstraps
├── lib/                       ← all plugin logic (OO PHP, namespace TIAAPlugin\lib)
│   ├── Discourse.php          ← Discourse API: ping, invite, REST endpoint
│   ├── TiaaReturnUrlCookie.php ← writes tiaa_wp_return_url cookie before SSO initiation
│   ├── TiaaMemberCookie.php   ← sets tiaa_member cookie; adds body class
│   ├── TiaaLoginRedirect.php  ← no-op in SSO client mode (kept for reference)
│   ├── TiaaHooks.php          ← WordPress hook registrations
│   ├── TiaaBase.php           ← base class
│   ├── TiaaSiteSettings.php   ← site settings admin tab + front-end output hooks
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

### `TiaaBase`
Entry point class; bootstrapped from the main plugin file. The constructor
registers `initialize_plugin()` on `init` at priority 3 (before WP-Discourse
SSO, which fires at 4–5).

`initialize_plugin()` also contains two inline behaviours that must run on
`init` (after WP has established the current user) rather than earlier hooks:

- **Admin bar suppression** — hides the WP admin bar for logged-in
  non-administrator users. Subscribers (the role Discourse SSO assigns) have
  no use for it on the front end; admins retain it. Implemented inline here
  because `after_setup_theme` fires before `is_user_logged_in()` is reliable.
- **Auth cookie lifetime** — extends the WP auth cookie to 30 days via
  `auth_cookie_expiration`. Discourse is the SSO authority; a shorter cookie
  would log members out of WP while they remain active on Discourse, causing
  split-session states.

### `TiaaReturnUrlCookie`
Writes a `tiaa_wp_return_url` cookie when a logged-out user clicks a Sign In / Join
button, so the Discourse brand header can link back to the exact WP page they came from
after authentication completes.

- Trigger: JS click listener on `.tiaa-sso-trigger` elements (injected via `wp_footer`)
- Cookie TTL: 1 hour (`COOKIE_TTL = 3600`)
- Cookie domain: from `TiaaSiteSettings::get_cookie_domain()` (must be `.tiaa-forum.org` with leading dot)
- Only runs for logged-out users on non-admin pages
- **Consumer:** `tiaa-forum-org/TIAA-BrandTheme-v3` — reads this cookie in its JS
  initializer and updates `.tiaa-home-link` hrefs to return the user to the originating
  WP page. The component validates the cookie value against a safe-domain allow-list
  (see that repo's CLAUDE.md); hostnames not on the list are silently rejected.
- **Elementor:** Add CSS class `tiaa-sso-trigger` to any Sign In / Join button (Advanced tab → CSS Classes)
- **SSO trigger `href`:** must point to `/?discourse_sso=1&redirect_to={encoded current URL}` —
  not directly to Discourse. WP-Discourse's `QueryRedirect` intercepts this and builds the
  proper Discourse SSO handshake. The cookie write and the SSO initiation both fire on the same click.

### `TiaaMemberCookie`
Sets a long-lived `tiaa_member=1` cookie on first logged-in page load.
Persists after logout — intentional (tracks returning members).
Adds `tiaa-returning-member` body class whenever cookie is present, allowing
Elementor to target returning members with conditional display.

The Discourse-side parallel is `localStorage.tiaa_returning_user` set by
`tiaa-forum-org/TIAA-DiscourseTheme-v1` after SSO callback — same concept,
separate mechanism scoped to each platform.

- Cookie TTL: 1 year (`YEAR_IN_SECONDS`)
- Cookie domain: from `TiaaSiteSettings::get_cookie_domain()`
- Bidirectional logout requires WP-Discourse SSO Client → "Sync Logout" ON

**Local dev limitation — both cookies:** Browsers treat `.local` as a reserved
mDNS domain and refuse to set cross-subdomain cookies on it. `tiaa_wp_return_url`
and `tiaa_member` cannot be shared between WP and Discourse on local dev.
**Use staging** (`test.tiaa-forum.org` / `discourse-f2.test.tiaa-forum.org`) for
any cookie or SSO flow testing.

**Staging cookie domain:** set `tiaa_cookie_domain` to `.test.tiaa-forum.org` in
the Site Settings admin tab. The `TIAA_COOKIE_DOMAIN` constant in `wp-config.php`
is not required — it is an optional override that locks the value and prevents
accidental admin changes. If the constant is absent, the admin setting is used.

**Confirmed working (2026-05-30):** Full cross-subdomain cookie flow verified on
staging — `tiaa_wp_return_url` and `tiaa_member` both visible on Discourse side;
BrandTheme return-URL override confirmed functional.

### `TiaaLoginRedirect`
**No-op in the current SSO client configuration.** Originally written to suppress
a flash of the WP SSO callback page when WP was the SSO provider. In SSO client
mode, WP-Discourse handles the Discourse callback at `init` (priority 5) and
redirects before `template_redirect` ever fires, so this class never activates.
Kept in the codebase for reference; safe to remove if the provider model never returns.

### `TiaaSiteSettings`
Owns the Site Settings admin tab and all global site configuration values.
Also responsible for two front-end output hooks.

**Admin fields (Settings → TIAA WP Plugin → Site Settings):**
- `tiaa_cookie_domain` — shared cookie domain; overridable via `TIAA_COOKIE_DOMAIN` constant in `wp-config.php`
- `tiaa_contact_email` — site contact email
- `tiaa_funding_level` — reserve level: `green` / `yellow` / `red` / `blue`
- `tiaa_stat_members`, `tiaa_stat_topics`, `tiaa_stat_posts` — forum stats (updated manually)
- `tiaa_stat_as_of` — date the stats were last recorded (YYYY-MM-DD)
- Discourse URL — read-only display pulled from WP-Discourse

**Shortcodes** (all output `<span class="tiaa-stats">`):
- `[tiaa_stat field="members|topics|posts"]` — numeric stat values
- `[tiaa_stat field="as_of"]` — as-of date formatted via the site's WordPress date format

**Front-end hooks:**
- `wp_head` — `output_contribute_color_style()`: outputs an inline `<style>` block setting the background colour of `.tiaa-contribute` elements based on funding level. Yellow gets black text; others inherit. **Elementor:** add CSS class `tiaa-contribute` to the Contribute button (Advanced → CSS Classes).
- `wp_footer` — `output_forum_button_script()`: outputs an inline `<script>` that sets the `href` of `.tiaa-go-to-forum` elements to the Discourse URL from WP-Discourse. **Elementor:** add CSS class `tiaa-go-to-forum` to the GO TO FORUM button.

**Static utility methods** (called by other lib classes — must be instantiated before them):
- `get_cookie_domain()` — used by `TiaaReturnUrlCookie` and `TiaaMemberCookie`
- `get_discourse_url()` — available for other callers; no longer used by `TiaaLoginRedirect`

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
- Cross-subdomain cookies cannot be tested on local dev — `.local` is a reserved mDNS domain; browsers block cross-subdomain cookies on it. Use staging.