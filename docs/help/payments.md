# Payments

## Overview
Payments provides lightweight helpers and webhooks to log completed transactions and expose settings like currency for dependent plugins.

## Current Payment Methods Status
- Released methods: Bank Transfer (BACS), Cheque, Cash, Card on the day, and Square.
- SumUp and WooCommerce are unreleased, dormant compatibility records. They must not be surfaced in settings, customer UI, or checkout flows even when legacy storage rows exist.
- A checkout method must be released, administrator-active, configured, and runtime-available. See the canonical [payment-method contract](../architecture/payments/tpw-core-payment-method-contract.md).
- Square remains visible because the shared framework still preserves its compatibility-era configuration state, even when the TPW Square Gateway add-on is not active.

## Key Screens / Shortcodes
- Settings → iLungu™ Club → Payment Methods (shared gateway enablement and configuration)
- Webhook endpoint(s): modules/payments/webhook.php (for gateways to call)

## Hooks
- tpw_payment_completed (action) — Fires when a gateway webhook marks a payment completed. Args: gateway, reference, email, amount, payload.

## Extending
- Subscribe to tpw_payment_completed to update your domain models (orders, entries). Validate payloads and idempotency yourself.
- Use get_option('flexievent_settings') for currency_symbol and currency_code where needed.
- Use `TPW_Payments_Manager::get_usable_methods()` to build a checkout selector and `TPW_Payments_Manager::is_method_usable()` to validate its submission. `get_active_methods()` is a backwards-compatible stored-preference API, not a checkout gate.
- For payment-log source migrations, register canonical and legacy aliases with `TPW_Payment_Source_Registry::register_source_aliases()`. New log writes use the canonical source; historical `tpw_payment_logs.plugin` values remain readable as one logical source. See [the canonical identity compatibility contract](../architecture/tpw-core-canonical-legacy-identity-compatibility-contract.md).

## References
- Developer Guide → ../developer-guide.md
- Logger: modules/payments/class-tpw-payment-logger.php
- Settings UI: modules/payments/class-tpw-payments-admin.php

See also: Shared Framework Hooks Index → ../developer-guide.md#core-hooks-index
