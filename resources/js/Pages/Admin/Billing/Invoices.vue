<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    invoices: Object,
    metrics: {
        type: Object,
        default: () => ({
            total_invoices: 0,
            paid_invoices: 0,
            partial_invoices: 0,
            unpaid_invoices: 0,
            total_billed: 0,
            total_collected: 0,
            total_due: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// Filters state
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

const applyFilters = () => {
    router.get(route('admin.billing.invoices'), {
        search: search.value || undefined,
        status: status.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const filterStatus = (st) => {
    status.value = st;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

watch([status, dateFrom, dateTo], () => {
    applyFilters();
});

// Modal state for Invoice Details & Itemization
const selectedInvoice = ref(null);
const isDetailModalOpen = ref(false);

const openDetailModal = (inv) => {
    selectedInvoice.value = inv;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedInvoice.value = null;
};
</script>

<template>
    <Head title="Invoice Ledger - Pirgacha Internet" />

    <AdminLayout>
        <div class="space-y-5">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <Link :href="route('admin.dashboard')" class="hover:text-white transition">Admin</Link>
                        <span>/</span>
                        <span class="text-slate-200">Billing & Accounting</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight mt-1">Invoice Ledger & Billing Records</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Audit customer bills, collections, and dues.</p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.billing.payments')"
                        class="flex-1 sm:flex-none text-center rounded-xl bg-[#091A2E] hover:bg-[#0E2746] border border-brand-navy hover:border-brand-sky/40 px-3.5 py-2 text-xs font-bold text-brand-sky transition active:scale-95 shadow-md shadow-black/20"
                    >
                        💳 Payments
                    </Link>
                    <Link
                        :href="route('admin.billing.renewals')"
                        class="flex-1 sm:flex-none text-center rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber hover:opacity-95 px-3.5 py-2 text-xs font-black text-white shadow-md shadow-brand-orange/25 transition active:scale-95"
                    >
                        🔄 Renewals
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Stat Cards (Mobile optimized: responsive grid & compact typography) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
                <!-- Total Invoiced -->
                <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/90 p-3 sm:p-4 shadow-lg flex items-center justify-between">
                    <div class="min-w-0 pr-1">
                        <span class="text-[10px] sm:text-[11px] font-semibold text-slate-400 uppercase tracking-wider truncate block">Invoiced</span>
                        <span class="text-lg sm:text-2xl font-mono font-black text-white mt-0.5 block truncate">৳{{ metrics.total_billed?.toLocaleString() }}</span>
                        <span class="text-[9px] sm:text-[10px] text-slate-400 inline-block truncate">{{ metrics.total_invoices }} Invoices</span>
                    </div>
                    <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-xl bg-brand-sky/15 border border-brand-sky/30 flex items-center justify-center text-brand-sky text-sm sm:text-base shrink-0">
                        🧾
                    </div>
                </div>

                <!-- Collected Amount -->
                <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/90 p-3 sm:p-4 shadow-lg flex items-center justify-between">
                    <div class="min-w-0 pr-1">
                        <span class="text-[10px] sm:text-[11px] font-semibold text-slate-400 uppercase tracking-wider truncate block">Collected</span>
                        <span class="text-lg sm:text-2xl font-mono font-black text-emerald-400 mt-0.5 block truncate">৳{{ metrics.total_collected?.toLocaleString() }}</span>
                        <span class="text-[9px] sm:text-[10px] text-emerald-400 font-bold inline-block truncate">{{ metrics.paid_invoices }} Paid</span>
                    </div>
                    <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-sm sm:text-base shrink-0">
                        ✅
                    </div>
                </div>

                <!-- Pending Due -->
                <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/90 p-3 sm:p-4 shadow-lg flex items-center justify-between">
                    <div class="min-w-0 pr-1">
                        <span class="text-[10px] sm:text-[11px] font-semibold text-slate-400 uppercase tracking-wider truncate block">Pending Due</span>
                        <span class="text-lg sm:text-2xl font-mono font-black text-rose-400 mt-0.5 block truncate">৳{{ metrics.total_due?.toLocaleString() }}</span>
                        <span class="text-[9px] sm:text-[10px] text-rose-400 font-bold inline-block truncate">{{ metrics.unpaid_invoices }} Unpaid</span>
                    </div>
                    <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-400 text-sm sm:text-base shrink-0">
                        ⚠️
                    </div>
                </div>

                <!-- Recovery Rate -->
                <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/90 p-3 sm:p-4 shadow-lg flex items-center justify-between">
                    <div class="min-w-0 pr-1">
                        <span class="text-[10px] sm:text-[11px] font-semibold text-slate-400 uppercase tracking-wider truncate block">Recovery</span>
                        <span class="text-lg sm:text-2xl font-mono font-black text-brand-orange mt-0.5 block truncate">
                            {{ metrics.total_billed > 0 ? Math.round((metrics.total_collected / metrics.total_billed) * 100) : 0 }}%
                        </span>
                        <span class="text-[9px] sm:text-[10px] text-slate-400 inline-block truncate">Collection Rate</span>
                    </div>
                    <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-xl bg-brand-orange/15 border border-brand-orange/30 flex items-center justify-center text-brand-orange text-sm sm:text-base shrink-0">
                        📊
                    </div>
                </div>
            </div>

            <!-- Search, Date Range & Status Filters Bar (Mobile 100% responsive, no overflow) -->
            <div class="rounded-2xl border border-brand-navy bg-[#081729] p-3 sm:p-4 space-y-3 shadow-xl">
                <!-- Row 1: Search Input -->
                <div class="relative w-full">
                    <span class="absolute left-3.5 top-2.5 text-slate-400 text-xs">🔍</span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search Invoice #, Customer name or Code..."
                        class="w-full pl-9 pr-8 py-2 rounded-xl bg-[#061220] border border-brand-navy text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none transition"
                    />
                    <button
                        v-if="search"
                        @click="search = ''"
                        type="button"
                        class="absolute right-2.5 top-2 text-slate-400 hover:text-white text-xs cursor-pointer"
                    >✕</button>
                </div>

                <!-- Row 2: Date Filters & Reset Button (wraps cleanly on mobile) -->
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2">
                    <div class="flex items-center gap-1.5 bg-[#061220] border border-brand-navy rounded-xl px-2.5 py-1.5 text-xs">
                        <span class="text-slate-400 text-[10px] sm:text-[11px] shrink-0">From:</span>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="bg-transparent text-white text-xs focus:outline-none w-full min-w-0 cursor-pointer"
                        />
                    </div>
                    <div class="flex items-center gap-1.5 bg-[#061220] border border-brand-navy rounded-xl px-2.5 py-1.5 text-xs">
                        <span class="text-slate-400 text-[10px] sm:text-[11px] shrink-0">To:</span>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="bg-transparent text-white text-xs focus:outline-none w-full min-w-0 cursor-pointer"
                        />
                    </div>
                    <button
                        v-if="search || status || dateFrom || dateTo"
                        @click="resetFilters"
                        type="button"
                        class="col-span-2 sm:col-span-1 rounded-xl border border-brand-navy bg-[#061220] hover:bg-brand-navy text-xs font-bold text-slate-300 hover:text-white px-3 py-1.5 transition flex items-center justify-center gap-1 cursor-pointer"
                    >
                        <span>✕</span>
                        <span>Reset Filters</span>
                    </button>
                </div>

                <!-- Row 3: Status Pills Bar -->
                <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-brand-navy/60">
                    <span class="text-xs text-slate-400 font-semibold mr-1 hidden sm:inline">Status:</span>
                    <button
                        @click="filterStatus('')"
                        type="button"
                        :class="[
                            !status
                                ? 'bg-gradient-to-r from-brand-sky to-brand-blue text-white font-black shadow-md shadow-brand-sky/25 border-brand-sky/40'
                                : 'bg-[#061220] text-slate-300 hover:bg-brand-navy/60 border-brand-navy/80',
                            'px-2.5 py-1 rounded-xl text-[11px] sm:text-xs font-semibold border transition cursor-pointer active:scale-95'
                        ]"
                    >
                        All ({{ metrics.total_invoices }})
                    </button>
                    <button
                        @click="filterStatus('paid')"
                        type="button"
                        :class="[
                            status === 'paid'
                                ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 font-bold shadow-md'
                                : 'bg-[#061220] text-slate-400 hover:text-emerald-400 border-brand-navy/80',
                            'px-2.5 py-1 rounded-xl text-[11px] sm:text-xs font-semibold border transition cursor-pointer active:scale-95 flex items-center gap-1.5'
                        ]"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Paid ({{ metrics.paid_invoices }})
                    </button>
                    <button
                        @click="filterStatus('unpaid')"
                        type="button"
                        :class="[
                            status === 'unpaid'
                                ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 font-bold shadow-md'
                                : 'bg-[#061220] text-slate-400 hover:text-rose-400 border-brand-navy/80',
                            'px-2.5 py-1 rounded-xl text-[11px] sm:text-xs font-semibold border transition cursor-pointer active:scale-95 flex items-center gap-1.5'
                        ]"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                        Unpaid ({{ metrics.unpaid_invoices }})
                    </button>
                    <button
                        @click="filterStatus('partial')"
                        type="button"
                        :class="[
                            status === 'partial'
                                ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-bold shadow-md'
                                : 'bg-[#061220] text-slate-400 hover:text-amber-400 border-brand-navy/80',
                            'px-2.5 py-1 rounded-xl text-[11px] sm:text-xs font-semibold border transition cursor-pointer active:scale-95 flex items-center gap-1.5'
                        ]"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        Partial ({{ metrics.partial_invoices }})
                    </button>
                </div>
            </div>

            <!-- MOBILE CARDS VIEW (Visible strictly on screens smaller than md: hidden md:block below) -->
            <div class="block md:hidden space-y-3">
                <div
                    v-for="inv in invoices.data"
                    :key="'mob-' + inv.id"
                    class="rounded-2xl border border-brand-navy/80 bg-[#091A2E]/95 p-4 space-y-3 shadow-lg"
                >
                    <!-- Card Top Header: Invoice No & Status Badge -->
                    <div class="flex items-center justify-between border-b border-brand-navy/60 pb-2.5">
                        <button
                            @click="openDetailModal(inv)"
                            type="button"
                            class="font-mono font-bold text-brand-sky text-sm hover:underline flex items-center gap-1"
                        >
                            <span>{{ inv.invoice_number }}</span>
                            <span class="text-[10px] text-slate-400">👁️</span>
                        </button>
                        <span
                            :class="[
                                inv.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : '',
                                inv.status === 'partial' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : '',
                                inv.status === 'unpaid' ? 'bg-rose-500/10 text-rose-400 border-rose-500/30' : '',
                                'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider'
                            ]"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="inv.status === 'paid' ? 'bg-emerald-400' : (inv.status === 'partial' ? 'bg-amber-400' : 'bg-rose-400')"></span>
                            {{ inv.status }}
                        </span>
                    </div>

                    <!-- Customer Info -->
                    <div class="flex items-start justify-between">
                        <div>
                            <Link :href="route('admin.customers.show', inv.customer_id)" class="font-bold text-white text-sm hover:text-brand-sky">
                                {{ inv.customer?.name || 'Customer' }}
                            </Link>
                            <div class="text-[11px] text-slate-400 font-mono">{{ inv.customer?.customer_code }}</div>
                        </div>
                        <div class="text-right text-xs">
                            <span class="text-slate-500 text-[10px] uppercase block">Due Date</span>
                            <span :class="inv.status === 'unpaid' ? 'text-rose-400 font-bold font-mono' : 'text-slate-300 font-mono'">
                                {{ formatDate(inv.due_date) }}
                            </span>
                        </div>
                    </div>

                    <!-- Financial Summary Box -->
                    <div class="grid grid-cols-3 gap-2 p-2.5 rounded-xl bg-[#061220] border border-brand-navy/60 text-center">
                        <div>
                            <span class="text-[9px] uppercase tracking-wider text-slate-400 block">Total</span>
                            <span class="font-mono font-bold text-white text-xs sm:text-sm">৳{{ Number(inv.total) }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] uppercase tracking-wider text-slate-400 block">Paid</span>
                            <span class="font-mono font-semibold text-emerald-400 text-xs sm:text-sm">৳{{ Number(inv.paid_amount) }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] uppercase tracking-wider text-slate-400 block">Due</span>
                            <span class="font-mono font-bold text-xs sm:text-sm" :class="Number(inv.due_amount) > 0 ? 'text-rose-400' : 'text-slate-400'">
                                ৳{{ Number(inv.due_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Billing Period & Action Button -->
                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                        <span class="truncate max-w-[150px]">Period: {{ formatDate(inv.period_start) }} - {{ formatDate(inv.period_end) }}</span>
                        <div class="flex items-center gap-1.5">
                            <a
                                :href="route('admin.billing.invoices.show', inv.id)"
                                target="_blank"
                                class="px-2.5 py-1.5 rounded-lg border border-indigo-500/30 bg-indigo-500/10 text-indigo-300 font-bold hover:bg-indigo-500/20 transition flex items-center gap-1"
                                title="Print or Save as PDF"
                            >
                                <span>🖨️</span>
                                <span>Print</span>
                            </a>
                            <button
                                @click="openDetailModal(inv)"
                                type="button"
                                class="px-2.5 py-1.5 rounded-lg border border-brand-navy bg-[#0B1E36] text-brand-sky font-bold hover:bg-[#102B4D] hover:text-white transition active:scale-95"
                            >
                                Details →
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="invoices.data.length === 0" class="p-8 text-center rounded-2xl border border-dashed border-brand-navy bg-[#091A2E]/50 text-slate-400 text-xs">
                    No invoices found.
                </div>
            </div>

            <!-- DESKTOP / TABLET DATA TABLE (Hidden on mobile phones: hidden md:block) -->
            <div class="hidden md:block overflow-hidden rounded-2xl border border-brand-navy/60 bg-[#091A2E]/90 backdrop-blur-sm shadow-xl shadow-black/20">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="border-b border-brand-navy bg-[#071322] uppercase font-semibold text-slate-400 whitespace-nowrap">
                            <tr>
                                <th class="px-5 py-4">Invoice #</th>
                                <th class="px-5 py-4">Customer</th>
                                <th class="px-5 py-4">Billing Period</th>
                                <th class="px-5 py-4">Due Date</th>
                                <th class="px-5 py-4">Total Amount</th>
                                <th class="px-5 py-4">Paid</th>
                                <th class="px-5 py-4">Due</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-navy/60 whitespace-nowrap">
                            <tr
                                v-for="inv in invoices.data"
                                :key="inv.id"
                                class="hover:bg-brand-navy/30 transition group"
                            >
                                <td class="px-5 py-4">
                                    <button
                                        @click="openDetailModal(inv)"
                                        type="button"
                                        class="font-mono font-bold text-brand-sky hover:text-brand-cyan hover:underline flex items-center gap-1.5 cursor-pointer"
                                        title="Click to view full invoice breakdown"
                                    >
                                        <span>{{ inv.invoice_number }}</span>
                                        <svg class="h-3.5 w-3.5 opacity-60 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="px-5 py-4">
                                    <Link :href="route('admin.customers.show', inv.customer_id)" class="font-bold text-white hover:text-brand-sky flex items-center gap-1.5">
                                        {{ inv.customer?.name || 'Customer' }}
                                    </Link>
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ inv.customer?.customer_code }}</div>
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-400">
                                    <div>{{ formatDate(inv.period_start) }} <span class="text-slate-600">→</span> {{ formatDate(inv.period_end) }}</div>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs">
                                    <span :class="inv.status === 'unpaid' ? 'text-rose-300 font-semibold' : 'text-slate-300'">
                                        {{ formatDate(inv.due_date) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono font-bold text-white">
                                    ৳{{ Number(inv.total) }}
                                    <span v-if="Number(inv.discount) > 0" class="text-[10px] text-brand-amber font-normal block">
                                        (Disc: ৳{{ Number(inv.discount) }})
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono font-semibold text-emerald-400">
                                    ৳{{ Number(inv.paid_amount) }}
                                </td>
                                <td class="px-5 py-4 font-mono font-semibold" :class="Number(inv.due_amount) > 0 ? 'text-rose-400 font-bold' : 'text-slate-400'">
                                    ৳{{ Number(inv.due_amount) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            inv.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : '',
                                            inv.status === 'partial' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : '',
                                            inv.status === 'unpaid' ? 'bg-rose-500/10 text-rose-400 border-rose-500/30' : '',
                                            'inline-flex items-center gap-1 rounded-md border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider'
                                        ]"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="inv.status === 'paid' ? 'bg-emerald-400' : (inv.status === 'partial' ? 'bg-amber-400' : 'bg-rose-400')"></span>
                                        {{ inv.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            :href="route('admin.billing.invoices.show', inv.id)"
                                            target="_blank"
                                            class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-3 py-1.5 text-xs font-semibold text-indigo-300 hover:bg-indigo-500/20 hover:text-white transition flex items-center gap-1.5 cursor-pointer"
                                            title="Print or Save as PDF"
                                        >
                                            <span>🖨️</span>
                                            <span>Print</span>
                                        </a>
                                        <button
                                            @click="openDetailModal(inv)"
                                            type="button"
                                            class="rounded-xl border border-brand-navy bg-[#0B1E36] px-3 py-1.5 text-xs font-semibold text-brand-sky hover:bg-[#102B4D] hover:text-white transition flex items-center gap-1 cursor-pointer"
                                            title="View Details"
                                        >
                                            <span>👁️</span>
                                            <span>Breakdown</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="invoices.data.length === 0">
                                <td colspan="9" class="px-5 py-12 text-center text-slate-500 space-y-2">
                                    <div class="text-3xl">🧾</div>
                                    <div class="font-bold text-white text-sm">No invoice records found</div>
                                    <p class="text-xs text-slate-400">Try changing your filters or search keywords.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Bar with Preserved Filters -->
            <div v-if="invoices.links && invoices.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 border border-brand-navy/60 rounded-2xl px-4 py-3 text-xs text-slate-400 bg-[#091A2E]/80">
                <div class="text-center sm:text-left">
                    Showing <strong class="text-white font-mono">{{ invoices.from || 0 }}</strong> to <strong class="text-white font-mono">{{ invoices.to || 0 }}</strong> of <strong class="text-white font-mono">{{ invoices.total || 0 }}</strong> invoices
                </div>
                <div class="flex items-center gap-1 flex-wrap justify-center">
                    <Link
                        v-for="(link, i) in invoices.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            link.active ? 'bg-brand-blue text-white font-bold' : 'text-slate-400 hover:bg-slate-800 hover:text-white',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                            'rounded-lg px-2.5 py-1 transition text-xs'
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- INVOICE BREAKDOWN & AUDIT MODAL -->
        <div v-if="isDetailModalOpen && selectedInvoice" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="closeDetailModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-3xl border border-brand-navy bg-[#091A2E] p-4 sm:p-6 shadow-2xl space-y-5">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-brand-navy pb-3">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-black text-white">Invoice Details</h2>
                            <span class="rounded-lg bg-brand-sky/15 border border-brand-sky/30 px-2 py-0.5 font-mono text-xs font-bold text-brand-sky">
                                {{ selectedInvoice.invoice_number }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">Generated for customer billing and ledger reconciliation.</p>
                    </div>
                    <button @click="closeDetailModal" type="button" class="rounded-xl p-1.5 text-slate-400 hover:text-white hover:bg-brand-navy transition cursor-pointer">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Customer & Period Info Grid -->
                <div class="grid grid-cols-2 gap-3 rounded-2xl bg-[#061220] p-3.5 border border-brand-navy text-xs">
                    <div>
                        <span class="text-slate-500 uppercase block text-[10px] font-semibold">Customer</span>
                        <Link :href="route('admin.customers.show', selectedInvoice.customer_id)" class="font-bold text-white hover:text-brand-sky text-sm mt-0.5 block truncate">
                            {{ selectedInvoice.customer?.name }}
                        </Link>
                        <span class="font-mono text-brand-orange text-[11px]">{{ selectedInvoice.customer?.customer_code }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500 uppercase block text-[10px] font-semibold">Status</span>
                        <span
                            :class="[
                                selectedInvoice.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : '',
                                selectedInvoice.status === 'partial' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : '',
                                selectedInvoice.status === 'unpaid' ? 'bg-rose-500/10 text-rose-400 border-rose-500/30' : '',
                                'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase mt-1'
                            ]"
                        >
                            {{ selectedInvoice.status }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500 uppercase block text-[10px] font-semibold">Billing Period</span>
                        <span class="text-slate-200 font-mono mt-0.5 block text-[11px]">
                            {{ formatDate(selectedInvoice.period_start) }} - {{ formatDate(selectedInvoice.period_end) }}
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500 uppercase block text-[10px] font-semibold">Due Date</span>
                        <span class="text-brand-orange font-mono font-bold mt-0.5 block text-[11px]">
                            {{ formatDate(selectedInvoice.due_date) }}
                        </span>
                    </div>
                </div>

                <!-- Itemized Breakdown Table -->
                <div class="space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white flex items-center gap-1.5">
                        <span>📦</span>
                        <span>Itemized Charges</span>
                    </h3>
                    <div class="overflow-hidden rounded-2xl border border-brand-navy bg-[#061220]">
                        <table class="w-full text-left text-xs text-slate-300">
                            <thead class="border-b border-brand-navy bg-[#071322] text-[10px] uppercase font-semibold text-slate-400">
                                <tr>
                                    <th class="px-3 py-2">Item Description</th>
                                    <th class="px-2 py-2 text-center">Qty</th>
                                    <th class="px-3 py-2 text-right">Unit Price</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-navy/60">
                                <tr v-for="item in selectedInvoice.items" :key="item.id">
                                    <td class="px-3 py-2 font-medium text-white">
                                        {{ item.description }}
                                        <span v-if="item.item_type" class="text-[10px] text-brand-sky font-mono ml-1">({{ item.item_type }})</span>
                                    </td>
                                    <td class="px-2 py-2 text-center font-mono">{{ item.quantity || 1 }}</td>
                                    <td class="px-3 py-2 text-right font-mono">৳{{ Number(item.unit_price) }}</td>
                                    <td class="px-3 py-2 text-right font-mono font-bold text-white">৳{{ Number(item.total) }}</td>
                                </tr>
                                <tr v-if="!selectedInvoice.items || selectedInvoice.items.length === 0">
                                    <td colspan="4" class="px-3 py-3 text-center text-slate-500 text-xs">
                                        Single package billing charge.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Financial Calculation Summary -->
                <div class="rounded-2xl border border-brand-navy bg-[#061220] p-3.5 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-slate-400">
                        <span>Subtotal:</span>
                        <span class="font-mono text-slate-200">৳{{ Number(selectedInvoice.subtotal || selectedInvoice.total) }}</span>
                    </div>
                    <div v-if="Number(selectedInvoice.discount) > 0" class="flex items-center justify-between text-brand-amber">
                        <span>Special Discount:</span>
                        <span class="font-mono font-bold">- ৳{{ Number(selectedInvoice.discount) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm font-bold text-white pt-1.5 border-t border-brand-navy/80">
                        <span>Net Invoiced Amount:</span>
                        <span class="font-mono text-brand-sky">৳{{ Number(selectedInvoice.total) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-emerald-400">
                        <span>Paid To Date:</span>
                        <span class="font-mono font-bold">৳{{ Number(selectedInvoice.paid_amount) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm font-black text-rose-400 pt-1 border-t border-brand-navy/80">
                        <span>Remaining Due Balance:</span>
                        <span class="font-mono">৳{{ Number(selectedInvoice.due_amount) }}</span>
                    </div>
                </div>

                <div v-if="selectedInvoice.notes" class="text-xs text-slate-400 italic">
                    <strong class="text-slate-300 not-italic">Notes:</strong> {{ selectedInvoice.notes }}
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-brand-navy">
                    <button
                        @click="closeDetailModal"
                        type="button"
                        class="rounded-xl border border-brand-navy bg-transparent px-3.5 py-2 text-xs font-semibold text-slate-400 hover:text-white transition cursor-pointer"
                    >
                        Close
                    </button>
                    <div class="flex items-center gap-2">
                        <a
                            :href="route('admin.billing.invoices.show', selectedInvoice.id)"
                            target="_blank"
                            class="rounded-xl bg-brand-blue hover:bg-brand-sky/90 px-3.5 py-2 text-xs font-bold text-white transition flex items-center gap-1.5 shadow-md shadow-brand-blue/20"
                        >
                            <span>🖨️</span>
                            <span>Print</span>
                        </a>
                        <Link
                            :href="route('admin.customers.show', selectedInvoice.customer_id)"
                            class="rounded-xl border border-brand-navy bg-[#0B1E36] hover:bg-[#0E2746] px-3.5 py-2 text-xs font-bold text-brand-sky transition"
                        >
                            Profile →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
