# TPW Core Canonical and Legacy Identity Compatibility Contract

**Status:** Authoritative
**Applies to:** active iLungu Club consumer plugins migrating provider, group, source, or Club Administration identities

## Purpose

Core supports canonical identifiers alongside declared legacy aliases during a consumer migration. The canonical identifier is the write and presentation identity. Legacy values remain readable; Core does not bulk rewrite, delete, recreate, or overwrite managed-site data.

This contract covers identity aliases only. It does not rename `TPW_Core_*`, `TPW_Member_*`, shared UI contracts, shortcodes, routes, or stored member data.

## Email Template Groups

Register each template once with its stable template key and canonical group. Add `legacy_groups` when an old group remains on installed sites:

```php
TPW_Email_Template_Registry::register_template(
	array(
		'key'           => 'ticket-confirmation',
		'group'         => 'ilungu-tickets',
		'legacy_groups' => array( 'tpw-flexiticket' ),
		// Other existing template fields.
	)
);
```

`TPW_Email_Template_Registry::get_by_group()` accepts either identity and returns the canonical registration. `get_group_identities()` exposes the canonical group followed by accepted legacy aliases. Overrides remain keyed by the stable template key in `tpw_email_templates`; registering a canonical group does not create a second override or overwrite a customized subject or body. Saving an existing override writes the canonical group label while retaining the same override row.

## Payment Log Sources

Before emitting or reading payment logs, a consumer registers aliases after Core is available:

```php
TPW_Payment_Source_Registry::register_source_aliases(
	'ilungu-tickets',
	array( 'tpw-flexiticket' )
);
```

`TPW_Payment_Source_Registry::normalize_source()` returns the canonical logical source. New `TPW_Payment_Logger::log()` writes use the canonical source after registration. `TPW_Payment_Logs_Admin::get_page_for_source()` accepts either identifier, queries all canonical and legacy stored `tpw_payment_logs.plugin` values, and normalizes returned rows to the canonical source.

Consumers must use canonical source IDs for new log writes and logical reporting. They must not rewrite historical payment-log rows. A report grouping source rows must group by `normalize_source()` so historical and current rows form one logical source total.

## System Page Providers

System Page slugs remain stable. Register the canonical provider and declare its former provider values:

```php
TPW_Core_System_Pages::register_page(
	'ticket-sales',
	array(
		'title'          => 'Ticket Sales',
		'shortcode'       => '[ilungu_ticket_sales]',
		'plugin'          => 'ilungu-tickets',
		'legacy_plugins'  => array( 'tpw-flexiticket' ),
		'required'        => 1,
	)
);
```

`TPW_Core_System_Pages::ensure_page()` and `get_page_id()` treat a page marked with either provider as owned by the one registry entry. A legacy-owned page therefore keeps its page ID and content instead of causing a duplicate page. Existing ensure behavior may lazily mark a reused page with the canonical provider; no bulk provider-meta rewrite occurs.

## Club Administration Contributions

Publish a canonical contribution key. When a plugin temporarily publishes its previous row as well, declare that prior key in `legacy_keys` on the canonical row:

```php
array(
	'key'         => 'ilungu-tickets-admin',
	'legacy_keys' => array( 'tpw-flexiticket-admin' ),
	// Existing contexts, capability, cards, actions, and workspace fields.
)
```

Core resolves every declared legacy key to its canonical key. A valid canonical row is primary when both rows exist; otherwise the first valid legacy row remains the compatibility fallback. Unrelated keys keep the existing deterministic first-valid-row behavior. Consumers should eventually publish only the canonical row after their compatibility window.

## Managed Members Menu Items

For a canonical Members Menu item, declare prior managed keys and providers on the canonical item specification:

```php
array(
	'key'              => 'ilungu-golf-fixtures',
	'legacy_keys'      => array( 'flexigolf-fixtures' ),
	'provider'         => 'ilungu-golf',
	'legacy_providers' => array( 'flexigolf' ),
	// Existing title, destination, login, and visibility fields.
)
```

Core normalizes declared legacy keys to the canonical key before managed-menu deduplication. A valid canonical item is primary when both forms are present. During a normal menu repair, an existing row stored with the legacy key is reused and updated to the canonical metadata; Core does not bulk rewrite menu rows. See [the Members Menu registration contract](navigation/tpw-core-members-menu-registration-contract.md).

## Help Ownership

Core does not own a Help provider registry, provider storage, or migration API. Help provider migration is owned by the Events Help Registry contract. Consumer plugins must use the canonical Events Help registration API and should not register Help providers through Core or invent a Core compatibility layer.

## Persistent Data Rules

This contract permits canonical registration, legacy reads, canonical writes, and idempotent lazy metadata marking already performed by the existing System Pages ensure path. It does not authorize bulk migration of `tpw_email_templates`, `tpw_payment_logs`, `tpw_core_system_pages`, post meta, or consumer-owned records.