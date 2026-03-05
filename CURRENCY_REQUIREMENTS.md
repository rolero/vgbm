# Currency Requirements

## Goal
Support multi-currency amounts while keeping data entry efficient through portfolio-level defaults.

## Functional Requirements

1. **Portfolio default currency**
   - Each portfolio must have a `default_currency` (ISO 4217, e.g., `EUR`, `USD`, `GBP`).
   - This value is used as the initial currency when creating amounts under that portfolio.

2. **Per-amount currency override**
   - Every monetary amount must store both:
     - numeric value
     - currency code
   - Users must be able to manually change the currency per amount, regardless of the portfolio default.

3. **Coverage for amount fields**
   - Requirement applies to contract rent amount.
   - Requirement also applies to other amount-based fields (example: unit-level amounts).

4. **Validation**
   - Currency code must be a valid ISO 4217 3-letter code.
   - Amount value must be non-null and numeric.

## UX Behavior

- On amount entry forms:
  - Prefill currency selector with `portfolio.default_currency`.
  - Keep selector editable.
- On detail/list pages:
  - Display amount and currency together (e.g., `1,250.00 EUR`).

## Suggested Data Model

### Portfolio
- `id`
- `name`
- `default_currency` (string, 3 chars, ISO 4217)

### Amount-bearing entities (examples)
- `contract.rent_amount_value`
- `contract.rent_amount_currency`
- `unit.amount_value`
- `unit.amount_currency`

> Alternative: use a reusable embedded/value object style pair for `(amount_value, amount_currency)` wherever monetary values appear.

## API Expectations

- Portfolio create/update accepts `default_currency`.
- Endpoints that write monetary values must accept both value and currency fields.
- If currency omitted for a new amount, API may default to portfolio currency; if provided, API must preserve explicit override.

## Acceptance Criteria

1. Portfolio can be created/updated with default currency.
2. New contract rent amount defaults to portfolio currency.
3. User can override contract rent amount currency manually.
4. New unit amount defaults to portfolio currency.
5. User can override unit amount currency manually.
6. Persisted records retain explicitly selected currency.
7. UI always renders amount with its own currency.
