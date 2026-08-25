# Audit Prompt — Pass 2: Fable Judgment Pass

**Target:** `tiaa-wpplugin` repo (github.com/tiaa-forum-org/tiaa-wpplugin)
**Run in:** Claude Code
**Model:** Fable
**Prerequisite:** `docs/audits/tiaa-wpplugin-map.md` from Pass 1 must exist
**Purpose:** Judgment-level audit — bugs/security, quality, WP-style consistency, documentation, and a prioritized improvement list.

---

## Prompt

```
You are doing PASS 2 of a two-pass audit of the tiaa-wpplugin repository 
(github.com/tiaa-forum-org/tiaa-wpplugin). PASS 1 already produced a structural 
map at docs/audits/tiaa-wpplugin-map.md — read it first, then verify against the 
actual repo before relying on it (the map may be incomplete or slightly stale).

CONTEXT YOU SHOULD KNOW:
- This plugin runs in production on tiaa-forum.org, a live WordPress community 
  site for AA members, maintained by a small volunteer team with basic WP 
  experience but limited custom-PHP depth.
- The site suffered a real breach in July 2026: unauthenticated privilege 
  escalation through a broken-access-control REST endpoint (OWASP API5:2023), 
  leading to malicious plugin installation. Attacker IP 129.121.77.134, C2 
  domain rakuten64jp.click. A clean rebuild was done from backup.
- WP SimplePay/Stripe is NOT part of this plugin — don't flag its absence as a gap.
- Volunteer-maintainability is a hard constraint: a fix that's technically 
  cleaner but unreadable to a WP-comfortable-but-not-expert maintainer is a 
  worse recommendation than a simpler one, unless the security stakes justify 
  the complexity. Say so explicitly when you make that trade-off. This applies 
  to documentation too — comments and docblocks should be written for that 
  same audience, not for a senior engineer.

DELIVERABLE: docs/audits/tiaa-wpplugin-audit.md with these sections:

## 1. Bugs / Security Issues
Every finding, each with: file:line, severity (Critical/High/Medium/Low), what's 
wrong, how it could actually be exploited or fail (concrete scenario, not generic 
description), and a suggested fix. Prioritize anything resembling the July 
incident's pattern (missing/weak permission_callback on register_rest_route, 
unauthenticated state-changing endpoints) at the top regardless of where it falls 
alphabetically.

## 2. Overall Quality & Structure
Assessment of code organization, separation of concerns, error handling 
consistency, whether the procedural/class-mixing (if any) creates maintenance 
risk. Call out anything that works but will bite a future volunteer maintainer 
who doesn't have full context.

## 3. Documentation
Assess whether the plugin is documented well enough that a volunteer maintainer 
who didn't write it could safely modify it. Specifically:

- **Docblocks**: Present on all functions/classes? Consistent format (@param, 
  @return, @since)? Use the Pass 1 map's per-function docblock column as a 
  starting point, then flag any public/hookable function missing one, and any 
  docblock that's present but wrong or stale (e.g. describes a parameter that 
  no longer exists).
- **Inline comments on non-obvious logic**: Look specifically at the SSO/webhook 
  HMAC verification, the /tiaa-logout route, the Discourse API calls, and any 
  regex or bit-twiddling — these are the spots where silence is most dangerous. 
  Flag code that does something clever or security-relevant with zero explanation 
  of *why*, not just *what*.
- **File-header comments**: Does each file explain its role, or does a maintainer 
  have to read the whole thing to know what it's for?
- **README / setup docs**: Does the repo have a README covering what the plugin 
  does, its dependencies (WP-Discourse, etc.), and any required config/constants? 
  If missing or stale, say so (cross-check against Pass 1's "Documentation 
  Artifacts" section).
- **Comment rot**: Any comments that contradict what the code actually does now 
  (a sign of edits made without updating the explanation) — these are worse than 
  no comment at all and should be called out specifically, not lumped in with 
  "missing" documentation.

Rate overall documentation health (Good/Adequate/Poor) with the file:line evidence 
behind that rating, not just a vibe.

## 4. WordPress Style Consistency
Deviations from WP Coding Standards and WP plugin conventions (hook naming, 
sanitization/escaping patterns — esc_html/esc_attr/sanitize_text_field usage, 
i18n readiness, plugin header completeness). Reference PHPCS output from the 
Pass 1 map if present.

## 5. Suggested Improvements (Prioritized)
Split into:
   - **Do now** — low-risk, high-value fixes that fit before/alongside the 
     ongoing REST audit
   - **Next release** — real improvements that need more testing/design time
   - **Someday / nice-to-have** — refactors that are correct ideas but not 
     worth volunteer time yet
For each item, note estimated effort (small/medium/large) and whether it 
requires custom code or has an Elementor/WP-native alternative, per this team's 
stated preference for native solutions over custom code. Documentation fixes 
(missing docblocks, stale comments) belong in this list too — most will be 
"do now," since they're cheap and carry no deployment risk.

## 6. Audiitor's Notes
Anything else you want to say to the auditor such as "This scan should have included these missing focus points or other considerations." Just list them in the report and state rationale as to why they're important and how important they are   .

Cite file:line for every claim. Do not restate the Pass 1 map's contents — 
build on it. End with a short "if I could only fix three things before the next 
deploy" callout.
```
