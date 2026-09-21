# iLungu™ Club Settings — Admin Guide

This page explains the settings available under wp-admin → iLungu Club → Settings.

The canonical backend Settings URL is `/wp-admin/admin.php?page=tpw-flexiclub-settings`. Each tab uses this page with its own `tab` query argument, for example `/wp-admin/admin.php?page=tpw-flexiclub-settings&tab=system-pages`.

Legacy `/wp-admin/options-general.php?page=tpw-core-settings` bookmarks redirect to the canonical route when possible, but iLungu Club is not shown in the native WordPress Settings menu.

## Features

- Default Login Page
  - What it does: Defines the page members should be redirected to when login is required on iLungu Club pages and features.
  - Where used: FlexiEvent and other iLungu Club add-ons read this value to send users to the correct login screen.
  - How to set: Choose a published page from the dropdown. The page should contain your login UI (e.g. the `[tpw_member_login]` shortcode) or a custom login form from your theme/builder.
  - Fallbacks: If not set, the shared plugin framework falls back to the registered System Page “Member Login” (if available), then to `/member-login/`, and finally to the standard WordPress login.

- Redirect After Login
  - Optional page to send members to immediately after a successful login. Leave as “No redirect” to send them to your site home.

## Branding

Controls UI theme tokens used in iLungu Club admin UIs and buttons. Adjust font, weights, colours, and button styles.

## Email Settings and Templates

Configure email throttling, logging, and the fallback logo used in email templates. Edit registered templates’ subject and body where allowed.

Notes
- All iLungu Club add-ons consult the Default Login Page for redirecting users who need to sign in.
- Site-specific overrides can still change the login URL using the `tpw_core/login_url` filter.
