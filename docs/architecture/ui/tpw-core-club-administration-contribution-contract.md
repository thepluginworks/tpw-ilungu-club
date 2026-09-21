# iLungu Club Administration Contribution Contract

**Status:** Authoritative  
**Applies to:** iLungu Club and compatible active consumer plugins

## Purpose and ownership

iLungu Club owns the Club Overview dashboard, Club Management portal, and iLungu Club wp-admin shell. Consumer plugins retain ownership of their business rules, routes, templates, assets, data, and authorization.

Consumers contribute through the `tpw_core_club_administration_contributions` filter. They must register only while their plugin is active and append ordered contribution rows. Each row carries its own stable `key`. Core validates each row and ignores invalid or unauthorized contributions. Core never treats a visible action or workspace link as authorization; every consumer destination and render callback must independently enforce access.

## Contribution shape

Each contribution row has a unique stable `key` and may declare these optional areas:

```php

[
	[
		'key' => 'example',
		'legacy_keys' => [ 'legacy-example' ], // Optional migration aliases.
	'contexts' => [ 'frontend', 'admin' ],
	'capability' => 'manage_options', // WordPress capability or callable returning bool.
	'overview_card' => [
		'title' => 'Example',
		'metric' => 'Ready',
		'tone' => 'default',
		'status_label' => 'Active',
		'status_tone' => 'success',
		'description' => 'Consumer-owned tool.',
		'icon' => 'dashicons-admin-generic',
		'primary_action' => [ 'label' => 'Manage', 'url' => '...' ],
		'secondary_action' => [ 'label' => 'Settings', 'url' => '...' ],
		'position' => 50,
	],
	'extend_actions' => [
		'catalogue_key' => 'lodge',
		'actions' => [
			[ 'label' => 'Manage', 'url' => '...' ],
			[ 'label' => 'Settings', 'url' => '...' ],
		],
	],
	'workspace' => [
		'key' => 'example',
		'label' => 'Example',
		'frontend' => [ 'render_callback' => [ $plugin, 'render_frontend' ] ],
		'admin' => [
			'capability' => 'manage_options',
			'page_callback' => [ $plugin, 'render_admin' ],
		],
		'position' => 50,
	],
	],
]
```

`contexts` accepts `frontend`, `admin`, or both. A row can additionally declare a capability string or callable. Admin workspaces require a WordPress capability string, supplied by `workspace.admin.capability` or the contribution-level capability. Missing labels, URLs, callbacks, unsupported contexts, malformed rows, and duplicate keys are ignored. Rows are processed in filter-return order. The first valid row for a key wins, and a rejected malformed row does not reserve that key. `position` is ascending, with stable key order as the tie-breaker.

For canonical/legacy migration, the canonical row may declare `legacy_keys`. Core resolves those legacy keys to the canonical key, renders only one logical contribution, and gives a valid canonical row priority when both forms are present. If only a legacy row is valid, it remains the compatibility fallback. Unrelated contribution keys retain the standard first-valid-row rule. See [the canonical identity compatibility contract](../tpw-core-canonical-legacy-identity-compatibility-contract.md).

## Dashboard contributions

`overview_card` is adapted into the existing dashboard card shape in each declared context. The primary action maps to the existing action fields. A valid secondary action is additive and is rendered after the primary action. Core cards and their order remain unchanged; consumer cards follow them.

`extend_actions` applies to an existing active Extend iLungu Club catalogue card identified by `catalogue_key`; it cannot add or replace catalogue cards. Inactive and discovery actions, including activation and `Learn more`, remain Club-owned and unchanged. For an active card, valid consumer actions are authoritative in the current context: when one or more valid `extend_actions` exist, Core suppresses its legacy active action and renders only those actions. If no valid consumer action exists for that card and context, Core retains the legacy active action as the compatibility fallback. Consumers that contribute actions must provide their complete desired active action set for each context. Actions are shown only when that catalogue plugin is active. Inactive catalogue behaviour, activation behaviour, route families, and `tpw_core_frontend_dashboard_plugin_active_url` remain unchanged.

## Workspaces

`workspace` allows one consumer-owned workspace to appear in each declared shell. `workspace.key` is the frontend route key and must be stable. Frontend workspaces are rendered inside the Club Management shell through the consumer `frontend.render_callback`. Admin workspaces are registered as submenus under the existing iLungu Club top-level menu through `admin.page_callback`.

Core applies declared visibility before exposing navigation or menus. Consumers must apply their own capability checks in render callbacks and at every destination. Consumer callbacks own their markup and may enqueue their own scoped assets using existing documented shared UI wrappers and handles. Core does not load consumer assets globally.

## Backwards compatibility and degradation

All additions are optional. Without valid consumer registrations, current Core dashboard cards, Quick Actions, Extend cards, frontend workspaces, wp-admin menus, and legacy TPW Control section registration behave unchanged. Invalid, unavailable, or unauthorized contributions fail closed and do not prevent Core rendering. This contract does not replace TPW Control sections or raw consumer functionality.