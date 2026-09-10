# Database Schema Blueprint

```
users
  - id, name, email, password, phone, status, timestamps

roles
  - id, name, slug, description, timestamps

permissions
  - id, name, slug, group, timestamps

role_user
  - user_id, role_id

permission_role
  - role_id, permission_id

permission_user
  - user_id, permission_id

areas
  - id, parent_id, name, code, status, timestamps

packages
  - id, name, code, speed_mbps, description, is_active, timestamps

package_prices
  - id, package_id, price, validity_days, effective_from, effective_to, is_active, timestamps

customers
  - id, customer_code, user_id, name, phone, alt_phone, email, address, area_id, status, billing_day, join_date, balance, timestamps

connections
  - id, connection_code, customer_id, area_id, package_id, ip_address, mac_address, router_model, status, timestamps

pppoe_credentials
  - id, connection_id, username, password_encrypted, status, timestamps

invoices
  - id, invoice_number, customer_id, period_start, period_end, due_date, subtotal, discount, total, status, timestamps

payments
  - id, payment_number, customer_id, amount, method, account_id, reference, idempotency_key, collected_by, paid_at, timestamps

complaints
  - id, complaint_number, customer_id, assigned_to, subject, description, priority, status, resolved_at, timestamps

audit_logs
  - id, user_id, action, module, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at

account_transactions
  - id, account_id, transaction_number, type, debit, credit, balance_after, reference_type, reference_id, description, created_by, timestamps

account_transfers
  - id, transfer_number, from_account_id, to_account_id, amount, transfer_date, notes, transferred_by, timestamps

expense_categories
  - id, name, code, status, timestamps

expenses
  - id, expense_number, expense_category_id, account_id, amount, expense_date, title, description, paid_by, status, timestamps

salary_periods
  - id, name, start_date, end_date, status, timestamps

salary_payments
  - id, payroll_number, user_id, salary_period_id, account_id, basic_salary, bonus, commission, advance_deduction, other_deductions, net_salary, payment_date, status, notes, paid_by, timestamps

sms_gateways
  - id, name, driver, api_url, api_key, sender_id, extra_params, is_active, timestamps

sms_templates
  - id, name, code, template, is_auto_enabled, timestamps

sms_logs
  - id, recipient, customer_id, template_id, gateway_id, message, status, provider_message_id, error_message, sent_at, sent_by, entity_type, entity_id, timestamps
```
