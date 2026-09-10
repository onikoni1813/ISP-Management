# ISP MANAGEMENT PLATFORM
## Antigravity Master Development Plan

---

# 0. ROLE

You are the lead software architect, senior Laravel/Vue developer, database architect, security engineer, PWA/offline engineer, QA engineer, and deployment engineer for this project.

Your responsibility is to build a production-ready ISP Management Platform.

Do NOT treat this as a simple CRUD application.

The system must be designed from day one to support:

- Admin management
- Staff operations
- Customer management
- ISP package management
- Area management
- Billing
- Payment collection
- Renewal
- Due management
- PPPoE credentials
- Complaint management
- Staff accountability
- Staff salary
- Staff commission
- Business expenses
- Cash/bank/bKash/Nagad accounts
- Income/expense tracking
- Profit/loss reporting
- SMS Gateway
- Automatic SMS
- Audit logs
- Offline-first Staff PWA
- Customer Portal
- Public Website
- Future online payment
- Future expansion

The application will initially be used as a PWA and must be deployable on shared hosting/cPanel.

The public website will be built from the same Laravel application and database in the future.

---

# 1. NON-NEGOTIABLE ARCHITECTURE PRINCIPLES

Follow these rules throughout the project.

## Rule 1 — One source of truth

Use:

Laravel Backend
+
MySQL Database
+
Vue 3 / Inertia Frontend

Do not create separate duplicated business logic for Admin, Staff, Customer Portal, and Website.

---

## Rule 2 — Database first

Never design the database casually while implementing UI.

Before implementing a module:

1. Check existing database schema.
2. Check existing relationships.
3. Check business rules.
4. Check whether historical data must be preserved.
5. Design migration.
6. Implement models/relationships.
7. Add tests.
8. Then implement UI.

---

## Rule 3 — Financial data must be historically safe

Never hard-delete financial records.

Do not store billing state using simplistic fields such as:

paid = true

Instead use:

Invoices
Invoice Items
Payments
Payment Allocations
Adjustments
Refunds/Reversals

Financial history must remain auditable.

---

## Rule 4 — Do not overwrite historical pricing

Package prices can change.

Therefore customer package history and invoice item prices must preserve the price that was actually used at that time.

Never calculate an old invoice using the package's current price.

---

## Rule 5 — Offline must be designed from the beginning

Do NOT build the entire application first and then attempt to add offline support.

The architecture must remain compatible with:

- IndexedDB
- Service Worker
- Offline cache
- Offline mutations
- Sync queue
- Retry
- Idempotency
- Conflict detection
- Sync status

---

## Rule 6 — Duplicate financial transactions must be impossible

Offline synchronization and network retry can send the same payment more than once.

Every offline-created mutation must have a unique client-generated UUID/idempotency key.

The server must reject duplicate processing of the same operation.

---

## Rule 7 — Authorization must be server-side

Never rely only on hiding UI buttons.

Every protected operation must be checked on the Laravel backend using:

- Policies
- Gates
- Permissions
- Form Requests
- Middleware where appropriate

---

## Rule 8 — Sensitive PPPoE passwords

PPPoE passwords must NOT be stored as plain text.

Use Laravel encryption for credentials that must later be displayed.

Password visibility must be permission-controlled.

Staff must only see PPPoE passwords if explicitly granted the permission:

pppoe.view_password

Viewing a PPPoE password should be auditable.

---

## Rule 9 — No unnecessary technology

The production target is shared hosting/cPanel.

Avoid dependencies that require a permanently running server unless absolutely necessary.

Prefer:

- Laravel
- MySQL/MariaDB
- Vue
- Inertia
- Tailwind
- Vite
- Database queue
- Laravel Scheduler
- cPanel Cron

Do not require Docker, Redis, Supervisor, WebSocket servers, Node runtime processes, etc. for basic production operation.

---

# 2. TARGET STACK

Use the latest stable Laravel version available when development starts.

Backend:

- PHP
- Laravel
- MySQL/MariaDB

Frontend:

- Vue 3
- Inertia.js
- Tailwind CSS
- Vite

Authentication:

- Laravel-supported authentication solution

Authorization:

- Laravel Policies/Gates
- Role/permission architecture

PWA:

- Service Worker
- Web App Manifest
- IndexedDB

Testing:

- PHPUnit/Pest according to project conventions

Deployment:

- cPanel/shared hosting
- HTTPS
- MySQL
- Cron

---

# 3. APPLICATION AREAS

The application must have these domains:

## Admin

/admin

## Staff

/staff

## Customer

/account

## Public Website

/

## API

/api/v1

Do not mix authorization boundaries between these areas.

---

# 4. USERS AND ROLES

Initial roles:

- Admin
- Staff
- Customer

But do NOT hard-code role logic everywhere.

Build a scalable permission system.

Future roles may include:

- Super Admin
- Manager
- Accountant
- Technician
- Support
- Customer

---

# 5. PERMISSION SYSTEM

Create granular permissions.

Examples:

customers.view
customers.create
customers.update
customers.delete

connections.view
connections.create
connections.update

pppoe.view_username
pppoe.view_password
pppoe.update

packages.view
packages.create
packages.update
packages.archive

areas.view
areas.create
areas.update
areas.archive

billing.view
billing.create
billing.collect
billing.adjust
billing.refund

payments.view
payments.create
payments.reverse

renewals.view
renewals.create

complaints.view
complaints.create
complaints.assign
complaints.update
complaints.resolve
complaints.close

staff.view
staff.create
staff.update
staff.permissions
staff.salary

expenses.view
expenses.create
expenses.update
expenses.void

accounts.view
accounts.create
accounts.transfer

reports.view

sms.view
sms.send
sms.templates
sms.settings

audit.view

website.manage

settings.manage

Do not assume that every Staff user has the same permissions.

---

# 6. DATABASE ARCHITECTURE

Design the database before building major UI.

Core identity:

- users
- roles
- permissions
- role_user
- permission_role

Customer domain:

- customers
- customer_contacts
- customer_addresses
- customer_notes
- customer_identifiers

Location:

- areas

Connection:

- connections
- pppoe_credentials

Packages:

- packages
- package_prices
- package_features
- customer_packages

Billing:

- billing_cycles
- invoices
- invoice_items
- payments
- payment_allocations
- refunds/reversals or credit/debit adjustment structure

Renewal:

- renewals

Complaints:

- complaints
- complaint_comments
- complaint_assignments
- complaint_status_history
- complaint_attachments

Staff:

- staff_profiles
- salary_structures
- salary_periods
- salary_components
- salary_advances
- salary_deductions
- salary_payments

Commission:

- commission_rules
- commission_entries

Accounting:

- accounts
- account_transactions
- account_transfers
- expense_categories
- expenses
- expense_attachments

SMS:

- sms_gateways
- sms_templates
- sms_logs
- sms_campaigns
- sms_campaign_recipients

Audit:

- audit_logs

Notifications:

- notifications

Offline/PWA:

- device registrations if required
- sync-related server structures if required

Website:

- pages
- menus
- banners
- faqs
- notices
- SEO/settings structures

System:

- settings

Do not blindly create every table at once if a better normalized design is required. Validate relationships before implementation.

---

# 7. CUSTOMER MODEL

A customer must have a unique customer code.

Example:

CUST-000001

Do not use the database numeric ID as the public customer identifier.

Customer fields should support:

- Name
- Status
- Join date
- Billing day
- Contact information
- Address
- Area
- Notes
- Created by
- Updated by

Customer status:

- Active
- Expired
- Suspended
- Disconnected
- Pending
- Archived

---

# 8. CUSTOMER AND CONNECTION MUST BE SEPARATE

A customer and an ISP connection are not necessarily the same entity.

Use:

Customer
↓
Connection

Example:

Customer:
CUST-000125

Connection:
CON-000875

This allows future support for multiple connections under one customer.

---

# 9. PPPoE

Create a dedicated PPPoE credential structure.

Minimum:

- connection_id
- username
- encrypted password
- status
- timestamps

Do not store the password as plaintext.

Staff access must be permission-controlled.

---

# 10. AREA

Use hierarchical areas.

Table:

areas

Fields should support:

- id
- parent_id
- name
- code
- status
- timestamps

This allows:

Area
→ Zone
→ Sub-zone

even if only one level is initially used.

---

# 11. PACKAGES

Packages must support:

- Name
- Code
- Speed
- Description
- Status

Prices must be historically versioned.

Use a package_prices structure with:

- package_id
- price
- validity_days
- effective_from
- effective_to
- status

Never modify historical billing records when a package price changes.

---

# 12. CUSTOMER PACKAGE HISTORY

Use customer_packages.

Store:

- customer_id
- connection_id if appropriate
- package_id
- actual applied price
- start date
- end date
- assigned_by
- status

This preserves package history.

---

# 13. BILLING ENGINE

Build billing as a proper transaction system.

Required entities:

billing_cycles
invoices
invoice_items
payments
payment_allocations

Invoice:

- invoice number
- customer
- billing period
- due date
- subtotal
- discount
- tax if enabled
- total
- status
- timestamps

Invoice item examples:

- Internet package
- Previous due
- Installation
- Equipment
- Late fee
- Service charge
- Discount

---

# 14. PAYMENT ENGINE

Payment fields should support:

- payment number
- customer
- amount
- payment method
- account
- reference
- paid_at
- collected_by
- status
- notes
- idempotency/client UUID

Payment methods should be configurable but initially support:

- Cash
- bKash
- Nagad
- Bank
- Other

Do not directly mutate invoice totals without transaction history.

---

# 15. PAYMENT ALLOCATION

Payments must support:

- Full payment
- Partial payment
- Multiple invoice allocation
- Advance payment where business rules allow it

Example:

Payment = ৳700

Invoice A = ৳500
Invoice B = ৳200

The payment allocation table must preserve this relationship.

---

# 16. REFUND / REVERSAL / ADJUSTMENT

Do not delete a payment after it has been recorded.

Support:

- Void
- Reverse
- Refund
- Credit adjustment
- Debit adjustment

Every financial correction must create a traceable history.

---

# 17. RENEWAL ENGINE

Renewal must be a proper business transaction.

A renewal should record:

- Customer
- Connection
- Package
- Invoice
- Payment
- Previous expiry
- New expiry
- Amount
- Renewed by
- Renewal date

Do not simply update expiry_date and discard the old value.

---

# 18. EXPIRY LOGIC

Define exactly how expiry is calculated.

Do not allow different controllers/components to calculate dates differently.

Create centralized business logic such as:

RenewalService
BillingService

Use one authoritative calculation method.

Test:

- Normal renewal
- Expired customer renewal
- Early renewal
- Partial payment
- Package change
- Leap year
- Month-end
- Different validity periods

---

# 19. COMPLAINT SYSTEM

Complaint fields:

- complaint number
- customer
- subject
- description
- priority
- status
- assigned staff
- created by
- resolved by
- resolution note
- timestamps

Statuses:

Open
Assigned
In Progress
Resolved
Closed
Cancelled

Priorities:

Low
Normal
High
Urgent

Track every status change.

---

# 20. STAFF MANAGEMENT

Staff profile:

- Name
- Mobile
- Role
- Joining date
- Status
- Salary type
- Basic salary
- Notes

Staff activity must be auditable.

---

# 21. STAFF SALARY

Support:

Basic salary
Bonus
Commission
Overtime
Advance
Deduction
Net salary
Payment

Salary must be period-based.

Example:

September 2026

Basic:
৳20,000

Bonus:
৳2,000

Commission:
৳1,500

Deduction:
৳500

Net:
৳23,000

Do not overwrite previous salary periods.

---

# 22. STAFF COMMISSION

Prepare architecture for configurable commission rules.

Examples:

New customer:
৳50

Renewal:
৳10

Collection:
1%

Use:

commission_rules
commission_entries

Commission entries must identify the staff member and source transaction.

---

# 23. EXPENSE MANAGEMENT

Admin can record all business expenses.

Categories:

- Bandwidth
- Upstream
- Server
- Hosting
- Electricity
- Office rent
- Transport
- Cable
- Equipment
- Maintenance
- Marketing
- SMS
- Salary
- Other

Expense must include:

- category
- amount
- account
- date
- description
- paid_by
- approval if required
- status
- attachment where applicable

---

# 24. ACCOUNT MANAGEMENT

Create accounts/wallets such as:

- Cash
- Bank
- bKash
- Nagad
- Rocket
- Other

Every money movement should be traceable.

Customer payment:

+৳500 to selected account

Expense:

-৳8,500 from selected account

Transfer:

Cash
-৳20,000

Bank
+৳20,000

Transfers must not incorrectly count as income or expense.

---

# 25. ACCOUNT TRANSACTIONS

Create an account transaction ledger.

Transaction types may include:

- Customer Payment
- Expense
- Salary
- Refund
- Transfer
- Adjustment

Do not calculate account balances only from arbitrary UI fields.

---

# 26. ACCOUNTING SCOPE

Version 1 does not need to become a full ERP.

Implement:

- Income
- Expense
- Salary
- Cash
- Bank
- Wallet accounts
- Account transfers
- Cash flow
- Revenue
- Expense
- Profit/Loss

But design the data model so future double-entry accounting can be added without rewriting all financial modules.

Future:

- Chart of Accounts
- Journal
- Ledger
- Debit
- Credit
- Trial Balance
- Balance Sheet

---

# 27. PROFIT / LOSS

Admin dashboard/report must be able to show:

Revenue
- Refunds/adjustments
- Operating expenses
- Salary
= Net Profit

Clearly distinguish:

Cash balance
from
Profit.

Do not treat them as the same concept.

---

# 28. SMS SYSTEM

Use an abstraction layer.

Do not hard-code one SMS provider throughout the application.

Architecture:

SmsGatewayInterface
↓
SmsService
↓
Gateway Driver

Support future gateway changes.

---

# 29. SMS FEATURES

Admin can:

- Configure gateway
- Send manual SMS
- Send custom SMS
- Create templates
- Edit templates
- Enable/disable automatic messages
- View SMS history
- View failed messages
- Retry failed messages

Templates:

- Payment Received
- Expiry Warning
- Expired
- Renewal
- Complaint Created
- Complaint Resolved
- Custom

Variables:

{name}
{customer_code}
{package}
{amount}
{expiry_date}
{due}

---

# 30. AUTOMATIC SMS

Use Laravel Scheduler + cPanel Cron.

Examples:

3 days before expiry
1 day before expiry
Expiry day
Expired

Also:

Payment received
Renewal completed
Complaint created
Complaint resolved

Prevent duplicate automatic SMS for the same event.

---

# 31. SMS LOGGING

Every SMS must have:

- recipient
- template
- message
- gateway
- status
- provider message ID if available
- sent_at
- error
- sent_by
- related entity where applicable

---

# 32. AUDIT LOG

Create a global audit system.

Record:

- user
- action
- module
- entity type
- entity ID
- old values
- new values
- IP
- user agent
- timestamp

Important actions:

- Login
- Customer created
- Customer updated
- Package changed
- Payment created
- Payment reversed
- Renewal
- Complaint assignment
- Complaint resolution
- SMS sent
- PPPoE password viewed
- PPPoE password changed
- Permission changed
- Expense created
- Salary paid
- Account transfer

---

# 33. STAFF ACTIVITY REPORT

Admin must be able to filter:

- Staff
- Action
- Customer
- Area
- Module
- Date range

Example:

Staff = Rahim
Action = Payment Collected
Date = September 2026

Show:

Number of collections
Total amount
Customers
Individual transactions

---

# 34. PWA REQUIREMENTS

The application must be installable as a PWA.

Requirements:

- manifest
- icons
- service worker
- cache strategy
- offline fallback
- IndexedDB
- sync queue
- connection status
- last sync timestamp
- update notification

---

# 35. OFFLINE-FIRST STAFF MODE

Staff must be able to work with previously synchronized data when internet is unavailable.

Offline features should support, where business rules permit:

- Customer search
- Customer profile
- Package information
- Area information
- PPPoE information subject to permission
- Complaint creation
- Complaint update
- Payment collection
- Renewal workflows if safely supported

Clearly indicate:

ONLINE
or
OFFLINE

in the UI.

---

# 36. OFFLINE DATA STORAGE

Do NOT use localStorage as the primary database for large application data.

Use IndexedDB.

Recommended conceptual architecture:

Vue
↓
Repository/Service Layer
↓
IndexedDB
↓
Sync Queue
↓
Laravel API
↓
MySQL

Do not let Vue components directly manipulate sync logic.

---

# 37. OFFLINE MUTATION

Every offline action must have:

- UUID
- device ID
- user ID
- entity
- action
- payload
- timestamp
- status
- retry count
- error information

When internet returns:

Pending
↓
Syncing
↓
Success

or

Pending
↓
Syncing
↓
Failed
↓
Retry

---

# 38. IDEMPOTENCY

Every financial mutation must be idempotent.

Especially:

- Payment
- Renewal
- Refund
- Account transfer

If the same request is submitted twice, the server must not create two financial transactions.

---

# 39. CONFLICT HANDLING

When local and server versions differ:

Do not blindly overwrite.

Use:

- updated_at/version
- server version
- local version
- conflict status
- appropriate resolution strategy

Financial transactions should use append-only/idempotent transaction logic wherever possible.

---

# 40. PPPoE OFFLINE SECURITY

Because PPPoE passwords are sensitive:

- Do not cache more credentials than necessary.
- Respect staff permission.
- Encrypt sensitive local data where practical.
- Clear sensitive local data on logout/device revoke according to security policy.
- Audit password viewing.
- Never expose passwords in logs.
- Never expose passwords in API responses unless explicitly authorized.

---

# 41. SEARCH

Customer search must be fast.

Search by:

- Customer Code
- Name
- Mobile
- PPPoE Username
- MAC
- IP
- Area

Add appropriate database indexes.

On mobile Staff UI, search must be accessible immediately.

---

# 42. CUSTOMER PROFILE

The customer profile must be the central operational screen.

Show:

Customer information
↓
Connection
↓
Package
↓
PPPoE
↓
Current billing status
↓
Due
↓
Payment history
↓
Renewal history
↓
Complaints
↓
SMS history
↓
Activity history

Staff should be able to perform permitted actions without navigating through many pages.

---

# 43. ADMIN DASHBOARD

Show:

- Total customers
- Active customers
- Expired customers
- Suspended customers
- Today's collection
- Monthly collection
- Total outstanding due
- Today's expenses
- Monthly expenses
- Salary
- Net profit
- Open complaints
- Pending/failed SMS
- Account balances

Also provide:

- Area-wise customers
- Package-wise customers
- Staff-wise collection
- Revenue vs expense chart

---

# 44. STAFF DASHBOARD

Keep it simple and fast.

Show:

- Customer search
- Today's collection
- Today's renewals
- Assigned complaints
- Open complaints
- Pending offline sync
- Last sync
- Quick actions

Quick buttons:

Search Customer
Collect Payment
Renew
New Complaint

---

# 45. CUSTOMER PORTAL

Build the backend architecture from the beginning even if the portal is implemented later.

Customer should eventually see:

- Current package
- Expiry date
- Current due
- Invoices
- Payment history
- Renewal
- Complaints
- Complaint status
- Profile
- Notifications

---

# 46. PUBLIC WEBSITE

The same Laravel project must support a public website.

Initial pages:

Home
About
Packages
Coverage Areas
FAQ
Notice
Contact
Login

Package information should come from the same database.

If Admin changes package pricing/content, public website should reflect it according to publishing rules.

---

# 47. WEBSITE CMS

Prepare CMS structures for:

- Pages
- Menus
- Banners
- FAQ
- Notices
- Footer
- SEO title
- SEO description
- Slug
- Publish status

Admin should eventually manage these from the Admin Panel.

---

# 48. FUTURE ONLINE PAYMENT

Do not implement a specific provider unless requested.

But architecture must support:

Payment Initiated
↓
Gateway
↓
Callback/Webhook
↓
Server-side verification
↓
Payment Created
↓
Invoice Allocation
↓
Account Transaction
↓
SMS

Never mark payment successful merely because the browser says "success".

---

# 49. REPORTS

Required reports:

## Customer

- Active
- Expired
- Suspended
- Area-wise
- Package-wise

## Billing

- Daily
- Monthly
- Custom date
- Due
- Collection
- Renewal

## Staff

- Collection
- Renewal
- Complaints
- Activity

## Accounting

- Income
- Expense
- Salary
- Cash flow
- Account balance
- Profit/Loss

## SMS

- Sent
- Failed
- Cost if provider supplies cost
- Template
- Date

---

# 50. EXPORT

Support where appropriate:

- CSV
- Excel
- PDF
- Print

Reports must respect permissions.

---

# 51. BACKUP

Production must have a backup strategy.

Consider:

- Database backup
- Files backup
- Scheduled backup
- Backup retention
- Restore procedure

Do not claim backup is working until it has been tested.

---

# 52. SECURITY

Mandatory:

- HTTPS
- CSRF protection
- XSS protection
- SQL injection protection
- Authentication
- Authorization
- Rate limiting
- Secure password hashing
- Encrypted PPPoE credentials
- Secure sensitive settings
- Session security
- Audit logs

Admin should optionally support 2FA.

---

# 53. DATABASE RULES

Use:

- Foreign keys
- Appropriate indexes
- Unique constraints
- Nullable fields only when logically justified
- Enum/status strategy that can evolve safely
- Timestamps
- Soft deletes only where appropriate
- Transactions for multi-table financial operations

Do not use database fields merely because they are convenient for the UI.

Database should represent business reality.

---

# 54. SERVICE LAYER

Do not put complex business logic inside controllers.

Use services such as:

CustomerService
BillingService
PaymentService
RenewalService
ComplaintService
SmsService
AccountingService
SalaryService
AuditService
SyncService

Use events/listeners where useful.

---

# 55. TRANSACTION SAFETY

Use database transactions for operations such as:

Payment creation
Payment allocation
Renewal
Refund
Account transfer
Salary payment
Expense posting

If one required part fails, the financial transaction should not be partially committed.

---

# 56. VALIDATION

Use Laravel Form Requests or equivalent structured validation.

Validate:

- Customer data
- Mobile numbers
- Package price
- Payment amount
- Renewal dates
- Staff salary
- Expense
- SMS recipient/message
- PPPoE username
- Permissions

Never trust frontend validation alone.

---

# 57. TESTING

Every important business rule needs tests.

Test:

Customer creation
Customer search
Package creation
Package price history
Invoice calculation
Due calculation
Partial payment
Payment allocation
Payment reversal
Renewal
Expiry calculation
Staff permissions
PPPoE password authorization
Complaint workflow
Salary calculation
Expense
Account transfer
Profit/Loss
SMS template rendering
Duplicate payment prevention
Offline sync
Retry
Conflict detection

---

# 58. UI PRINCIPLES

Mobile-first for Staff.

Desktop-friendly for Admin.

Avoid unnecessary clicks.

Staff workflow:

Search
↓
Customer
↓
Collect/Renew/Complaint
↓
Confirm
↓
Receipt/Result

Admin workflow can be more detailed.

Use reusable components.

Do not duplicate table/filter/modal implementations unnecessarily.

---

# 59. DESIGN SYSTEM

Create reusable:

- Buttons
- Inputs
- Selects
- Date pickers
- Modals
- Tables
- Pagination
- Search box
- Filters
- Status badges
- Empty states
- Loading states
- Error states
- Confirmation dialogs
- Toast notifications
- Receipt components

Maintain consistent UX.

---

# 60. ERROR HANDLING

Never silently fail.

For important operations show:

Success
Warning
Error

For offline operations show:

Saved offline
Waiting for sync
Syncing
Synced
Sync failed

---

# 61. AUDITABILITY

The system must answer:

Who did this?
When?
To which customer?
What changed?
What was the previous value?
What is the new value?

This applies especially to:

Payments
Renewals
Customer changes
Package changes
PPPoE credentials
Complaints
Expenses
Salary
Permissions

---

# 62. DEVELOPMENT MILESTONES

Do NOT attempt the entire project in one operation.

Work sequentially.

---

## MILESTONE 0 — PROJECT AUDIT & ARCHITECTURE

Before writing major code:

1. Inspect the existing project.
2. Identify Laravel version.
3. Identify Vue/Inertia setup.
4. Inspect composer.json.
5. Inspect package.json.
6. Inspect existing routes.
7. Inspect existing migrations/models.
8. Inspect environment assumptions.
9. Determine whether this is a fresh project.
10. Create architecture documentation.

Create/update:

docs/PROJECT_ARCHITECTURE.md
docs/DATABASE_SCHEMA.md
docs/BUSINESS_RULES.md
docs/PERMISSION_MATRIX.md
docs/OFFLINE_SYNC_SPEC.md
docs/SMS_SPEC.md
docs/ACCOUNTING_SPEC.md
docs/API_SPEC.md
docs/TESTING_QA_SPEC.md
docs/DEPLOYMENT_SPEC.md

Do not delete existing useful work.

At the end provide:

- Files inspected
- Existing architecture
- Problems discovered
- Proposed architecture
- Files created/changed
- Risks
- Next milestone

STOP after completing the milestone.

---

# MILESTONE 1 — FOUNDATION

Implement:

- Authentication
- Users
- Roles
- Permissions
- Base layouts
- Admin layout
- Staff layout
- Responsive navigation
- PWA foundation

Add tests.

Run tests.

Do not continue automatically to Milestone 2.

---

# MILESTONE 2 — DATABASE CORE

Implement:

- Areas
- Packages
- Package prices
- Customers
- Contacts
- Addresses
- Connections
- PPPoE credentials
- Customer package history

Implement migrations, models, relationships, factories, seeders and tests.

Verify database integrity.

STOP.

---

# MILESTONE 3 — CUSTOMER CRM

Implement:

- Customer CRUD
- Customer search
- Customer profile
- Connection management
- PPPoE display
- Package assignment
- Package history
- Area assignment
- Notes

Implement permission checks.

Implement audit logging.

STOP.

---

# MILESTONE 4 — BILLING ENGINE

Implement:

- Billing cycles
- Invoices
- Invoice items
- Payments
- Payment allocations
- Due
- Receipts

Use database transactions.

Add comprehensive tests.

Test partial payments.

Test multiple invoice allocation.

Test duplicate payment prevention.

STOP.

---

# MILESTONE 5 — RENEWAL ENGINE

Implement:

- Renewal
- Expiry calculation
- Package change
- Renewal history
- Payment integration
- Invoice integration
- Receipt
- Audit

Test edge cases.

STOP.

---

# MILESTONE 6 — STAFF OPERATIONS

Implement:

- Staff dashboard
- Customer search
- Payment collection
- Renewal
- Complaint access
- PPPoE access based on permission
- Staff activity

Optimize mobile UI.

STOP.

---

# MILESTONE 7 — AUDIT & ACCOUNTABILITY

Implement:

- Audit logs
- Activity log
- Staff filters
- Customer filters
- Date filters
- Action filters
- Sensitive action logging

Create admin reports.

STOP.

---

# MILESTONE 8 — COMPLAINT SYSTEM

Implement:

- Complaint creation
- Assignment
- Status
- Priority
- Comments
- Resolution
- Attachments
- Status history
- Staff accountability
- Notifications

STOP.

---

# MILESTONE 9 — ACCOUNTING

Implement:

- Accounts
- Account transactions
- Account transfers
- Expense categories
- Expenses
- Salary
- Salary periods
- Salary payments
- Advances
- Deductions
- Commission

Implement transaction-safe accounting operations.

STOP.

---

# MILESTONE 10 — REPORTING

Implement:

- Collection reports
- Due reports
- Renewal reports
- Staff reports
- Complaint reports
- Expense reports
- Salary reports
- Account reports
- Profit/Loss
- Cash flow

Add filters and exports.

STOP.

---

# MILESTONE 11 — SMS ENGINE

Implement:

- SMS gateway configuration
- Gateway abstraction
- Templates
- Variables
- Manual SMS
- SMS logs
- Failed SMS
- Retry

Do not hard-code one provider.

STOP.

---

# MILESTONE 12 — AUTOMATED SMS

Implement:

- Expiry reminders
- Expired messages
- Payment confirmation
- Renewal message
- Complaint notifications

Use Scheduler + queue.

Prevent duplicates.

STOP.

---

# MILESTONE 13 — OFFLINE PWA

Implement:

- Service Worker
- IndexedDB
- Offline cache
- Customer cache
- Package cache
- Area cache
- Complaint cache
- Offline payment workflow
- Offline mutation queue
- Sync
- Retry
- Idempotency
- Sync status
- Conflict detection

Test with actual network disconnection.

STOP.

---

# MILESTONE 14 — CUSTOMER PORTAL

Implement:

/account

Features:

- Login
- Dashboard
- Package
- Expiry
- Due
- Invoices
- Payments
- Renewal
- Complaints
- Profile
- Notifications

STOP.

---

# MILESTONE 15 — PUBLIC WEBSITE

Implement:

/

Pages:

Home
About
Packages
Coverage
FAQ
Notice
Contact
Login

Use database-driven content where appropriate.

STOP.

---

# MILESTONE 16 — WEBSITE CMS

Implement Admin CMS:

- Pages
- Menus
- Banners
- FAQ
- Notices
- SEO
- Publish/unpublish

STOP.

---

# MILESTONE 17 — SECURITY HARDENING

Audit:

- Authentication
- Authorization
- IDOR
- CSRF
- XSS
- SQL injection
- Rate limiting
- Sensitive data
- PPPoE credentials
- Session security
- File upload security
- Audit logs

Run tests.

STOP.

---

# MILESTONE 18 — PERFORMANCE

Optimize:

- Database indexes
- N+1 queries
- Eager loading
- Pagination
- Search
- API responses
- PWA cache
- Large reports

Do not sacrifice correctness for premature optimization.

STOP.

---

# MILESTONE 19 — PRODUCTION QA

Perform complete end-to-end testing.

Test:

Admin
Staff
Customer
Billing
Payment
Renewal
Complaint
SMS
Accounting
PWA
Offline
Sync
Reports
Security

Fix all critical/high severity issues.

STOP.

---

# MILESTONE 20 — SHARED HOSTING DEPLOYMENT

Prepare:

- Production .env
- Database
- Storage
- Permissions
- Build assets
- Queue configuration
- Scheduler
- Cron
- SSL
- Cache
- Backup
- PWA
- Production health check

Target:

billing.example.com

Public website can later use:

example.com

STOP.

---

# 63. GIT CHECKPOINT RULE

At the end of every milestone:

1. Run tests.
2. Check migrations.
3. Check routes.
4. Check permissions.
5. Check UI.
6. Check mobile.
7. Check security-sensitive operations.
8. Review changed files.
9. Update documentation.
10. Create a Git commit.

Commit format:

feat(module): milestone description

Example:

feat(billing): implement invoice and payment engine

Do not create a commit if tests are failing unless explicitly instructed.

---

# 64. ANTIGRAVITY EXECUTION RULE

When I give you a milestone instruction:

1. Read the Master Plan.
2. Read the relevant documentation.
3. Inspect existing implementation.
4. Do not assume missing details.
5. Do not invent conflicting architecture.
6. Implement only the requested milestone.
7. Preserve existing working functionality.
8. Run tests.
9. Fix failures caused by your changes.
10. Update documentation.
11. Provide a checkpoint report.
12. STOP.

Do not automatically continue to the next milestone.

---

# 65. WHEN YOU FIND A DESIGN PROBLEM

If you discover that the current implementation conflicts with this architecture:

Do NOT silently create a workaround.

Report:

Problem
Why it matters
Affected files
Affected database structures
Recommended solution
Migration risk

Then choose the safest compatible solution unless the change requires a major architectural decision.

---

# 66. NO FAKE IMPLEMENTATION

Do not create fake:

- API success
- SMS success
- payment success
- sync success
- accounting balance
- profit calculation
- authentication
- permission checks

A feature is only considered complete when the real backend logic works.

---

# 67. DEFINITION OF DONE

A milestone is NOT complete just because the UI exists.

A milestone is complete only when:

[ ] Database implemented
[ ] Relationships correct
[ ] Backend implemented
[ ] Authorization implemented
[ ] Validation implemented
[ ] UI implemented
[ ] Mobile UI checked
[ ] Error handling implemented
[ ] Audit logging implemented where required
[ ] Tests written
[ ] Tests passing
[ ] Documentation updated
[ ] No obvious regression
[ ] Git checkpoint created

---

# 68. FINAL QUALITY STANDARD

The final system must behave like a real ISP business management platform.

It must be:

- Maintainable
- Secure
- Auditable
- Mobile-friendly
- Offline-capable
- Financially consistent
- Shared-hosting compatible
- Website-ready
- Customer-portal-ready
- API-ready
- Future-extensible

Do not optimize for "code generated quickly".

Optimize for:

Correctness
Data integrity
Security
Maintainability
Real-world usability

---

# 69. FIRST ACTION

Start with MILESTONE 0 only.

First inspect the existing project completely.

Do not implement the full application yet.

After inspection, create/update the architecture documentation and provide the Milestone 0 checkpoint report.

Then STOP and wait for my next instruction.