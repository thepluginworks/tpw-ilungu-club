# iLungu Club Lifecycle and Data Retention Contract

**Status:** Authoritative

## Default Retention

iLungu Club preserves persistent data by default on deactivation, update, uninstall, and plugin deletion. The current-site option `tpw_core_delete_data_on_uninstall` is the only lifecycle-cleanup consent record. Missing or malformed values are off; only the exact stored string `1` enables uninstall cleanup.

The consent control is available to administrators with `manage_options` at both iLungu Club Settings routes:

- wp-admin: `admin.php?page=ilungu-club-settings&tab=lifecycle`
- front end: the Settings workspace rendered by `[ilungu_club]`

The option is current-site only. No network iteration or network-wide cleanup occurs.

## Deactivation

Deactivation is non-destructive. It removes only future Club-owned jobs:

- `tpw_email_queue_reconcile` in the `tpw-email` Action Scheduler group
- `tpw_email_logs_cleanup`
- `tpw_control_backfill_checksums`
- `tpw_control_checksum_backfill`

Queued email-item actions are retained because their rows and scheduled actions can originate from consumers and cannot be attributed safely at schedule level. No scheduler tables, queue rows, shared Action Scheduler groups, or consumer jobs are removed.

## Opted-In Uninstall Cleanup

When consent is exactly `1`, cleanup removes only these proven Club-owned settings:

- `tpw_core_branding`, `tpw_ui_theme_settings`, `tpw_heading_styles`, `tpw_brand_title`
- `tpw_core_default_login_page`, `tpw_login_redirect_page_id`, `tpw_member_menu_location`
- `tpw_core_rewrite_flushed_v1`, `tpw_core_rewrite_flushed_v2`
- `tpw_core_member_menu_seeded`, `tpw_core_member_menu_defaults_seeded_v2`, `tpw_core_profile_page_seeded`

It also removes only these exact `tpw_email_templates.template_key` overrides:

- `member_new_wp_user_created`
- `member_password_setup`

For the Club System Page slugs `member-login`, `my-profile`, `manage-members`, `noticeboard`, `club-management`, `logs`, `menu-management`, `archival-system`, and `tpw-control`, cleanup removes a stored mapping only if the mapped WordPress page carries matching `_tpw_system_page_slug` metadata and `_tpw_system_page_plugin = tpw-core`. Pages and their metadata are retained so activation can safely reuse them. Missing or conflicting metadata preserves the mapping and page.

No broad transient-prefix deletion occurs. The prior `_transient_tpw_%` and `_site_transient_tpw_%` cleanup is intentionally retired because prefix ownership is not exclusive.

## Always Preserved

Cleanup never drops or truncates tables. It preserves members, households, WordPress users and user meta, roles and capabilities, uploads, Upload Pages records and associations, payment records and logs, signup/history data, email queue/log tables and rows, Action Scheduler infrastructure, consumer jobs, consumer System Pages and mappings, Events `flexievent_*` settings, consumer email templates, galleries, notices, menus, and ambiguous records.

In particular, `tpw_rsvp_payments` rows are always preserved because they have no ownership discriminator. `tpw_payment_logs` are also preserved because exact Club-only ownership is not established.

The following legacy uninstall candidates are deliberately preserved: all `tpw_members_*` settings and field configuration, payment-method and gateway configuration, `flexievent_*` settings, `tpw_gallery_db_version`, and all unlisted `tpw_*` options. Prefixes do not establish lifecycle ownership.

## Reinstall

Activation and upgrade paths remain additive and idempotent. Retained System Pages, email templates, payment methods, member fields, recurring jobs, Upload Pages schema, and mappings must be reused rather than reset or duplicated.