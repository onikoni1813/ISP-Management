<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    accounts: Array,
    recentTransactions: Array,
});

const showTransferModal = ref(false);
const transferForm = useForm({
    from_account_id: props.accounts[0]?.id || '',
    to_account_id: props.accounts[1]?.id || '',
    amount: '',
    notes: '',
});

const submitTransfer = () => {
    transferForm.post(route('admin.accounting.transfer'), {
        onSuccess: () => {
            showTransferModal.value = false;
            transferForm.reset('amount', 'notes');
        }
    });
};
</script>

<template>
    <Head title="Accounts & Cash Ledger - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Account Wallets & Cash Flow</h1>
                <p class="text-sm text-slate-400 mt-1">Traceable funds across Cash, Bank, bKash, and Nagad accounts.</p>
            </div>
            <button
                @click="showTransferModal = true"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-indigo-600/30"
            >
                🔄 Inter-Account Fund Transfer
            </button>
        </div>

        <!-- Account Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div v-for="acc in accounts" :key="acc.id" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ acc.name }}</span>
                    <span class="rounded-md bg-indigo-500/10 text-indigo-400 px-2 py-0.5 text-[10px] font-bold uppercase">
                        {{ acc.type }}
                    </span>
                </div>
                <div class="text-2xl font-black text-white font-mono mt-3">
                    ৳{{ acc.balance }}
                </div>
                <div class="text-xs text-slate-500 mt-1">Total collections: {{ acc.payments_count || 0 }}</div>
            </div>
        </div>

        <!-- Account Transactions Ledger -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-sm">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider mb-4">
                Recent Money Movements & Account Transactions
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-950/50 uppercase font-semibold text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Txn #</th>
                            <th class="px-4 py-3">Account</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Inflow (Debit)</th>
                            <th class="px-4 py-3">Outflow (Credit)</th>
                            <th class="px-4 py-3">Balance After</th>
                            <th class="px-4 py-3">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <tr v-for="tx in recentTransactions" :key="tx.id" class="hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-mono font-bold text-slate-400">{{ tx.transaction_number }}</td>
                            <td class="px-4 py-3 font-bold text-white">{{ tx.account?.name }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-slate-800 px-2 py-0.5 text-[10px] font-bold uppercase font-mono">
                                    {{ tx.type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-emerald-400">
                                <span v-if="tx.debit > 0">+৳{{ tx.debit }}</span>
                                <span v-else class="text-slate-600">-</span>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-rose-400">
                                <span v-if="tx.credit > 0">-৳{{ tx.credit }}</span>
                                <span v-else class="text-slate-600">-</span>
                            </td>
                            <td class="px-4 py-3 font-mono font-extrabold text-white">৳{{ tx.balance_after }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ tx.description }}</td>
                        </tr>
                        <tr v-if="recentTransactions.length === 0">
                            <td colspan="7" class="text-center py-6 text-slate-500">No account transactions recorded.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Transfer Modal -->
        <div v-if="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-white">Inter-Account Fund Transfer</h3>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">From Account (Source)</label>
                    <select v-model="transferForm.from_account_id" class="w-full rounded-xl bg-slate-950 border-slate-800 p-2.5 text-xs text-white">
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} (৳{{ a.balance }})</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">To Account (Destination)</label>
                    <select v-model="transferForm.to_account_id" class="w-full rounded-xl bg-slate-950 border-slate-800 p-2.5 text-xs text-white">
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} (৳{{ a.balance }})</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Transfer Amount (BDT)</label>
                    <input v-model="transferForm.amount" type="number" required class="w-full rounded-xl bg-slate-950 border-slate-800 p-2.5 text-xs text-white font-mono" />
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Notes / Reason</label>
                    <input v-model="transferForm.notes" type="text" placeholder="e.g. Bank cash deposit" class="w-full rounded-xl bg-slate-950 border-slate-800 p-2.5 text-xs text-white" />
                </div>
                <div class="flex gap-2 pt-2">
                    <button @click="showTransferModal = false" class="flex-1 rounded-xl bg-slate-800 p-2.5 text-xs font-semibold text-slate-300">Cancel</button>
                    <button @click="submitTransfer" :disabled="transferForm.processing" class="flex-1 rounded-xl bg-indigo-600 p-2.5 text-xs font-bold text-white">Transfer</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
