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

When consent is exactly `1`, cleanup permanently deletes all data proven to be owned solely by iLungu Club. Administrators must back up or export data they need before uninstalling; deleted Club-owned data is unrecoverable.

Cleanup deletes these proven Club-owned settings:

- `tpw_core_branding`, `tpw_ui_theme_settings`, `tpw_heading_styles`, `tpw_brand_title`
- `tpw_core_default_login_page`, `tpw_login_redirect_page_id`, `tpw_member_menu_location`
- `tpw_core_rewrite_flushed_v1`, `tpw_core_rewrite_flushed_v2`
- `tpw_core_member_menu_seeded`, `tpw_core_member_menu_defaults_seeded_v2`, `tpw_core_profile_page_seeded`

It also deletes the Member module settings:

- `tpw_members_settings`, `tpw_members_allow_deletion`, `tpw_default_member_status`, `tpw_members_enable_households`
- `tpw_member_change_notify_email`, `tpw_members_default_view`, `tpw_members_default_per_page`, `tpw_members_default_per_page_card`
- `tpw_members_show_adult_family_on_primary_profile`, `tpw_members_use_photos`, `tpw_members_enable_advanced_search`
- `tpw_member_editable_fields`, `tpw_member_viewable_fields`, `tpw_member_profile_photo_mode`, `tpw_member_profile_page_id`
- `tpw_member_searchable_fields`, `tpw_member_field_download`, `tpw_member_field_sections`, `tpw_conditional_field`, `tpw_conditional_fields`, `tpw_enable_signup_debug`
- `tpw_gallery_db_version`, `tpw_control_files_migrated_to_registry`, and `tpw_control_upload_pages_schema_repair_issue`

It also removes only these exact `tpw_email_templates.template_key` overrides:

- `member_new_wp_user_created`
- `member_password_setup`

Cleanup deletes rows, while retaining their schemas, from these Club-only tables:

- Member records and configuration: `tpw_members`, `tpw_members_household`, `tpw_members_household_member`, `tpw_field_settings`, `tpw_member_field_visibility`, and `tpw_member_meta`
- Gallery records: `tpw_galleries`, `tpw_gallery_images`, and `tpw_gallery_categories`; WordPress media attachments and upload files are not deleted
- TPW Control Upload Pages records: `tpw_upload_pages`, `tpw_upload_categories`, `tpw_upload_pages_files`, legacy `tpw_upload_files`, and `tpw_files`; the Upload Pages schemas are never dropped or recreated during runtime cleanup

Cleanup deletes sign-up attempts only where `tpw_signup_attempts.plugin_key` is exactly `tpw-core`. It deletes all `tpw_notice` posts and `tpw_notice_category` terms because that post type and taxonomy are owned solely by Core.

For the Club System Page slugs `member-login`, `my-profile`, `manage-members`, `noticeboard`, `club-management`, `logs`, `menu-management`, `archival-system`, and `tpw-control`, cleanup permanently deletes a mapped WordPress page and its metadata only if it is a page, carries matching `_tpw_system_page_slug` metadata, and carries `_tpw_system_page_plugin = tpw-core`. It removes the mapping only after that deletion succeeds. Missing or conflicting metadata preserves both mapping and page.

No broad transient-prefix deletion occurs. The prior `_transient_tpw_%` and `_site_transient_tpw_%` cleanup is intentionally retired because prefix ownership is not exclusive.

## Always Preserved

Cleanup never drops or truncates tables. It preserves WordPress users and user meta, roles and capabilities, WordPress media attachments and physical uploads, payment records and logs, email queue/log tables and rows, Action Scheduler infrastructure, consumer jobs, consumer System Pages and mappings, Events `flexievent_*` settings, consumer email templates, event menus and costs, feedback rows, and ambiguous records.

In particular, `tpw_rsvp_payments` rows are always preserved because they have no ownership discriminator. Payment methods and payment logs remain provider/shared infrastructure. Signup attempts for any `plugin_key` other than `tpw-core` remain. `tpw_payment_logs` are preserved because exact Club-only ownership is not established.

All unlisted `tpw_*` options and tables remain preserved. Prefixes do not establish lifecycle ownership.

## Reinstall

Activation and upgrade paths remain additive and idempotent. After opted-in cleanup, retained table schemas are reused and activation recreates the default valid Club state. Retained shared System Pages, email templates, payment methods, recurring jobs, Upload Pages schema, and consumer mappings must be reused rather than reset or duplicated.