# Architecture Overview: ISP Management Platform

## 1. Stack & Runtime
- **Backend:** Laravel 11.x (PHP 8.2+)
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS + Vite
- **Database:** MySQL 8.0 / MariaDB
- **Target Platform:** Shared Hosting (cPanel compatible) / Local XAMPP
- **Mobile/Offline:** PWA with Service Worker + IndexedDB sync queue

## 2. Authentication & Authorization
- Session-based authentication with Laravel Breeze (Inertia/Vue 3 stack)
- Scalable Role-Based Access Control (RBAC) with granular permissions:
  - Tables: `roles`, `permissions`, `role_user`, `permission_role`, `permission_user` (direct overrides)
  - Laravel Gates & Policies inspecting user permissions
  - Server-side authorization checks on all routes and actions

## 3. Application Areas & Routing Boundaries
- `/admin` — Full ISP operational management, analytics, financial ledger, package management, staff control, audit trails.
- `/staff` — Streamlined, mobile-first PWA interface optimized for field agents, billing collections, and complaint resolutions.
- `/account` — Customer self-service portal (Billing, receipts, status, tickets).
- `/` — High-converting public marketing website & online application.
- `/api/v1` — Secure authenticated REST endpoints for offline sync and client operations.

## 4. Financial & Reporting Architecture
- Strict adherence to **Rule 27 (Profit/Loss Distinction)**:
  - `Net Operating Profit = Revenue - Operating Expenses - Staff Salaries`
  - Liquid Cash & Bank wallet balances are kept strictly distinct from period profits.
- Append-only `account_transactions` double-entry compliant ledger for traceable money flows.
## 5. SMS Gateway & Template Engine (Milestone 11)
- **Gateway Abstraction Layer (`SmsDriverInterface`):**
  - Modular SMS driver design supporting multiple providers (`log`, `greenweb`, `mimsms`, `bulksmsbd`, `reve`).
  - Active gateway dynamic resolution via database configuration (`sms_gateways`).
- **Template System (`sms_templates`):**
  - Variable interpolation (`{name}`, `{customer_code}`, `{package}`, `{amount}`, `{due}`, `{expiry_date}`, `{complaint_number}`).
  - Dynamic toggling (`is_auto_enabled`) for system-wide automated event notifications.
- **Audit & Delivery Logging (`sms_logs`):**
  - Recipient, raw content, provider tracking, status (`sent`, `failed`, `queued`), error messages, and retry dispatching.

## 6. Automated SMS Triggers & Scheduler (Milestone 12)
- **Background Queue Processing (`SendCustomerSmsJob`):**
  - Event-driven notifications dispatched via Laravel Queue (`ShouldQueue`) to prevent blocking user-facing HTTP requests.
- **Real-Time Automated Event Hooks:**
  - **Billing Payments:** Dispatched automatically upon payment collection (`BillingService::collectPayment`).
  - **Package Renewals:** Dispatched automatically upon package renewal (`RenewalService::processRenewal`).
  - **Complaint Logging & Resolution:** Dispatched on complaint creation and customer resolution (`ComplaintService`).
- **Automated Expiry Scheduler (`isp:send-expiry-sms`):**
  - Scheduled daily at 09:00 AM in `routes/console.php`.
  - Sends warning reminders at 3 days before, 1 day before, and on the expiry day.
  - Sends expired notices for accounts past due and shifts status if unaddressed.
- **Strict Duplicate Prevention (Rule 30):**
  - Template dispatching enforces entity-level checks (`template_id` + `entity_type` + `entity_id` + status) and daily date fences to guarantee customers never receive duplicate automated SMS for the same billing, renewal, or expiry event.
