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
```
