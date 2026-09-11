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

## 7. Offline PWA & Sync Architecture (Milestone 13)
- **Service Worker (`public/sw.js`):**
  - Cache-First strategy for static assets (`/build/`, icons, web fonts, CSS, JS).
  - Network-First with Cache Fallback for Inertia HTML navigation views.
- **Client IndexedDB Store (`resources/js/Services/offlineStorage.js`):**
  - Object stores: `customers`, `packages`, `areas`, `complaints`, `mutations`, `meta`.
  - Indexes on customer code, name, phone number, and PPPoE username for instant offline field searches.
- **Offline Mutation Queue & Idempotency (`resources/js/Services/syncService.js`):**
  - Captures field actions (payments, renewals, tickets, status changes) offline with client UUIDs.
  - Server endpoints (`/staff/api/sync/bootstrap`, `/staff/api/sync/mutations`) process batch queues with atomic idempotency checks.
- **Conflict Handling (Section 39):**
  - Server checks status conflicts (e.g. ticket already resolved by NOC) and reports conflict status back to client queue instead of silently overwriting.
- **UI Indicators:**
  - Real-time `ONLINE` / `OFFLINE` status badge in `StaffLayout.vue`.
  - Queued mutations counter badge and manual sync trigger button.
  - Offline cached search results indicator on `Staff/Dashboard.vue`.
  - Offline queued notification banners on `Staff/CustomerDetails.vue`.

## 8. Customer Self-Service Portal (`/account`) (Milestone 14)
- **Authentication & Gateway:**
  - Standard user login routes customers automatically to `/account` dashboard via `/dashboard` role inspection.
  - Automatic association of authenticated user to `Customer` record via `user_id`, or verified primary contact phone/email.
- **Subscriber Dashboard (`Account/Dashboard.vue`):**
  - Live subscription status, optical bandwidth, expiry countdown, and outstanding balance summary.
  - PPPoE technical overview and quick renewal / support ticket actions.
- **Self-Service Features:**
  - **Invoices (`Account/Invoices.vue`):** Itemized breakdown of monthly package bills, previous balances, due dates, and payment status.
  - **Payment Receipts (`Account/Payments.vue`):** Historical ledger of money receipts and payment transactions.
  - **Instant Renewal (`Account/Renewal.vue`):** Self-service connection extension (30/60/90 days) with mobile wallet support (bKash, Nagad) creating atomic renewals, invoices, and payments.
  - **Support Tickets (`Account/Complaints.vue`):** Ticket submission with severity levels and interactive customer/NOC conversation threads.
  - **Subscriber Profile (`Account/Profile.vue`):** Customer code, registered address, and billing contact details.

## 9. Public Marketing Website (`/`) (Milestone 15)
- **Shared Architecture (Rule 1 & Section 46):**
  - Serves public visitors from the exact same Laravel and Vue 3 / Inertia foundation without duplicating codebase or database instances.
  - Live packages and pricing queried directly from `packages` and `package_prices` tables (single source of truth).
  - Coverage zones dynamically queried from hierarchical `areas` table.
- **Pages Implemented:**
  - `/` (Home) — High-converting hero, key feature highlights, dynamic optical package matrix, and interactive online connection application form.
  - `/packages` — Dedicated fiber package tiers with bandwidth speeds, BDIX caching specs, and monthly rates.
  - `/coverage` — Active coverage zones and connected sub-zones across Pirgacha.
  - `/about` — ISP company profile, mission statement, optical backbone highlights, and local NOC details.
  - `/faq` — Answers to common subscriber inquiries (setup time, equipment, billing methods, red LOS lights).
  - `/notices` — Official ISP bulletin board, maintenance notices, and expansion announcements.
  - `/contact` — Direct NOC hotline, email, operating hours, and inquiry submission.
- **Online Connection Application (`POST /apply`):**
  - Captures prospective subscriber details (name, phone, area, package, installation address) and records initial customer entity via `CustomerService`.

## 10. Website CMS & Dynamic Publishing Engine (Milestone 16)
- **CMS Database Foundation:**
  - `cms_pages`: Dynamic content pages with customizable URL slugs (`/p/{slug}`), rich markdown/HTML body, display ordering, and dedicated SEO fields (`seo_title`, `seo_description`).
  - `cms_banners`: High-impact promotional hero banners, badge texts, gradient styling, call-to-action buttons (`button_text`, `button_url`), and display ordering.
  - `cms_faqs`: Categorized questions and answers (`General`, `Setup & Connection`, `Billing & Payments`, `Troubleshooting`, `Technical`) with display sequencing and publishing status.
  - `cms_notices`: Official ISP notices and NOC announcements categorized by type (`General`, `Maintenance`, `Expansion`, `System`, `Billing`) with scheduled/effective dates.
- **Admin CMS Management (`/admin/cms`):**
  - Intuitive overview dashboard displaying managed content counts and real-time live site link.
  - Granular CRUD interfaces for Custom Pages, Banners, FAQs, and Notices.
  - Instant publish/draft toggle actions (`toggle`) and delete safeguards.
  - Automatic audit trail integration (`AuditLog::log`) for creation, editing, status toggling, and deletion of all CMS entities.
- **Security & Authorization:**
  - Restricted strictly to users holding the `website.manage` permission under the Admin domain.
  - Dynamic page resolution (`/p/{slug}`) protects draft/unpublished pages with 404 responses until explicitly published by an administrator.

