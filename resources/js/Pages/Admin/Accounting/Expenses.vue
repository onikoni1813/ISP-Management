<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    expenses: Object,
    categories: Array,
    accounts: Array,
    metrics: Object,
    filters: Object,
});

const showAddModal = ref(false);
const search = ref(props.filters.search || '');
const filterCategory = ref(props.filters.category_id || '');
const filterDateFrom = ref(props.filters.date_from || '');
const filterDateTo = ref(props.filters.date_to || '');

const form = useForm({
    title: '',
    expense_category_id: props.categories.length ? props.categories[0].id : '',
    account_id: props.accounts.length ? props.accounts[0].id : '',
    amount: '',
    expense_date: new Date().toISOString().slice(0, 10),
    recipient: '',
    payment_reference: '',
    description: '',
});

const applyFilters = () => {
    router.get(route('admin.accounting.expenses'), {
        search: search.value || undefined,
        category_id: filterCategory.value || undefined,
        date_from: filterDateFrom.value || undefined,
        date_to: filterDateTo.value || undefined,
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
    filterCategory.value = '';
    filterDateFrom.value = '';
    filterDateTo.value = '';
    applyFilters();
};

const submitExpense = () => {
    form.post(route('admin.accounting.expenses.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset('title', 'amount', 'recipient', 'payment_reference', 'description');
        }
    });
};
</script>

<template>
    <Head title="Expense Management - Pirgacha Internet" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <Link :href="route('admin.dashboard')" class="hover:text-white">Admin</Link>
                        <span>/</span>
                        <Link :href="route('admin.accounting.accounts')" class="hover:text-white">Accounts & Ledger</Link>
                        <span>/</span>
                        <span class="text-slate-200">Expenses</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white mt-1">Operational Expense Ledger</h1>
                    <p class="text-xs text-slate-400">Audit company disbursements, vendor bills, utility payments, and operational costs.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Link
                        :href="route('admin.accounting.accounts')"
                        class="flex-1 sm:flex-none text-center rounded-xl border border-brand-navy bg-[#0B1E36] px-4 py-2.5 text-xs font-semibold text-brand-sky hover:bg-[#102B4D] hover:text-white transition"
                    >
                        View Account Wallets
                    </Link>
                    <button
                        @click="showAddModal = true"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-rose-600 to-rose-500 hover:opacity-95 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-600/25 transition active:scale-95 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Record New Expense
                    </button>
                </div>
            </div>

            <!-- Expense Summary Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="rounded-2xl border border-rose-500/30 bg-rose-950/20 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-rose-400">Total All-Time Expenses</div>
                    <div class="text-2xl font-black text-white mt-1 font-mono">
                        ৳{{ Number(metrics?.total_expenses ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-[11px] text-rose-400/80 mt-1">{{ metrics?.records_count ?? 0 }} total vouchers</div>
                </div>

                <div class="rounded-2xl border border-amber-500/30 bg-amber-950/20 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-amber-400">This Month Expenses</div>
                    <div class="text-2xl font-black text-amber-400 mt-1 font-mono">
                        ৳{{ Number(metrics?.this_month_expenses ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-[11px] text-amber-500/80 mt-1">Current billing cycle</div>
                </div>

                <div class="rounded-2xl border border-brand-sky/30 bg-brand-sky/10 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-300">Today's Disbursements</div>
                    <div class="text-2xl font-black text-brand-sky mt-1 font-mono">
                        ৳{{ Number(metrics?.today_expenses ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-[11px] text-brand-sky/80 mt-1">Posted today</div>
                </div>

                <div class="rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Active Accounts</div>
                    <div class="text-2xl font-black text-emerald-400 mt-1">
                        {{ accounts?.length ?? 0 }}
                    </div>
                    <div class="text-[11px] text-emerald-500/80 mt-1">Ready for payment payout</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-2xl border border-brand-navy/60 bg-[#091A2E]/80 p-4 backdrop-blur-sm shadow-xl grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Search Expenses</label>
                    <input
                        type="text"
                        v-model="search"
                        placeholder="Search voucher #, title, description..."
                        class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Category</label>
                    <select
                        v-model="filterCategory"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">From Date</label>
                    <input
                        type="date"
                        v-model="filterDateFrom"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white font-mono focus:border-brand-sky focus:outline-none"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">To Date</label>
                    <div class="flex items-center gap-2">
                        <input
                            type="date"
                            v-model="filterDateTo"
                            @change="applyFilters"
                            class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white font-mono focus:border-brand-sky focus:outline-none"
                        />
                        <button
                            v-if="search || filterCategory || filterDateFrom || filterDateTo"
                            @click="resetFilters"
                            class="rounded-xl border border-rose-500/30 bg-rose-950/20 px-3 py-2 text-xs font-bold text-rose-400 hover:bg-rose-950/40 transition shrink-0"
                            title="Reset all filters"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Expenses Desktop Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-hidden rounded-2xl border border-brand-navy/80 bg-[#091A2E]/80 backdrop-blur-sm shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="border-b border-brand-navy bg-[#071322]/80 uppercase font-semibold text-slate-400 whitespace-nowrap">
                            <tr>
                                <th class="px-5 py-3.5">Date</th>
                                <th class="px-5 py-3.5">Expense / Vouch #</th>
                                <th class="px-5 py-3.5">Title & Payee</th>
                                <th class="px-5 py-3.5">Paid From Account</th>
                                <th class="px-5 py-3.5">Amount</th>
                                <th class="px-5 py-3.5">Reference / Details</th>
                                <th class="px-5 py-3.5">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-navy/50">
                            <tr v-if="!expenses.data || expenses.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500 text-xs">
                                    No expense records found matching your filter criteria.
                                </td>
                            </tr>
                            <tr v-for="exp in expenses.data" :key="exp.id" class="hover:bg-brand-navy/30 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap text-slate-200 font-mono text-xs">
                                    {{ formatDate(exp.expense_date) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                        {{ exp.category?.name || 'General' }}
                                    </span>
                                    <div class="text-[11px] font-mono text-brand-sky mt-1">{{ exp.expense_number }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-white">{{ exp.title || exp.recipient || 'Expense' }}</div>
                                    <div v-if="exp.recipient && exp.title" class="text-xs text-slate-400">Payee: {{ exp.recipient }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="rounded-lg bg-[#071322] px-2.5 py-1 text-xs text-slate-300 border border-brand-navy font-medium">
                                        {{ exp.account?.name }} ({{ exp.account?.type }})
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-mono font-black text-rose-400 text-sm">
                                    ৳{{ Number(exp.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-400 max-w-xs truncate">
                                    <span v-if="exp.payment_reference" class="font-mono text-slate-300 block">Ref: {{ exp.payment_reference }}</span>
                                    <span>{{ exp.description || '—' }}</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-400 text-xs">
                                    {{ exp.payer?.name || 'System' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="expenses.links && expenses.links.length > 3" class="px-5 py-3.5 border-t border-brand-navy bg-[#071322]/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400">
                    <div>Showing {{ expenses.from }} to {{ expenses.to }} of {{ expenses.total }} expenses</div>
                    <div class="flex gap-1.5 flex-wrap justify-center">
                        <template v-for="(link, i) in expenses.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-xl border transition font-semibold',
                                    link.active 
                                        ? 'bg-gradient-to-r from-brand-orange to-brand-amber text-white border-brand-orange shadow-sm' 
                                        : 'bg-[#091A2E] text-slate-300 hover:text-white hover:bg-brand-navy/60 border-brand-navy'
                                ]"
                                v-html="link.label"
                            ></button>
                            <span v-else class="px-3 py-1.5 text-xs text-slate-600 border border-transparent font-semibold" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Expenses Mobile Cards View (Visible on mobile/tablet) -->
            <div class="block md:hidden space-y-3">
                <div
                    v-for="exp in expenses.data"
                    :key="'mob-exp-' + exp.id"
                    class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/80 p-4 shadow-lg backdrop-blur-md space-y-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-[11px] font-bold text-brand-sky">{{ exp.expense_number }}</span>
                            <div class="font-bold text-white text-sm mt-0.5">{{ exp.title || exp.recipient || 'Expense' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-black text-rose-400 text-base">
                                ৳{{ Number(exp.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-bold uppercase bg-rose-500/10 text-rose-400 border border-rose-500/30 mt-1">
                                {{ exp.category?.name || 'General' }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs py-2 border-y border-brand-navy/60">
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-400">Date</div>
                            <div class="text-slate-200 font-mono mt-0.5">{{ formatDate(exp.expense_date) }}</div>
                            <div class="text-[10px] text-slate-400 mt-1">By: {{ exp.payer?.name || 'System' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-400">Paid Account</div>
                            <div class="text-white font-bold truncate mt-0.5">{{ exp.account?.name }}</div>
                            <div class="text-[10px] text-slate-400 truncate">{{ exp.account?.type }}</div>
                        </div>
                    </div>

                    <div v-if="exp.description || exp.payment_reference" class="text-xs text-slate-300 bg-[#071322]/60 rounded-xl p-2.5 border border-brand-navy/40">
                        <div v-if="exp.payment_reference" class="font-mono text-[11px] text-brand-sky mb-0.5">Ref: {{ exp.payment_reference }}</div>
                        <div>{{ exp.description || 'No description provided' }}</div>
                    </div>
                </div>

                <div v-if="!expenses.data || expenses.data.length === 0" class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/40 p-8 text-center text-slate-400 text-xs">
                    No expense records found matching your filter criteria.
                </div>

                <!-- Mobile Pagination -->
                <div v-if="expenses.links && expenses.links.length > 3" class="p-3 border border-brand-navy/60 rounded-2xl bg-[#091A2E]/80 flex justify-center gap-1.5 flex-wrap">
                    <template v-for="(link, i) in expenses.links" :key="'mob-page-' + i">
                        <button
                            v-if="link.url"
                            @click="router.get(link.url, {}, { preserveState: true })"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-xl border transition font-semibold',
                                link.active 
                                    ? 'bg-gradient-to-r from-brand-orange to-brand-amber text-white border-brand-orange' 
                                    : 'bg-[#071322] text-slate-300 hover:text-white border-brand-navy'
                            ]"
                            v-html="link.label"
                        ></button>
                    </template>
                </div>
            </div>

            <!-- Record Expense Modal -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="showAddModal = false" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>

                <div class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-3xl border border-brand-navy bg-[#091A2E] p-4 sm:p-6 shadow-2xl space-y-5">
                    <div class="flex justify-between items-center pb-4 border-b border-brand-navy">
                        <div>
                            <h2 class="text-lg font-black text-white">Record Operational Expense</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Post an expense voucher and disburse funds from company account.</p>
                        </div>
                        <button @click="showAddModal = false" class="rounded-xl p-1 text-slate-400 hover:text-white hover:bg-brand-navy/60 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitExpense" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Expense Title *</label>
                            <input
                                type="text"
                                v-model="form.title"
                                required
                                placeholder="e.g. Office Rent, Bandwidth Bill, Fuel"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                            <div v-if="form.errors.title" class="text-xs text-rose-400 mt-1">{{ form.errors.title }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Expense Category *</label>
                            <select
                                v-model="form.expense_category_id"
                                required
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            >
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Pay From Account *</label>
                                <select
                                    v-model="form.account_id"
                                    required
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                                >
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                        {{ acc.name }} (Bal: ৳{{ Number(acc.balance).toLocaleString() }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Amount (৳) *</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    v-model="form.amount"
                                    required
                                    placeholder="0.00"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs font-mono font-bold text-rose-400 placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                                />
                                <div v-if="form.errors.amount" class="text-xs text-rose-400 mt-1">{{ form.errors.amount }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Expense Date *</label>
                                <input
                                    type="date"
                                    v-model="form.expense_date"
                                    required
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs font-mono text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Recipient / Vendor</label>
                                <input
                                    type="text"
                                    v-model="form.recipient"
                                    placeholder="Vendor name or staff"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Reference / Voucher No</label>
                            <input
                                type="text"
                                v-model="form.payment_reference"
                                placeholder="e.g. Receipt #1234 or Trx ID"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs font-mono text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Notes / Description</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Purpose of this expense..."
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            ></textarea>
                            <div v-if="form.errors.description" class="text-xs text-rose-400 mt-1">{{ form.errors.description }}</div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-navy">
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="rounded-xl border border-brand-navy bg-transparent px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-xl bg-gradient-to-r from-rose-600 to-rose-500 hover:opacity-95 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-600/25 transition disabled:opacity-50"
                            >
                                {{ form.processing ? 'Posting...' : 'Post Expense' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
