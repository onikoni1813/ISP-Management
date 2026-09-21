<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    payments: Object,
    periods: Array,
    staffUsers: Array,
    accounts: Array,
    metrics: Object,
    filters: Object,
});

const showPayoutModal = ref(false);
const showPeriodModal = ref(false);

const search = ref(props.filters?.search || '');
const filterPeriod = ref(props.filters?.period_id || '');
const filterUser = ref(props.filters?.user_id || '');

const periodForm = useForm({
    period_name: '',
    start_date: '',
    end_date: '',
});

const payoutForm = useForm({
    user_id: props.staffUsers?.length ? props.staffUsers[0].id : '',
    salary_period_id: props.periods?.length ? props.periods[0].id : '',
    account_id: props.accounts?.length ? props.accounts[0].id : '',
    basic_salary: '',
    bonus: 0,
    commission: 0,
    advance_deduction: 0,
    other_deductions: 0,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
});

const netPayable = computed(() => {
    const basic = Number(payoutForm.basic_salary) || 0;
    const bonus = Number(payoutForm.bonus) || 0;
    const comm = Number(payoutForm.commission) || 0;
    const advance = Number(payoutForm.advance_deduction) || 0;
    const other = Number(payoutForm.other_deductions) || 0;
    return Math.max(0, basic + bonus + comm - advance - other);
});

const applyFilters = () => {
    router.get(route('admin.accounting.payroll'), {
        search: search.value || undefined,
        period_id: filterPeriod.value || undefined,
        user_id: filterUser.value || undefined,
    }, { preserveState: true, replace: true });
};

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 350);
});

const resetFilters = () => {
    search.value = '';
    filterPeriod.value = '';
    filterUser.value = '';
    applyFilters();
};

const submitPeriod = () => {
    periodForm.post(route('admin.accounting.payroll.period.store'), {
        onSuccess: () => {
            showPeriodModal.value = false;
            periodForm.reset();
        }
    });
};

const submitPayout = () => {
    payoutForm.post(route('admin.accounting.payroll.payout.store'), {
        onSuccess: () => {
            showPayoutModal.value = false;
            payoutForm.reset('basic_salary', 'bonus', 'commission', 'advance_deduction', 'other_deductions', 'notes');
        }
    });
};
</script>

<template>
    <Head title="Staff Payroll & Disbursements - Pirgacha Internet" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-brand-sky border border-brand-navy">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </span>
                        <span>Staff Payroll & Salary Ledger</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Manage monthly salary periods, bonuses, commissions, advance loan deductions, and payroll payouts.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        @click="showPeriodModal = true"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-[#0B1E36] hover:bg-[#102B4D] border border-brand-navy hover:border-brand-sky/40 text-slate-200 hover:text-white text-xs font-bold rounded-xl shadow-sm transition cursor-pointer active:scale-95"
                    >
                        <svg class="w-4 h-4 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Period
                    </button>
                    <button
                        @click="showPayoutModal = true"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-900/30 transition border border-emerald-500/30 cursor-pointer active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Disburse Salary
                    </button>
                </div>
            </div>

            <!-- Payroll Summary Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Total Salary Disbursed</div>
                    <div class="text-2xl font-black text-white mt-1 font-mono">
                        ৳{{ Number(metrics?.total_salary_disbursed ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-[11px] text-emerald-500/80 mt-1">{{ metrics?.total_disbursements_count ?? 0 }} total payouts</div>
                </div>

                <div class="rounded-2xl border border-brand-sky/30 bg-brand-sky/10 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-brand-sky">This Month Disbursed</div>
                    <div class="text-2xl font-black text-brand-sky mt-1 font-mono">
                        ৳{{ Number(metrics?.this_month_disbursed ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Current salary cycle</div>
                </div>

                <div class="rounded-2xl border border-indigo-500/30 bg-indigo-950/20 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-400">Payroll Staff</div>
                    <div class="text-2xl font-black text-indigo-400 mt-1">
                        {{ metrics?.total_staff_count ?? staffUsers?.length ?? 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Authorized personnel</div>
                </div>

                <div class="rounded-2xl border border-amber-500/30 bg-amber-950/20 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Salary Periods</div>
                    <div class="text-2xl font-black text-amber-400 mt-1">
                        {{ periods?.length ?? 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Configured pay cycles</div>
                </div>
            </div>

            <!-- Active Periods Quick List -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Salary Periods</h3>
                    <span class="text-xs text-brand-sky">{{ periods?.length || 0 }} periods configured</span>
                </div>
                <div class="flex flex-wrap gap-2.5">
                    <div v-if="!periods || periods.length === 0" class="text-xs text-slate-500 italic py-1">
                        No salary periods created yet. Click "+ New Period" to begin.
                    </div>
                    <button
                        v-for="p in periods"
                        :key="p.id"
                        type="button"
                        @click="filterPeriod = filterPeriod == p.id ? '' : p.id; applyFilters()"
                        class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl border text-xs font-medium transition cursor-pointer"
                        :class="filterPeriod == p.id ? 'bg-brand-sky/20 border-brand-sky text-white shadow-sm' : 'bg-[#071322] border-brand-navy text-slate-200 hover:border-brand-sky/40'"
                    >
                        <span class="w-2 h-2 rounded-full" :class="p.status === 'closed' ? 'bg-slate-500' : 'bg-emerald-400 shadow-sm shadow-emerald-400/50'"></span>
                        <span class="font-semibold">{{ p.name }}</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ p.start_date }} ~ {{ p.end_date }})</span>
                        <span v-if="p.status === 'closed'" class="text-[9px] uppercase font-bold text-slate-400 bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700">Closed</span>
                        <span v-else class="text-[9px] uppercase font-bold text-emerald-400 bg-emerald-950/60 px-1.5 py-0.5 rounded border border-emerald-800/40">Open</span>
                    </button>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="rounded-2xl border border-brand-navy/60 bg-[#091A2E]/80 p-3.5 backdrop-blur-sm shadow-xl flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                <div class="relative flex-1">
                    <input
                        type="text"
                        v-model="search"
                        placeholder="Search by payroll #, staff name, or email..."
                        class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="filterPeriod"
                        @change="applyFilters"
                        class="w-full sm:w-auto rounded-xl border border-brand-navy bg-[#071322] px-3 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none"
                    >
                        <option value="">All Periods</option>
                        <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>

                    <select
                        v-model="filterUser"
                        @change="applyFilters"
                        class="w-full sm:w-auto rounded-xl border border-brand-navy bg-[#071322] px-3 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none"
                    >
                        <option value="">All Staff</option>
                        <option v-for="u in staffUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>

                    <button
                        v-if="search || filterPeriod || filterUser"
                        @click="resetFilters"
                        class="rounded-xl border border-rose-500/30 bg-rose-950/20 px-3.5 py-2.5 text-xs font-bold text-rose-400 hover:bg-rose-950/40 transition shrink-0"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Payroll Payments Desktop Table (Hidden on mobile) -->
            <div class="hidden md:block bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-brand-navy shadow-xl overflow-hidden">
                <div class="p-4 border-b border-brand-navy flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white tracking-wide">Disbursement History</h3>
                    <span class="text-xs text-slate-400">{{ payments?.total || payments?.data?.length || 0 }} total transactions</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-navy/60 text-left text-xs text-slate-300">
                        <thead class="bg-[#071322]/80 text-slate-400 uppercase font-semibold whitespace-nowrap">
                            <tr>
                                <th class="px-4 py-3.5">Payroll #</th>
                                <th class="px-4 py-3.5">Disbursed Date</th>
                                <th class="px-4 py-3.5">Staff Member</th>
                                <th class="px-4 py-3.5">Period</th>
                                <th class="px-4 py-3.5">Basic</th>
                                <th class="px-4 py-3.5">Bonus / Comm</th>
                                <th class="px-4 py-3.5">Deductions</th>
                                <th class="px-4 py-3.5">Net Paid</th>
                                <th class="px-4 py-3.5">Paid From</th>
                                <th class="px-4 py-3.5">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-navy/40">
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="10" class="px-4 py-12 text-center text-slate-500">
                                    No salary disbursements found matching your filter criteria.
                                </td>
                            </tr>
                            <tr v-for="pay in payments.data" :key="pay.id" class="hover:bg-brand-navy/30 transition">
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="font-mono text-xs font-semibold text-brand-sky bg-brand-sky/10 px-2 py-0.5 rounded border border-brand-sky/20">
                                        {{ pay.payroll_number }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-300 font-mono text-xs">
                                    {{ formatDate(pay.payment_date) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-semibold text-white">{{ pay.user?.name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ pay.user?.email }}</div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-300 text-xs font-medium">
                                    {{ pay.salary_period?.name }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-300 font-mono text-xs">
                                    ৳ {{ Number(pay.basic_salary).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-emerald-400 font-mono text-xs font-medium">
                                    +৳ {{ (Number(pay.bonus) + Number(pay.commission)).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-rose-400 font-mono text-xs">
                                    -৳ {{ (Number(pay.advance_deduction) + Number(pay.other_deductions)).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-bold text-emerald-400 font-mono text-sm">
                                    ৳ {{ Number(pay.net_salary).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-300">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-brand-sky"></span>
                                        {{ pay.account?.name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-400 max-w-xs truncate">
                                    {{ pay.notes || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="px-4 py-3.5 bg-[#071322]/80 border-t border-brand-navy flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400">
                    <div>Showing {{ payments.from }} to {{ payments.to }} of {{ payments.total }} disbursements</div>
                    <div class="flex gap-1.5 flex-wrap justify-center">
                        <template v-for="(link, i) in payments.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-xl border transition font-medium',
                                    link.active ? 'bg-gradient-to-r from-brand-sky to-brand-blue text-white border-brand-sky shadow-sm' : 'bg-[#091A2E] text-slate-300 hover:bg-brand-navy/70 border-brand-navy hover:text-white'
                                ]"
                                v-html="link.label"
                            ></button>
                            <span v-else class="px-3 py-1.5 text-xs text-slate-500 border border-transparent" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Payroll Payments Mobile Cards View (Visible on mobile/tablet) -->
            <div class="block md:hidden space-y-3">
                <div
                    v-for="pay in payments.data"
                    :key="'mob-pay-' + pay.id"
                    class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/80 p-4 shadow-lg backdrop-blur-md space-y-3 text-xs"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-[11px] font-bold text-brand-sky">{{ pay.payroll_number }}</span>
                            <div class="font-bold text-white text-sm mt-0.5">{{ pay.user?.name }}</div>
                            <div class="text-[11px] text-slate-400">{{ pay.salary_period?.name }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-black text-emerald-400 text-base">
                                ৳ {{ Number(pay.net_salary).toLocaleString() }}
                            </div>
                            <span class="text-[10px] text-slate-400 block font-mono mt-0.5">
                                {{ formatDate(pay.payment_date) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 py-2 border-y border-brand-navy/60 text-center">
                        <div class="bg-[#071322]/60 p-2 rounded-xl">
                            <div class="text-[10px] uppercase font-bold text-slate-400">Basic</div>
                            <div class="font-mono text-white font-bold mt-0.5">৳{{ Number(pay.basic_salary).toLocaleString() }}</div>
                        </div>
                        <div class="bg-[#071322]/60 p-2 rounded-xl">
                            <div class="text-[10px] uppercase font-bold text-emerald-400">Additions</div>
                            <div class="font-mono text-emerald-400 font-bold mt-0.5">
                                +৳{{ (Number(pay.bonus) + Number(pay.commission)).toLocaleString() }}
                            </div>
                        </div>
                        <div class="bg-[#071322]/60 p-2 rounded-xl">
                            <div class="text-[10px] uppercase font-bold text-rose-400">Deductions</div>
                            <div class="font-mono text-rose-400 font-bold mt-0.5">
                                -৳{{ (Number(pay.advance_deduction) + Number(pay.other_deductions)).toLocaleString() }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>Paid From: <strong class="text-white">{{ pay.account?.name }}</strong></span>
                        <span v-if="pay.notes" class="truncate max-w-[150px]">{{ pay.notes }}</span>
                    </div>
                </div>

                <div v-if="!payments.data || payments.data.length === 0" class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/40 p-8 text-center text-slate-400 text-xs">
                    No salary disbursements found matching your filter criteria.
                </div>

                <!-- Mobile Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="p-3 border border-brand-navy/60 rounded-2xl bg-[#091A2E]/80 flex justify-center gap-1.5 flex-wrap">
                    <template v-for="(link, i) in payments.links" :key="'mob-pay-page-' + i">
                        <button
                            v-if="link.url"
                            @click="router.get(link.url, {}, { preserveState: true })"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-xl border transition font-semibold',
                                link.active 
                                    ? 'bg-gradient-to-r from-brand-sky to-brand-blue text-white border-brand-sky' 
                                    : 'bg-[#071322] text-slate-300 hover:text-white border-brand-navy'
                            ]"
                            v-html="link.label"
                        ></button>
                    </template>
                </div>
            </div>

            <!-- Create Salary Period Modal -->
            <div v-if="showPeriodModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-[#091A2E] rounded-2xl max-w-md w-full p-6 shadow-2xl border border-brand-navy animate-in fade-in zoom-in-95">
                    <div class="flex justify-between items-center pb-3 border-b border-brand-navy">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <svg class="h-5 w-5 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            New Salary Period
                        </h2>
                        <button @click="showPeriodModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-brand-navy/60 transition">✕</button>
                    </div>
                    <form @submit.prevent="submitPeriod" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Period Name *</label>
                            <input
                                type="text"
                                v-model="periodForm.period_name"
                                required
                                placeholder="e.g. September 2026"
                                class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Start Date *</label>
                                <input
                                    type="date"
                                    v-model="periodForm.start_date"
                                    required
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">End Date *</label>
                                <input
                                    type="date"
                                    v-model="periodForm.end_date"
                                    required
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-brand-navy">
                            <button
                                type="button"
                                @click="showPeriodModal = false"
                                class="px-4 py-2 border border-brand-navy text-slate-300 text-sm font-medium rounded-xl hover:bg-brand-navy/60 transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="periodForm.processing"
                                class="px-5 py-2 bg-gradient-to-r from-brand-sky to-brand-blue hover:from-sky-400 hover:to-blue-600 text-white text-sm font-semibold rounded-xl shadow-md disabled:opacity-50 transition"
                            >
                                Create Period
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Disburse Salary Modal -->
            <div v-if="showPayoutModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-[#091A2E] rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-brand-navy animate-in fade-in zoom-in-95 max-h-[92vh] overflow-y-auto">
                    <div class="flex justify-between items-center pb-3 border-b border-brand-navy">
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Disburse Staff Salary
                        </h2>
                        <button @click="showPayoutModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-brand-navy/60 transition">✕</button>
                    </div>

                    <form @submit.prevent="submitPayout" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Staff Member *</label>
                                <select
                                    v-model="payoutForm.user_id"
                                    required
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                >
                                    <option v-for="u in staffUsers" :key="u.id" :value="u.id">
                                        {{ u.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Salary Period *</label>
                                <select
                                    v-model="payoutForm.salary_period_id"
                                    required
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                >
                                    <option v-for="p in periods" :key="p.id" :value="p.id">
                                        {{ p.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Basic Salary (৳) *</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="payoutForm.basic_salary"
                                    required
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white font-mono placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Bonus / Incentives (৳)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="payoutForm.bonus"
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-emerald-400 font-mono placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Commission (৳)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="payoutForm.commission"
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-emerald-400 font-mono placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Advance Deduct (৳)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="payoutForm.advance_deduction"
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-rose-400 font-mono placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Other Deduct (৳)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="payoutForm.other_deductions"
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-rose-400 font-mono placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                        </div>

                        <!-- Net Payable Summary Card -->
                        <div class="p-3.5 bg-emerald-950/30 border border-emerald-500/30 rounded-xl flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Calculated Net Payout</span>
                            <span class="text-xl font-extrabold text-emerald-400 font-mono">৳ {{ netPayable.toLocaleString() }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Disburse From Account *</label>
                                <select
                                    v-model="payoutForm.account_id"
                                    required
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                >
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                        {{ acc.name }} (৳{{ Number(acc.balance).toLocaleString() }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Payment Date *</label>
                                <input
                                    type="date"
                                    v-model="payoutForm.payment_date"
                                    required
                                    class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Notes / Remarks</label>
                            <textarea
                                v-model="payoutForm.notes"
                                rows="2"
                                placeholder="Payment notes, transaction reference..."
                                class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-brand-navy">
                            <button
                                type="button"
                                @click="showPayoutModal = false"
                                class="px-4 py-2 border border-brand-navy text-slate-300 text-sm font-medium rounded-xl hover:bg-brand-navy/60 transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="payoutForm.processing"
                                class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-sm font-semibold rounded-xl shadow-md disabled:opacity-50 transition"
                            >
                                {{ payoutForm.processing ? 'Processing...' : 'Confirm Disbursement' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
