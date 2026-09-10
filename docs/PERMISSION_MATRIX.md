# Permission Matrix & RBAC Specification

## Roles
1. **Admin** (`admin`): Full platform access, financial audit, staff management, package pricing, CMS.
2. **Staff** (`staff`): Operational field operations, customer search, collections, renewals, complaints.
3. **Customer** (`customer`): Portal access, account overview, invoices, ticket submission.

## Granular Permissions
- `customers.view`, `customers.create`, `customers.update`, `customers.delete`
- `connections.view`, `connections.create`, `connections.update`
- `pppoe.view_username`, `pppoe.view_password`, `pppoe.update`
- `packages.view`, `packages.create`, `packages.update`, `packages.archive`
- `areas.view`, `areas.create`, `areas.update`, `areas.archive`
- `billing.view`, `billing.create`, `billing.collect`, `billing.adjust`, `billing.refund`
- `payments.view`, `payments.create`, `payments.reverse`
- `renewals.view`, `renewals.create`
- `complaints.view`, `complaints.create`, `complaints.assign`, `complaints.update`, `complaints.resolve`, `complaints.close`
- `staff.view`, `staff.create`, `staff.update`, `staff.permissions`, `staff.salary`
- `expenses.view`, `expenses.create`, `expenses.update`, `expenses.void`
- `accounts.view`, `accounts.create`, `accounts.transfer`
- `reports.view`
- `sms.view`, `sms.send`, `sms.templates`, `sms.settings`
- `audit.view`
- `website.manage`
- `settings.manage`
