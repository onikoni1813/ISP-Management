<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    expenses: Object,
    categories: Array,
    accounts: Array,
    filters: Object,
});

const showAddModal = ref(false);
const filterCategory = ref(props.filters.category_id || '');
const filterDateFrom = ref(props.filters.date_from || '');
const filterDateTo = ref(props.filters.date_to || '');

const form = useForm({
    expense_category_id: props.categories.length ? props.categories[0].id : '',
    account_id: props.accounts.length ? props.accounts[0].id : '',
    amount: '',
    expense_date: new Date().toISOString().slice(0, 10),
    recipient: '',
    payment_reference: '',
    notes: '',
});

const applyFilters = () => {
    router.get(route('admin.accounting.expenses'), {
        category_id: filterCategory.value || undefined,
        date_from: filterDateFrom.value || undefined,
        date_to: filterDateTo.value || undefined,
    }, { preserveState: true, replace: true });
};

const submitExpense = () => {
    form.post(route('admin.accounting.expenses.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset('amount', 'recipient', 'payment_reference', 'notes');
        }
    });
};
</script>

<template>
    <Head title="Expense Management" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Expense Ledger</h1>
                    <p class="text-sm text-gray-500">Track and categorize company expenditures & disbursements</p>
                </div>
                <div>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg shadow-sm transition"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Record New Expense
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Category</label>
                    <select
                        v-model="filterCategory"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">From Date</label>
                    <input
                        type="date"
                        v-model="filterDateFrom"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">To Date</label>
                    <input
                        type="date"
                        v-model="filterDateTo"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                    />
                </div>
                <div>
                    <button
                        v-if="filterCategory || filterDateFrom || filterDateTo"
                        @click="filterCategory = ''; filterDateFrom = ''; filterDateTo = ''; applyFilters()"
                        class="text-xs text-gray-500 hover:text-gray-800 underline pb-2"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Recipient / Vendor</th>
                                <th class="px-4 py-3">Paid From Account</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Reference / Notes</th>
                                <th class="px-4 py-3">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="!expenses.data || expenses.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                    No expense records found.
                                </td>
                            </tr>
                            <tr v-for="exp in expenses.data" :key="exp.id" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-900 font-medium">
                                    {{ exp.expense_date }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ exp.category?.name || 'General' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-800">
                                    {{ exp.recipient || 'N/A' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    {{ exp.account?.name }} ({{ exp.account?.type }})
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-rose-600">
                                    ৳ {{ Number(exp.amount).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 max-w-xs truncate">
                                    <span v-if="exp.payment_reference" class="font-mono text-xs text-gray-700 block">Ref: {{ exp.payment_reference }}</span>
                                    <span>{{ exp.notes || '—' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">
                                    {{ exp.creator?.name || 'System' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="expenses.links && expenses.links.length > 3" class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex gap-1">
                        <template v-for="(link, i) in expenses.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1 text-xs rounded border transition',
                                    link.active ? 'bg-rose-600 text-white border-rose-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-100 border-gray-300'
                                ]"
                                v-html="link.label"
                            ></button>
                            <span v-else class="px-3 py-1 text-xs text-gray-400 border border-transparent" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Record Expense Modal -->
            <div v-if="showAddModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-gray-100 animate-in fade-in zoom-in-95">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900">Record Operational Expense</h2>
                        <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="submitExpense" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Expense Category *</label>
                            <select
                                v-model="form.expense_category_id"
                                required
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            >
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Pay From Account *</label>
                                <select
                                    v-model="form.account_id"
                                    required
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                >
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                        {{ acc.name }} (Bal: ৳{{ Number(acc.current_balance).toLocaleString() }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Amount (৳) *</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    v-model="form.amount"
                                    required
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500 font-bold"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Expense Date *</label>
                                <input
                                    type="date"
                                    v-model="form.expense_date"
                                    required
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Recipient / Vendor</label>
                                <input
                                    type="text"
                                    v-model="form.recipient"
                                    placeholder="Vendor name or staff"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Reference / Voucher No</label>
                            <input
                                type="text"
                                v-model="form.payment_reference"
                                placeholder="e.g. Receipt #1234 or Trx ID"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Notes / Description</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Purpose of this expense..."
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg shadow-sm disabled:opacity-50"
                            >
                                {{ form.processing ? 'Saving...' : 'Post Expense' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
