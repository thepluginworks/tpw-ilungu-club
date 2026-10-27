# Getting Started with iLungu Club

iLungu Club is the shared foundation for iLungu Club-powered WordPress sites and add-on plugins. Configure only the screens and workflows your site uses.

## Install and activate

1. Install and activate iLungu Club from the WordPress plugin directory, or upload the `tpw-ilungu-club` folder to `/wp-content/plugins/`.
2. If replacing the older physical plugin, deactivate `tpw-flexiclub/tpw-flexiclub.php` at the same single-site or network scope before activating iLungu Club. Do not activate both copies together.
3. Open **iLungu Club → Settings** in wp-admin.
4. Review the System Pages, Member Menu, Features, Branding, Email, and (where required by an installed add-on) Payment Methods tabs.

The canonical wp-admin Settings screen is `admin.php?page=ilungu-club-settings`. Older Settings bookmarks redirect to the current screen.

## Set up the essential pages

iLungu Club registers the following System Pages. Required pages are maintained by the shared System Pages registry; optional pages are available only when your site uses their workflows.

| Page | Shortcode | When to use it |
| --- | --- | --- |
| My Profile | `[tpw_member_profile]` | Shared member self-service page. |
| Member Login | `[tpw_member_login]` | Front-end member login page. |
| Manage Members | `[tpw_manage_members]` | Member administration page. |
| Noticeboard | `[tpw_noticeboard_list]` | Private member noticeboard page. |
| Club Management | `[ilungu_club]` | Club management workspace. |
| Join | `[tpw_join_form]` | Use only when member sign-ups are enabled. |
| iLungu Club Control | `[tpw-control]` | Optional front-end administration hub. |
| Menu Management | `[ilungu_club_menu_management]` | Optional menu-management workspace. |
| Archival System | `[ilungu_club_archival_system]` | Optional archival workspace. |

Use the **System Pages** tab to review registered pages. Do not replace a registered shortcode with an older `flexiclub` shortcode: legacy shortcodes are compatibility aliases only.

For the profile workflow, select the published page containing `[tpw_member_profile]` in **Member Settings → Member Profile**. For login redirects, choose the published login page in **iLungu Club → Settings**. If no login page is selected, iLungu Club falls back to the registered Member Login page, then `/member-login/`, then the normal WordPress login.

## Configure the workflows you use

### Members

Open **Manage Members** to add and manage member records. You can link an existing WordPress user or create one for an eligible member with a valid email address. Configure which fields members can view and edit under **Member Settings → Member Profile**.

For detailed member administration, see [Members](members.md) and [Getting Started for Admins](../../modules/members/docs/admin-help/getting-started.md).

### Member sign-up

The Join page is provisioned only when member sign-ups are enabled. When enabled, `[tpw_join_form]` uses the active Join provider; the built-in form is shown only when its public sign-up fields are configured. If sign-ups are not enabled or no public fields are available, the built-in form does not accept registrations.

### Noticeboard and front-end administration

`[tpw_noticeboard_list]` is members-only by default. Authorised administrators and noticeboard managers can manage notices; standard members can view notices only when they have an allowed member status.

`[tpw-control]` provides an optional front-end hub for enabled society tools, including Upload Pages and Menu Manager. Access and available sections depend on the current user's role, flags, status, and enabled modules.

## Payments and add-ons

iLungu Club provides shared payment settings and records for add-ons that require them. The Payment Methods tab appears only when an installed plugin declares that it needs shared payment methods.

Only released, active, configured, and runtime-available methods may be presented at checkout. The currently released methods are Bank Transfer (BACS), Cheque, Cash, Card on the day, and Square. SumUp and WooCommerce are not released and must not be enabled or presented to customers.

See [Payments](payments.md) for the current payment-method guidance. Add-ons may require additional setup before their own customer workflows are available.

## Troubleshooting

- **A member cannot open their profile:** confirm that the selected profile page is published and contains `[tpw_member_profile]`.
- **Login sends a member to the wrong place:** set the Default Login Page and optional Redirect After Login in **iLungu Club → Settings**. Check any site-specific login redirect customisation separately.
- **The Join form is unavailable:** confirm that member sign-ups are enabled and that public sign-up fields have been configured.
- **A Noticeboard page is visible in an automatic page list:** iLungu Club hides known private System Pages from logged-out fallback page lists, but it does not rewrite arbitrary site menus. Review explicit menu links and the page's access settings.
- **A payment method is missing:** methods must be released, active, configured, and available at runtime. Ask the add-on administrator to confirm its payment configuration; do not use SumUp or WooCommerce.
- **A front-end administration tool is missing:** confirm that its shortcode page exists and that the signed-in user has the required access. For TPW Control, see the [TPW Control admin guide](admin-guide-tpw-control.md).

For System Page details, see [System Pages](system-pages.md). This guide does not establish support hours, service levels, public product-page URLs, or add-on-specific setup requirements.
