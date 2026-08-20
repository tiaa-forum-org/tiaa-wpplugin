### Known bugs or incomplete investigations
#### 1. ~~Problem with saving options as an array~~ — RESOLVED 2026-08-20
`WelcomeSettings.php` used `validate_options` instead of `validate_options_blank_ok`, forcing
Discourse URL/API key/username to be re-entered on the Welcome tab instead of falling back to
the Connection tab like Invite and Group Invite do.

Root cause turned out to be two separate bugs, not the array-coercion issue originally suspected:
1. `validate_options_blank_ok` — the method `InviteSettings.php`/`GroupInviteSettings.php` already
   passed to `register_setting()` — didn't exist anywhere in the codebase. WP's `register_setting()`
   only attaches a sanitize callback when `is_callable()` is true, so it silently skipped validation
   entirely for those two tabs; that's why blank worked for them (no validator ran at all, not a
   working blank-tolerant one).
2. A naming mismatch (`tiaa_validate_api_blank_ok` instead of `tiaa_validate_api_key_blank_ok`) meant
   even a correct dispatcher would have missed the API key's blank-ok filter.

Fixed: added a real `FormHelper::validate_options_blank_ok()` that dispatches each field through its
`tiaa_validate_{key}_blank_ok` filter when one is registered, falling back to the strict
`tiaa_validate_{key}` filter otherwise (e.g. `cron_interval`); fixed the api_key filter name; pointed
`WelcomeSettings.php` at the new method. Verified directly: blank url/api_key/username now save with
no settings errors, `group_list` still comes back as an array (not coerced to a string), and non-blank
invalid input (bad URL, bad API key format) still errors as before.

#### 2. Logger handling
The file TIAAFile.php is buried in the \Analog area but should be moved to the plugin library and generalized. 

As a result, the logging is not reliable because it seems like it doesn't get initialized for all the calls. This needs to be audited so that the calls from \PluginUtil are reliable. 