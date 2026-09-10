# PWA & Offline Architecture Specification

## 1. Core Principles
- **IndexedDB for Local Storage:** No large relational datasets stored in localStorage. Dexie.js or native IDB for schemas.
- **Client-Side UUIDs & Idempotency:** Every offline mutation (payment, renewal, ticket) receives a client-generated UUID `mutation_id`.
- **Sync Queue:** Mutations saved to an IndexedDB queue with statuses (`pending`, `syncing`, `completed`, `failed`).
- **Server Idempotency Middleware:** Laravel checks incoming mutation UUIDs against a ledger before processing to prevent double charging or redundant records.

## 2. PWA Manifest & Service Worker
- Manifest defines standalone display, theme color, icons, and start URL `/staff`.
- Cache-First for static assets (Vite bundles, CSS, icons, fonts).
- Network-First with Offline Fallback for dynamic pages and API calls.
- Clear Online/Offline visual indicator badge across Staff UI.
