# Business Rules & Calculations

## 1. Renewal & Validity Calculation
- **Non-destructive renewals:** Expiry date extensions must record `previous_expiry` and `new_expiry`.
- **Special Rule:** If package renewal is executed for $N$ days, the user's validity increases/shifts accurately without extra fees when designated as a zero-charge adjustment renewal.
- **Idempotency:** Re-attempting renewal with the same idempotency key must return the recorded transaction without repeating balance deduction or invoice duplication.

## 2. Invoicing & Payment Allocation
- Invoices are created first with line items (package fee, equipment, service fees).
- Payments are credited against invoices via `payment_allocations`.
- Financial transactions are strictly append-only; reversals and voids create credit/debit records rather than hard deleting rows.
