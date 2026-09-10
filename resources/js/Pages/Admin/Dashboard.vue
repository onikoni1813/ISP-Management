<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <Head title="Admin Dashboard - Pirgacha Internet" />

    <AdminLayout>
        <!-- Welcome Hero -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-white tracking-tight">
                    Operations Center
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Welcome back, {{ user?.name }}. System is running smoothly.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('staff.dashboard')"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-200 border border-slate-700 shadow-sm transition"
                >
                    <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Staff Field View
                </Link>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Customer
                </button>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-5 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Customers</span>
                    <span class="rounded-lg bg-indigo-500/10 p-2 text-indigo-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-white tracking-tight">1,248</span>
                    <span class="text-xs font-medium text-emerald-400">+12% this mo</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">1,180 Active | 68 Expired</p>
            </div>

            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-5 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Today's Collection</span>
                    <span class="rounded-lg bg-emerald-500/10 p-2 text-emerald-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-white tracking-tight">৳28,500</span>
                    <span class="text-xs font-medium text-emerald-400">42 Transactions</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Target: ৳35,000 / day</p>
            </div>

            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-5 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Due Amount</span>
                    <span class="rounded-lg bg-rose-500/10 p-2 text-rose-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-white tracking-tight">৳64,200</span>
                    <span class="text-xs font-medium text-rose-400">114 Customers</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Pending recovery</p>
            </div>

            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-5 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Open Tickets</span>
                    <span class="rounded-lg bg-amber-500/10 p-2 text-amber-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-white tracking-tight">5</span>
                    <span class="text-xs font-medium text-amber-400">2 Urgent</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Avg resolution: 45 min</p>
            </div>
        </div>

        <!-- System Architecture & Security Status Section -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6 backdrop-blur-sm">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                System Environment & Security Health
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                    <div class="text-xs text-slate-400">Role-Based Access Control</div>
                    <div class="text-sm font-bold text-emerald-400 mt-1 flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Multi-tier RBAC Active
                    </div>
                    <div class="text-xs text-slate-500 mt-2">Granular permission gates enforced server-side.</div>
                </div>

                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                    <div class="text-xs text-slate-400">Offline PWA Sync Engine</div>
                    <div class="text-sm font-bold text-emerald-400 mt-1 flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Service Worker Ready
                    </div>
                    <div class="text-xs text-slate-500 mt-2">IndexedDB cache initialized for field operations.</div>
                </div>

                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                    <div class="text-xs text-slate-400">Financial Ledger Engine</div>
                    <div class="text-sm font-bold text-emerald-400 mt-1 flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Append-only Architecture
                    </div>
                    <div class="text-xs text-slate-500 mt-2">Zero financial record deletion enforced by rules.</div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
