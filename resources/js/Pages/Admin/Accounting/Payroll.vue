<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    payments: Object,
    periods: Array,
    users: Array,
    accounts: Array,
});

const showPayoutModal = ref(false);
const showPeriodModal = ref(false);

const periodForm = useForm({
    period_name: '',
    start_date: '',
    end_date: '',
});

const payoutForm = useForm({
    user_id: props.users.length ? props.users[0].id : '',
    salary_period_id: props.periods.length ? props.periods[0].id : '',
    account_id: props.accounts.length ? props.accounts[0].id : '',
    basic_salary: '',
    bonus: 0,
    deductions: 0,
    advance_deductions: 0,
    payment_method: 'Cash',
    reference: '',
    notes: '',
});

const netPayable = computed(() => {
    const basic = Number(payoutForm.basic_salary) || 0;
    const bonus = Number(payoutForm.bonus) || 0;
    const deductions = Number(payoutForm.deductions) || 0;
    const advance = Number(payoutForm.advance_deductions) || 0;
    return Math.max(0, basic + bonus - deductions - advance);
});

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
            payoutForm.reset('basic_salary', 'bonus', 'deductions', 'advance_deductions', 'reference', 'notes');
        }
    });
};
</script>

<template>
    <Head title="Staff Payroll & Disbursements" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Staff Payroll & Salaries</h1>
                    <p class="text-sm text-gray-500">Manage salary periods, deductions, advances, and payroll payouts</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="showPeriodModal = true"
                        class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg shadow-sm transition"
                    >
                        + Create Period
                    </button>
                    <button
                        @click="showPayoutModal = true"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Disburse Salary
                    </button>
                </div>
            </div>

            <!-- Active Periods Quick List -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Salary Periods</h3>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="p in periods"
                        :key="p.id"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-sm font-medium bg-gray-50 border-gray-200 text-gray-700"
                    >
                        <span class="w-2 h-2 rounded-full" :class="p.is_closed ? 'bg-gray-400' : 'bg-emerald-500'"></span>
                        <span>{{ p.period_name }}</span>
                        <span class="text-xs text-gray-400 font-mono">({{ p.start_date }} ~ {{ p.end_date }})</span>
                        <span v-if="p.is_closed" class="text-[10px] uppercase font-bold text-gray-500 bg-gray-200 px-1 rounded">Closed</span>
                    </div>
                </div>
            </div>

            <!-- Payroll Payments Ledger -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Disbursed Date</th>
                                <th class="px-4 py-3">Staff Member</th>
                                <th class="px-4 py-3">Period</th>
                                <th class="px-4 py-3">Basic</th>
                                <th class="px-4 py-3">Bonus</th>
                                <th class="px-4 py-3">Deductions</th>
                                <th class="px-4 py-3">Net Paid</th>
                                <th class="px-4 py-3">Paid From</th>
                                <th class="px-4 py-3">Reference / Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                    No salary disbursements recorded yet.
                                </td>
                            </tr>
                            <tr v-for="pay in payments.data" :key="pay.id" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-900 font-medium">
                                    {{ pay.payment_date }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold text-gray-900">{{ pay.user?.name }}</div>
                                    <div class="text-xs text-gray-500">{{ pay.user?.email }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                    {{ pay.period?.period_name }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    ৳ {{ Number(pay.basic_salary).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-emerald-600 font-medium">
                                    +৳ {{ Number(pay.bonus).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-rose-500 text-xs">
                                    -৳ {{ (Number(pay.deductions) + Number(pay.advance_deductions)).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-gray-900">
                                    ৳ {{ Number(pay.net_salary).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                    {{ pay.account?.name }} ({{ pay.payment_method }})
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">
                                    <span v-if="pay.payment_reference" class="font-mono text-gray-700 block">Ref: {{ pay.payment_reference }}</span>
                                    <span>{{ pay.notes || '—' }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex gap-1">
                        <template v-for="(link, i) in payments.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1 text-xs rounded border transition',
                                    link.active ? 'bg-emerald-600 text-white border-emerald-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-100 border-gray-300'
                                ]"
                                v-html="link.label"
                            ></button>
                            <span v-else class="px-3 py-1 text-xs text-gray-400 border border-transparent" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Create Salary Period Modal -->
            <div v-if="showPeriodModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100 animate-in fade-in zoom-in-95">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                        <h2 class="text-base font-bold text-gray-900">New Salary Period</h2>
                        <button @click="showPeriodModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form @submit.prevent="submitPeriod" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Period Name *</label>
                            <input
                                type="text"
                                v-model="periodForm.period_name"
                                required
                                placeholder="e.g. September 2026"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Start Date *</label>
                                <input
                                    type="date"
                                    v-model="periodForm.start_date"
                                    required
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">End Date *</label>
                                <input
                                    type="date"
                                    v-model="periodForm.end_date"
                                    required
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="showPeriodModal = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="periodForm.processing"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm disabled:opacity-50"
                            >
                                Create Period
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Disburse Salary Modal -->
            <div v-if="showPayoutModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-gray-100 animate-in fade-in zoom-in-95">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900">Disburse Staff Salary</h2>
                        <button @click="showPayoutModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="submitPayout" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Staff Member *</label>
                                <select
                                    v-model="payoutForm.user_id"
                                    required
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                    <option v-for="u in users" :key="u.id" :value="u.id">
                                        {{ u.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Salary Period *</label>
                                <select
                                    v-model="payoutForm.salary_period_id"
                                    required
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                    <option v-for="p in periods" :key="p.id" :value="p.id">
                                        {{ p.period_name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Basic Salary (৳) *</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="payoutForm.basic_salary"
                                    required
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 font-medium"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Bonus (৳)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="payoutForm.bonus"
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-emerald-600 font-medium"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Deductions (৳)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="payoutForm.deductions"
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-rose-500 font-medium"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Advance Deductions (৳)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="payoutForm.advance_deductions"
                                    placeholder="0.00"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-rose-500 font-medium"
                                />
                            </div>
                        </div>

                        <!-- Net Payable Summary -->
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Calculated Net Payout</span>
                            <span class="text-xl font-extrabold text-emerald-700">৳ {{ netPayable.toLocaleString() }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Disburse From Account *</label>
                                <select
                                    v-model="payoutForm.account_id"
                                    required
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                        {{ acc.name }} (৳{{ Number(acc.current_balance).toLocaleString() }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Payment Method</label>
                                <select
                                    v-model="payoutForm.payment_method"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                    <option value="Cash">Cash</option>
                                    <option value="Bank">Bank Transfer</option>
                                    <option value="bKash">bKash</option>
                                    <option value="Nagad">Nagad</option>
                                    <option value="Rocket">Rocket</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Trx Reference / Cheque No</label>
                            <input
                                type="text"
                                v-model="payoutForm.reference"
                                placeholder="e.g. Bank slip or bKash Trx ID"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Notes</label>
                            <textarea
                                v-model="payoutForm.notes"
                                rows="2"
                                placeholder="Remarks..."
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="showPayoutModal = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="payoutForm.processing"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm disabled:opacity-50"
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
