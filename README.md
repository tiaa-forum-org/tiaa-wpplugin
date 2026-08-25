# TIAA WordPress Plugin

## Overview

The **TIAA WordPress Plugin** (from tiaa-forum.org) is designed to provide essential functionality for the [TIAA-Forum.org](https://tiaa-forum.org) community, integrating key features to enhance user experience, streamline administrative processes, and improve interactions with external the Discourse server.

This plugin consolidates various functions previously handled by Google Apps Scripts and third-party WordPress plugins, aiming for a more maintainable and WordPress-native solution.

## Features

### 1. **User Invitation Management**
- Replaces the existing Google Apps Script-based invitation system.
- Provides a form-based invite process within WordPress.
- Integrates with Discourse to automate invitation processing.
- Allows invitation to a particular Discourse group to allow for special onboarding or to allow changing the home group for members of a particular primary group
- Detects duplicate email addresses and directs users to the password reset process.

#### Setting up an Invite Topic ID

Both the Signup tab and each Group Invite tab have an optional **Topic ID**
field. When set, a new member lands on that Discourse topic right after
accepting their invite — it's typically used as a "start here" post
tailored to that particular invite group.

**Why the topic has to be created a specific way, not just any topic ID:**
Discourse's invite API checks whether the linked topic is visible *as if
the recipient hadn't joined yet* — regardless of how privileged the
account sending the invite is. If the topic lives in a category that
isn't readable by "Everyone" (for example, one restricted to logged-in
members only), every invite send using that Topic ID fails, even though
the exact same topic links without any problem when an admin creates the
invite manually from inside Discourse's own web UI (confirmed 2026-08-20
— that flow doesn't go through the same check the API does).

**Process for creating a topic to use as an invite Topic ID** (confirmed
working 2026-08-20):

1. On Discourse, create a new topic in the `#public` category.
2. Write the post as you'd want a new invite recipient to see it — this
   is the first thing they'll land on after joining.
3. Once the post is finished, open the topic's settings and change it to
    to `unlisted`.
4. Note the topic ID from the URL — that's the value to enter in the
   Topic ID field on the Signup or Group Invite settings tab in the topic_id field.

Creating the topic in `#public` first, then moving it to `unlisted`,
keeps the category's read permission set to "Everyone" — satisfying the
invite API's visibility check above — while `unlisted` hides the topic
from public search, the topic list, and ordinary browsing (unless the user is a member of the `staff` group). In practice this
means the topic is only reachable by someone who has the direct link (as
the invite itself provides), even though it isn't gated by a logged-in-only
permission the way a normal restricted category would be.

#### Setting up the invite form's honeypot field

The `/invite` REST endpoint is intentionally public (anonymous visitors
submit it), which makes it a target for form-scraping spam bots. As one
layer of defense, the plugin checks for a hidden **honeypot** field named
`website` on the submitted form — see `TiaaHooks::INVITE_HONEYPOT_FIELD`'s
docblock for the full reasoning. This field has to be added on the
Elementor side; it can't be added by a code change alone.

**To set it up, on each Elementor invite form (Signup and every Group
Invite form):**

1. Add a new text field to the form with the exact field ID/name `website`
   (or change `TiaaHooks::INVITE_HONEYPOT_FIELD` to match a different name —
   just keep the two in sync).
2. In the field's Advanced settings, add a custom CSS class (e.g.
   `tiaa-hp`), then add this rule to the site's custom CSS:
   ```css
   .tiaa-hp { position: absolute; left: -9999px; top: -9999px; }
   ```
   Use `position: absolute` off-screen, not `display: none` — some bots
   skip fields hidden that way but still fill anything present in the DOM
   regardless of CSS visibility.
3. Leave the field optional/unvalidated in Elementor — a real visitor
   should never interact with it at all, and its presence with any value
   is what marks a submission as a bot.

A real visitor never sees or fills this field, so it stays empty on every
genuine submission. A submission that arrives with it populated is
treated as a bot and gets a normal-looking success response with no
Discourse API call made — see the honeypot section of
`TiaaHooks::invite_to_discourse()` for why the response is disguised
rather than an explicit rejection.

**Limitation:** this only screens naive, generic scraping bots that
blindly fill every field they find. It does not stop a targeted attacker
who inspects the real request and simply omits the field — the per-email
invite rate limit (`TiaaHooks::INVITE_EMAIL_RATE_LIMIT_MAX`) is the
defense that still holds against that case.

### 2. **Welcome Message Automation**
- Sends personalized Discourse messages to new users.
- Helps onboard members by explaining key features of the forum.
- Ensures engagement and retention by encouraging participation.

### 3. **Site Settings**
- Centralized settings page for site-wide configuration (`Admin > TIAA Forum > Site Settings`).

|  Setting      | Purpose                                                                                                                                                                                                                                      |
|---------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Cookie Domain | Shared domain used for cross-subdomain cookies between WordPress and Discourse (e.g. `.tiaa-forum.org` for production). Can be locked via the `TIAA_COOKIE_DOMAIN` constant in `wp-config.php` to prevent accidental changes.                |
| Contact Email | Site contact email address. Displayed anywhere via `[tiaa_contact_email]`, rendered as a `mailto:` link.                                                                                                                                    |
| Funding Level | Reserve status (blue / green / yellow / red) — colors for the "Contribute" button on the front end header.                                                                                                                                   |
| Forum Stats   | Member, topic, post, and category counts, plus an as-of date. Updated manually and displayed anywhere (e.g. Home Page) via `[tiaa_stat field="..."]` shortcodes. Current numbers can be obtained via a Discourse Data Explorer plugin query. |

- Also drives front-end automations that update automatically when a Site Setting changes — no code or template edits needed:
  - **Funding Level** → colors the `.tiaa-contribute` button (Elementor CSS class) via an inline `<style>` block on `wp_head`.
  - **Discourse URL** (read from WP-Discourse's own settings, not stored here) → sets the `href` of `.tiaa-go-to-forum` links/buttons (Elementor CSS class) via inline `<script>` on `wp_footer`.
  - **Contact Email** → available via the `[tiaa_contact_email]` shortcode, same pattern as the Forum Stats shortcodes above.

### 4. **Web Hook & API Integrations**
- Uses webhooks for real-time processing of forms.
- Custom PHP handlers improve error handling and user feedback.
- Designed to work independently of Elementor but offers integration where needed.

### 5. **Admin & Plugin Configuration**
- Provides a centralized admin panel for managing plugin settings.
- Supports configurable parameters for integration with Discourse.
- Implements WordPress cron jobs for scheduled tasks.

### 6. **Wordfence-Safe Logout Route (`/tiaa-logout`)**
- Added in v0.0.12 (`lib/TiaaLogoutRoute.php`).
- **Why it exists:** the site's logout link used to point at
  `/wp-login.php?action=logout`. Wordfence's Brute Force Protection locks out
  `wp-login.php` after repeated failed login attempts — since logout went
  through the same script, a locked-out visitor couldn't log out either.
  `/tiaa-logout` never touches `wp-login.php`, so it stays reachable during a
  lockout.
- **Href format:** `/tiaa-logout?redirect_to=/` — the Elementor header's
  Logout button/link must use this, not `wp_logout_url()` or
  `/wp-login?action=logout`. See `tiaa-wpsite-v3`'s
  `docs/guides/03 elementor header template.md` for the Elementor-side setup.
- **`redirect_to` param:** optional; validated with `wp_validate_redirect()`
  so it can't be pointed off-site. Defaults to the site home URL if absent or
  invalid.
- **No nonce check, deliberately:** the logout link this replaces never had
  one either (logout is non-destructive), so this preserves the existing
  one-click behavior. Do not "fix" this by adding nonce verification.
- **Discourse SSO teardown still works:** the route calls WordPress core's
  `wp_logout()`, which fires the standard `wp_logout` and `clear_auth_cookie`
  actions — the same hooks WP-Discourse's SSO Client uses to end the
  corresponding Discourse session. Verified (2026-08) to behave identically
  to the old `/wp-login.php?action=logout` flow: both call `wp_logout()` the
  same way, and no plugin in this stack (WP-Discourse, Wordfence, Simple
  History) hooks anything specific to `wp-login.php`'s request path — they
  all hook the generic `wp_logout` / `clear_auth_cookie` actions that fire
  regardless of which route triggers them.
- **⚠️ Does not sync Discourse logout on local/localhost installs.**
  WP-Discourse's Discourse-logout API call
  (`POST /admin/users/{id}/log_out`) fails silently on local dev — e.g. the
  `wp-test` Docker environment talking to `discourse-dev.test` — because
  that container's CA trust store doesn't include the local mkcert
  certificate the Discourse dev instance uses (`cURL error 60: SSL
  certificate problem: unable to get local issuer certificate`).
  WordPress logs the user out fine either way; only the Discourse-side API
  call is affected, and it fails the exact same way via the old
  `/wp-login.php?action=logout` link too — this is a pre-existing local-dev
  environment gap, not specific to `/tiaa-logout`. Confirmed working
  correctly on `test-v3` staging (2026-08-06). See the WP repo's
  Dockerfile for the equivalent CA-trust fix already prepared for its
  container; `wp-test`'s `Dockerfile.wp-test` doesn't yet have it applied.

## Installation

1. **Download the plugin ZIP from GitHub Releases** — not the repository's
   own green "Code > Download ZIP" button. That downloads the entire repo
   (`bin/`, `docs/`, `CLAUDE.md`, and other development-only files) instead
   of a clean, installable plugin package.
    - Always-current link — safe to reuse any time, always resolves to the
      newest version: [`tiaa-wpplugin.zip`](https://github.com/tiaa-forum-org/tiaa-wpplugin/releases/latest/download/tiaa-wpplugin.zip)
    - To install a specific older version instead, pick it from the
      [Releases page](https://github.com/tiaa-forum-org/tiaa-wpplugin/releases)
      and download that version's `tiaa-wpplugin.zip` asset.

2. **Upload the Plugin:**
    - In the WordPress admin, go to `Plugins > Add New Plugin > Upload Plugin`.
    - Choose the downloaded `tiaa-wpplugin.zip` file and click **Install Now**.
    - Click **Activate**.

3. **Configure Settings:**
    - Go to `Settings > TIAA Plugin` in the WordPress admin panel.
    - Enter the required API keys for Discourse.
    - Adjust settings for invite processing, welcome messages, and webhook responses.

4. **Use the Plugin:**
    - The plugin will automatically handle invites, welcome messages, and other functions based on the settings configured.

## Future Development

Planned enhancements include:
- More robust error handling for Discourse API calls.
- Additional admin tools for managing invitations.

## Contributing

If you'd like to contribute to this plugin, you can:
- Submit bug reports and feature requests on the [GitHub repository](https://github.com/tiaa-forum-org).
- Fork the repository and submit pull requests with improvements.
- Provide feedback and suggestions via the [TIAA Forum](https://discourse.tiaa-forum.org).

## License

This plugin is open-source and licensed under the [MIT License](LICENSE).
