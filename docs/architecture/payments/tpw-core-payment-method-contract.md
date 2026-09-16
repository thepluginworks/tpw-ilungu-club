# TPW Core Payment Method Contract

This is the canonical contract for iLungu Club payment-method discovery and checkout eligibility.

## Method States

A payment method may be represented by a stored row in `tpw_payment_methods`, but a stored row alone does not make the method customer-facing.

1. **Released/supported**: iLungu Club currently ships and supports the method.
2. **Active**: an administrator has enabled the stored method row.
3. **Configured**: all settings required by the method are present.
4. **Available**: all required runtime integrations or add-ons are active.
5. **Usable**: the method is released, active, configured, and available.

Checkout consumers must offer and accept only usable methods.

## Released Method Registry

The currently released payment methods are:

- `bacs`
- `cheque`
- `cash`
- `card-on-the-day`
- `square`

`sumup` and `woocommerce` are unreleased. They may remain as dormant legacy rows in `tpw_payment_methods`, but are unsupported records and must not appear in iLungu Club UI, checkout-safe APIs, or customer-facing payment selectors. A future product release must deliberately add a method to the released registry and define its configuration and availability requirements.

## Shared APIs

- `TPW_Payments_Manager::get_active_methods()` exposes stored administrator active preferences for backwards compatibility. It is not a checkout-safety contract.
- `TPW_Payments_Manager::get_usable_methods()` is the canonical checkout-safe discovery API. It returns only released, active, configured, and available methods.
- `TPW_Payments_Manager::is_method_usable( string $slug )` is the canonical server-side validator for a submitted method.

Consumer plugins must use `get_usable_methods()` to build payment selectors and `is_method_usable()` before processing submitted payment methods. They must not query `tpw_payment_methods` directly for checkout eligibility.

## Configuration And Runtime Availability

| Method | Configuration | Runtime availability |
| --- | --- | --- |
| `bacs` | Account name, account number, and sort code | No additional dependency |
| `cheque` | Payable-to name | No additional dependency |
| `cash` | Customer instruction message | No additional dependency |
| `card-on-the-day` | Customer instruction message | No additional dependency |
| `square` | Application ID, access token, and location ID | TPW Square Gateway add-on is active |

## Storage And Migration

New payment-method rows default to inactive. Existing legacy rows are not automatically migrated or deactivated. This preserves a working administrator preference on existing sites while the usable-method API prevents incomplete or unsupported methods from reaching checkout.

## Rollout

1. Update this contract and its discoverability links.
2. Add or update the shared usable-method API.
3. Update each consumer plugin in its own scoped implementation to use the canonical API.
4. Do not expose new payment methods because a database row exists.