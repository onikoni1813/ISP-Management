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
