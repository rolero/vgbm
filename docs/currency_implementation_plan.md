# Currency Implementation Plan

This document translates `CURRENCY_REQUIREMENTS.md` into implementation tasks for backend, frontend, and QA.

## 1) Database design

## Portfolio table
Add a portfolio-level default:

```sql
ALTER TABLE portfolios
ADD COLUMN default_currency CHAR(3) NOT NULL DEFAULT 'EUR';
```

## Contract amounts
Store value + currency pair:

```sql
ALTER TABLE contracts
ADD COLUMN rent_amount_value NUMERIC(14,2) NOT NULL DEFAULT 0,
ADD COLUMN rent_amount_currency CHAR(3) NOT NULL DEFAULT 'EUR';
```

## Unit amounts
Store value + currency pair:

```sql
ALTER TABLE units
ADD COLUMN amount_value NUMERIC(14,2),
ADD COLUMN amount_currency CHAR(3);
```

## Validation constraints

```sql
ALTER TABLE portfolios
ADD CONSTRAINT chk_portfolios_currency
CHECK (default_currency ~ '^[A-Z]{3}$');

ALTER TABLE contracts
ADD CONSTRAINT chk_contract_rent_currency
CHECK (rent_amount_currency ~ '^[A-Z]{3}$');

ALTER TABLE units
ADD CONSTRAINT chk_units_amount_currency
CHECK (amount_currency IS NULL OR amount_currency ~ '^[A-Z]{3}$');
```

## 2) API contract

## Portfolio APIs
- `POST /portfolios` accepts `defaultCurrency`.
- `PATCH /portfolios/{id}` accepts `defaultCurrency`.

Example request:

```json
{
  "name": "Berlin Residential",
  "defaultCurrency": "EUR"
}
```

## Contract APIs
For contract create/update, include both fields:

```json
{
  "portfolioId": "p_123",
  "rentAmount": {
    "value": 1250.00,
    "currency": "EUR"
  }
}
```

Fallback behavior:
- If `rentAmount.currency` is omitted, backend defaults to the portfolio `defaultCurrency`.
- If supplied, backend preserves provided currency.

## Unit APIs
Use same money object shape:

```json
{
  "portfolioId": "p_123",
  "amount": {
    "value": 950.00,
    "currency": "USD"
  }
}
```

## 3) UI behavior

- Portfolio settings form includes `Default currency` selector.
- Contract/unit forms prefill currency from portfolio default.
- Currency selector remains editable per amount field.
- Read-only pages always render `value + currency` together.

## 4) Backfill and migration

For existing rows:
1. Set `portfolios.default_currency='EUR'` (or company-level configured default).
2. Fill missing contract/unit currency from related portfolio.
3. Run data-quality report for invalid/missing currencies.

## 5) QA test matrix

1. Create portfolio with `EUR`, verify persisted default.
2. Create contract rent without explicit currency, verify `EUR` default applied.
3. Create contract rent with `USD`, verify override retained.
4. Create unit amount without currency, verify portfolio default applied (if field required) or persisted null per chosen model.
5. Update portfolio default from `EUR` to `GBP`, verify existing records remain unchanged.
6. Validate rejection of invalid currency codes (`EURO`, `EU`, `123`).

## 6) Non-goals (current phase)

- FX conversion logic
- Currency-rate ingestion
- Cross-currency aggregation normalization

Those can be added in a future phase after baseline currency integrity is implemented.
