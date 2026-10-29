# Menus (Event Menus)

## Overview
Event Menus let you define menus (courses and choices) and attach them to events. A front‑end modal displays the menu on event pages.

## Key Screens / Shortcodes
- Front‑end render: automatically after event content; shortcode fallback [tpw_menu_modal event_id="123"].
- Admin tables (created on activate): tpw_menus, tpw_menu_courses, tpw_menu_choices, tpw_event_menu_relationship.

## Hooks
- tpw_core/menu_modal_button_label (filter) — Change button label.
- tpw_core/menu_modal_title (filter) — Change modal title.
- tpw_core/menu_modal_price_html (filter) — Customize price HTML.

## Extending
- Use TPW_Menus::get_menu_payload( $event_id ) to reuse payload server‑side.
- Consumer plugins with a WordPress `tpw_event` post ID must use
  `tpw_core_get_event_menu_payload_for_post( $event_post_id )`. It resolves the
  iLungu Events domain ID, returns stable dish IDs and additional costs, and
  returns `null` for missing, ambiguous, deleted, or incomplete definitions.
  The full contract is [Event Menu Read Contract](../architecture/menus/tpw-core-event-menu-read-contract.md).
- Club administration flows assigning a menu to a WordPress event post must use
  `TPW_Event_Menu_Rel::assign_menu_to_event_post( $event_post_id, $menu_id )`.
  It returns a result array with an explicit success state and outcome code,
  preserves relationships for other events, allows a menu to be reused, and
  rejects pre-existing ambiguous relationships without deleting data.
- Theme override: create template at `theme/tpw-ilungu-club/menus/menu-modal.php` to customize markup. The legacy `theme/tpw-flexiclub/menus/menu-modal.php` override remains supported during the compatibility window.
- Legacy override support: existing theme/tpw-core/menus/menu-modal.php overrides still load for backward compatibility.

## References
- Developer Guide → ../developer-guide.md
- Renderer: modules/menus/class-tpw-menus.php
- Template: modules/menus/templates/menu-modal.php

See also: Core Hooks Index → ../developer-guide.md#core-hooks-index
