<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    accounts: Array,
    recentTransactions: Array,
    metrics: Object,
});

const showDeleteConfirm = ref(false);
const accountToDelete = ref(null);
const deletingAccount = ref(false);

const showStatusConfirm = ref(false);
const accountToToggle = ref(null);
const togglingStatus = ref(false);

// Transfer modal state
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

// Account Create / Edit modal state
const showAccountModal = ref(false);
const isEditing = ref(false);
const editingAccountId = ref(null);
const previewQrModal = ref(null);

const accountForm = useForm({
    name: '',
    type: 'Bangla QR',
    account_number: '',
    qr_image: null,
    qr_image_url: '',
    balance: '0.00',
    status: 'active',
});

const openCreateAccount = () => {
    isEditing.value = false;
    editingAccountId.value = null;
    accountForm.reset();
    accountForm.name = '';
    accountForm.type = 'Bangla QR';
    accountForm.account_number = '';
    accountForm.qr_image = null;
    accountForm.qr_image_url = '';
    accountForm.balance = '0.00';
    accountForm.status = 'active';
    showAccountModal.value = true;
};

const openEditAccount = (acc) => {
    isEditing.value = true;
    editingAccountId.value = acc.id;
    accountForm.name = acc.name;
    accountForm.type = acc.type;
    accountForm.account_number = acc.account_number || '';
    accountForm.qr_image = null;
    accountForm.qr_image_url = acc.qr_image || '';
    accountForm.balance = acc.balance;
    accountForm.status = acc.status || 'active';
    showAccountModal.value = true;
};

const handleQrFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        accountForm.qr_image = file;
    }
};

const submitAccount = () => {
    if (isEditing.value) {
        accountForm.post(route('admin.accounting.accounts.update', editingAccountId.value), {
            forceFormData: true,
            onSuccess: () => {
                showAccountModal.value = false;
                accountForm.reset();
            }
        });
    } else {
        accountForm.post(route('admin.accounting.accounts.store'), {
            forceFormData: true,
            onSuccess: () => {
                showAccountModal.value = false;
                accountForm.reset();
            }
        });
    }
};

const openToggleStatusModal = (acc) => {
    accountToToggle.value = acc;
    showStatusConfirm.value = true;
};

const confirmToggleStatus = () => {
    if (!accountToToggle.value) return;
    togglingStatus.value = true;
    router.patch(route('admin.accounting.accounts.toggle-status', accountToToggle.value.id), {}, {
        onFinish: () => {
            togglingStatus.value = false;
            showStatusConfirm.value = false;
            accountToToggle.value = null;
        }
    });
};

const openDeleteModal = (acc) => {
    accountToDelete.value = acc;
    showDeleteConfirm.value = true;
};

const confirmDeleteAccount = () => {
    if (!accountToDelete.value) return;
    deletingAccount.value = true;
    router.delete(route('admin.accounting.accounts.destroy', accountToDelete.value.id), {
        onFinish: () => {
            deletingAccount.value = false;
            showDeleteConfirm.value = false;
            accountToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Accounts & Cash Ledger - Pirgacha Internet" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span>Account Wallets & Payment Methods</span>
                        <span class="rounded-lg bg-emerald-500/20 border border-emerald-500/40 px-2.5 py-0.5 text-xs font-bold text-emerald-400">
                            Cash & Banking
                        </span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Manage financial accounts, banks, Bangla QR standees, and mobile wallets (bKash, Nagad, Rocket).
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        @click="openCreateAccount"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition cursor-pointer active:scale-95"
                    >
                        <span>➕</span>
                        <span>Add Payment Method / Bangla QR</span>
                    </button>
                    <button
                        @click="showTransferModal = true"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-indigo-600/30 transition cursor-pointer active:scale-95"
                    >
                        <span>🔄</span>
                        <span>Inter-Account Fund Transfer</span>
                    </button>
                </div>
            </div>

            <!-- Financial Summary Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="rounded-2xl border border-brand-sky/30 bg-brand-sky/10 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-300">Total Liquid Balance</div>
                    <div class="text-2xl font-black text-white mt-1 font-mono">
                        ৳{{ (metrics?.total_balance ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-[11px] text-brand-sky/80 mt-1">Across all active accounts</div>
                </div>

                <div class="rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Active Accounts</div>
                    <div class="text-2xl font-black text-emerald-400 mt-1">
                        {{ metrics?.active_accounts_count ?? accounts?.length ?? 0 }}
                    </div>
                    <div class="text-[11px] text-emerald-500/80 mt-1">Banks, QR & Wallets</div>
                </div>

                <div class="rounded-2xl border border-teal-500/30 bg-teal-950/20 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-teal-400">Today's Inflow</div>
                    <div class="text-2xl font-black text-teal-400 mt-1 font-mono">
                        +৳{{ (metrics?.today_inflow ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-[11px] text-teal-500/80 mt-1">Customer collections & credits</div>
                </div>

                <div class="rounded-2xl border border-rose-500/30 bg-rose-950/20 p-4 backdrop-blur-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-rose-400">Today's Outflow</div>
                    <div class="text-2xl font-black text-rose-400 mt-1 font-mono">
                        -৳{{ (metrics?.today_outflow ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                    </div>
                    <div class="text-[11px] text-rose-400/80 mt-1">Expenses & transfers out</div>
                </div>
            </div>

            <!-- Account Cards Grid -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">All Wallets & Gateways</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div
                        v-for="acc in accounts"
                        :key="acc.id"
                        class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/80 p-5 backdrop-blur-sm flex flex-col justify-between hover:border-brand-sky/40 transition shadow-lg"
                    >
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-sm font-black text-white block">
                                        {{ acc.name }}
                                    </span>
                                    <span v-if="acc.account_number" class="text-xs font-mono font-bold text-brand-orange mt-0.5 block">
                                        {{ acc.account_number }}
                                    </span>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span 
                                        :class="[
                                            acc.type === 'Bangla QR' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-black' : 'bg-indigo-500/10 text-indigo-400',
                                            'rounded-md px-2 py-0.5 text-[10px] font-bold uppercase'
                                        ]"
                                    >
                                        {{ acc.type }}
                                    </span>
                                    <span
                                        :class="[
                                            acc.status === 'active' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30',
                                            'rounded-md px-1.5 py-0.5 text-[9px] font-extrabold uppercase'
                                        ]"
                                    >
                                        {{ acc.status || 'active' }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-2xl font-black text-white font-mono mt-4">
                                ৳{{ Number(acc.balance).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                            </div>

                            <!-- Customer Portal Visible Indicator for Mobile Banking / Bangla QR -->
                            <div v-if="acc.type === 'Mobile Banking' || acc.type === 'Bangla QR'" class="mt-2 space-y-1">
                                <span
                                    v-if="acc.status === 'active'"
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full"
                                >
                                    <span>●</span>
                                    <span>Customer Renewal Portal Visible ✓</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-500 bg-slate-800 px-2 py-0.5 rounded-full"
                                >
                                    <span>○</span>
                                    <span>Hidden from Renewal Portal</span>
                                </span>

                                <!-- QR Badge / Preview Button -->
                                <div v-if="acc.qr_image" class="pt-1">
                                    <button
                                        type="button"
                                        @click="previewQrModal = acc"
                                        class="text-[10px] font-bold text-brand-orange hover:text-orange-400 inline-flex items-center gap-1 underline cursor-pointer"
                                    >
                                        <span>📷</span>
                                        <span>View Bank Bangla QR</span>
                                    </button>
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-400 mt-2">
                                Total collections: <span class="font-bold text-slate-200">{{ acc.payments_count || 0 }}</span>
                            </div>
                        </div>

                        <!-- Account Actions -->
                        <div class="mt-4 pt-3 border-t border-brand-navy/60 flex items-center justify-between gap-1.5 flex-wrap">
                            <button
                                type="button"
                                @click="openEditAccount(acc)"
                                class="text-xs font-bold text-sky-400 hover:text-sky-300 transition cursor-pointer"
                            >
                                ✏️ Edit
                            </button>
                            <button
                                type="button"
                                @click="openToggleStatusModal(acc)"
                                :class="[
                                    acc.status === 'active' ? 'text-amber-400 hover:text-amber-300' : 'text-emerald-400 hover:text-emerald-300',
                                    'text-xs font-bold transition cursor-pointer'
                                ]"
                            >
                                {{ acc.status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button
                                type="button"
                                @click="openDeleteModal(acc)"
                                class="text-xs font-bold text-rose-400 hover:text-rose-300 transition cursor-pointer"
                                title="Delete Account"
                            >
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Transactions Ledger -->
            <div class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/60 p-4 sm:p-5 backdrop-blur-sm shadow-xl">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <h2 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider">
                        Recent Money Movements & Account Transactions
                    </h2>
                    <span class="text-[11px] text-slate-400">Last 20 operations</span>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="border-b border-brand-navy bg-[#071322]/80 uppercase font-semibold text-slate-400 whitespace-nowrap">
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
                        <tbody class="divide-y divide-brand-navy/50">
                            <tr v-for="tx in recentTransactions" :key="tx.id" class="hover:bg-brand-navy/30 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-slate-400 whitespace-nowrap">{{ tx.transaction_number }}</td>
                                <td class="px-4 py-3 font-bold text-white whitespace-nowrap">{{ tx.account?.name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="rounded-md bg-brand-navy px-2 py-0.5 text-[10px] font-bold uppercase font-mono text-slate-300">
                                        {{ tx.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-emerald-400 whitespace-nowrap">
                                    <span v-if="tx.debit > 0">+৳{{ Number(tx.debit).toLocaleString() }}</span>
                                    <span v-else class="text-slate-600">-</span>
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-rose-400 whitespace-nowrap">
                                    <span v-if="tx.credit > 0">-৳{{ Number(tx.credit).toLocaleString() }}</span>
                                    <span v-else class="text-slate-600">-</span>
                                </td>
                                <td class="px-4 py-3 font-mono font-extrabold text-white whitespace-nowrap">
                                    ৳{{ Number(tx.balance_after).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 text-slate-400">{{ tx.description }}</td>
                            </tr>
                            <tr v-if="recentTransactions.length === 0">
                                <td colspan="7" class="text-center py-8 text-slate-400 text-xs">No account transactions recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View for Transactions -->
                <div class="block md:hidden space-y-3">
                    <div
                        v-for="tx in recentTransactions"
                        :key="'mob-tx-' + tx.id"
                        class="rounded-xl border border-brand-navy bg-[#071322]/80 p-3.5 space-y-2 text-xs"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-[11px] text-slate-400 font-bold">{{ tx.transaction_number }}</span>
                            <span class="rounded-md bg-brand-navy px-2 py-0.5 text-[9px] font-bold uppercase font-mono text-slate-300">
                                {{ tx.type }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1 border-t border-brand-navy/60">
                            <div>
                                <div class="font-bold text-white">{{ tx.account?.name }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ tx.description }}</div>
                            </div>
                            <div class="text-right">
                                <div v-if="tx.debit > 0" class="font-mono font-black text-emerald-400 text-sm">
                                    +৳{{ Number(tx.debit).toLocaleString() }}
                                </div>
                                <div v-else-if="tx.credit > 0" class="font-mono font-black text-rose-400 text-sm">
                                    -৳{{ Number(tx.credit).toLocaleString() }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    Bal: ৳{{ Number(tx.balance_after).toLocaleString() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="recentTransactions.length === 0" class="text-center py-8 text-slate-400 text-xs">
                        No account transactions recorded.
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Account Modal -->
        <div v-if="showAccountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">
                        {{ isEditing ? 'Edit Account / Bangla QR' : 'Add New Account / Bangla QR' }}
                    </h3>
                    <button @click="showAccountModal = false" class="text-slate-400 hover:text-white text-sm">✕</button>
                </div>

                <form @submit.prevent="submitAccount" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Account / Method Name</label>
                        <input
                            v-model="accountForm.name"
                            type="text"
                            required
                            placeholder="e.g. Bangla QR (Islami Bank / All Apps), Rocket Merchant, bKash 02"
                            class="w-full rounded-xl bg-slate-950 border border-slate-800 p-2.5 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Account Category / Type</label>
                        <select
                            v-model="accountForm.type"
                            required
                            class="w-full rounded-xl bg-slate-950 border border-slate-800 p-2.5 text-xs text-white outline-none"
                        >
                            <option value="Bangla QR">Bangla QR (Official Bank QR Standee - All Apps Compatible)</option>
                            <option value="Mobile Banking">Mobile Banking (bKash, Nagad, Rocket, Upay)</option>
                            <option value="Bank">Bank Account</option>
                            <option value="Cash">Cash in Hand / Drawer</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">
                            Account / Wallet Number (Phone Number / Terminal ID)
                        </label>
                        <input
                            v-model="accountForm.account_number"
                            type="text"
                            placeholder="e.g. 01711223344 or Terminal ID"
                            class="w-full rounded-xl bg-slate-950 border border-slate-800 p-2.5 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none font-mono"
                        />
                        <p class="text-[10px] text-slate-500 mt-1">
                            This number/ID is shown to customers for payment reference.
                        </p>
                    </div>

                    <!-- Upload Bank Bangla QR Image -->
                    <div v-if="accountForm.type === 'Bangla QR' || accountForm.type === 'Mobile Banking'" class="space-y-2 p-3 rounded-2xl border border-dashed border-slate-700 bg-slate-950">
                        <label class="block text-xs font-bold text-slate-300">
                            📷 Bank-issued Bangla QR Image (ব্যাংক থেকে প্রাপ্ত QR কোড ছবি)
                        </label>
                        <input
                            type="file"
                            @change="handleQrFileChange"
                            accept="image/*"
                            class="w-full text-xs text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500 cursor-pointer"
                        />
                        <p class="text-[10px] text-slate-400">
                            ব্যাংক থেকে পাওয়া বাংলা কিউআর স্ট্যান্ডি বা ছবির ফাইল আপলোড করুন (.png, .jpg, .svg)।
                        </p>
                        <div v-if="accountForm.qr_image_url" class="flex items-center gap-2 pt-1 text-[11px] text-emerald-400 font-mono">
                            <span>✓ Current QR Image:</span>
                            <span class="truncate max-w-[200px]">{{ accountForm.qr_image_url }}</span>
                        </div>
                    </div>

                    <div v-if="!isEditing">
                        <label class="block text-xs font-bold text-slate-400 mb-1">Opening Balance (BDT)</label>
                        <input
                            v-model="accountForm.balance"
                            type="number"
                            step="0.01"
                            class="w-full rounded-xl bg-slate-950 border border-slate-800 p-2.5 text-xs text-white font-mono outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Status</label>
                        <select
                            v-model="accountForm.status"
                            class="w-full rounded-xl bg-slate-950 border border-slate-800 p-2.5 text-xs text-white outline-none"
                        >
                            <option value="active">Active (Available for transactions & portal)</option>
                            <option value="inactive">Inactive (Disabled / Hidden)</option>
                        </select>
                    </div>

                    <div class="flex gap-2 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="showAccountModal = false"
                            class="flex-1 rounded-xl bg-slate-800 hover:bg-slate-700 p-2.5 text-xs font-semibold text-slate-300 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="accountForm.processing"
                            class="flex-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 p-2.5 text-xs font-bold text-white transition shadow-lg shadow-emerald-600/30"
                        >
                            {{ isEditing ? 'Save Changes' : 'Create Account' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview QR Modal -->
        <div v-if="previewQrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="w-full max-w-sm rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl space-y-4 text-center">
                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                    <h3 class="text-sm font-bold text-white">{{ previewQrModal.name }}</h3>
                    <button @click="previewQrModal = null" class="text-slate-400 hover:text-white text-sm">✕</button>
                </div>

                <div class="bg-white p-3 rounded-2xl shadow-inner">
                    <img :src="previewQrModal.qr_image" alt="Bangla QR" class="w-full max-h-80 object-contain mx-auto" />
                </div>

                <div class="text-xs text-slate-400 font-mono">
                    Account: <strong class="text-brand-orange">{{ previewQrModal.account_number }}</strong>
                </div>

                <button
                    type="button"
                    @click="previewQrModal = null"
                    class="w-full rounded-xl bg-slate-800 hover:bg-slate-700 py-2.5 text-xs font-bold text-white transition"
                >
                    Close Preview
                </button>
            </div>
        </div>

        <!-- Transfer Modal -->
        <div v-if="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Inter-Account Fund Transfer</h3>
                    <button @click="showTransferModal = false" class="text-slate-400 hover:text-white text-sm">✕</button>
                </div>

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

        <!-- Professional Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteConfirm"
            :title="`Delete Account: ${accountToDelete?.name || ''}`"
            :message="`Are you sure you want to permanently delete account '${accountToDelete?.name}'? If this account already contains recorded client payments, staff collections, or ledger transactions, the deletion will be protected by audit security.`"
            confirm-text="Permanently Delete"
            cancel-text="Keep Account"
            type="danger"
            :processing="deletingAccount"
            @confirm="confirmDeleteAccount"
            @cancel="showDeleteConfirm = false; accountToDelete = null;"
        />

        <!-- Professional Status Toggle Confirmation Modal -->
        <ConfirmModal
            :show="showStatusConfirm"
            :title="`${accountToToggle?.status === 'active' ? 'Deactivate' : 'Activate'} Account: ${accountToToggle?.name || ''}`"
            :message="`Are you sure you want to ${accountToToggle?.status === 'active' ? 'deactivate' : 'activate'} '${accountToToggle?.name}'? ${accountToToggle?.status === 'active' ? 'Deactivated accounts will be hidden from customer portals and collection dropdowns.' : 'Activated accounts will immediately become available for transactions and payment collections.'}`"
            :confirm-text="accountToToggle?.status === 'active' ? 'Deactivate Account' : 'Activate Account'"
            cancel-text="Cancel"
            :type="accountToToggle?.status === 'active' ? 'warning' : 'success'"
            :processing="togglingStatus"
            @confirm="confirmToggleStatus"
            @cancel="showStatusConfirm = false; accountToToggle = null;"
        />
    </AdminLayout>
</template>
