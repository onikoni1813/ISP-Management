<script setup>
import { ref, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    gateways: Array,
    balanceInfo: Object,
});

const showAddModal = ref(false);
const showTestModal = ref(false);
const selectedGatewayForTest = ref(null);
const checkingBalance = ref(false);
const liveBalanceMap = ref({});

const showDeleteConfirm = ref(false);
const gatewayToDelete = ref(null);
const deletingGateway = ref(false);

const form = useForm({
    name: '',
    driver: 'alphasms',
    api_url: 'https://api.sms.net.bd/sendsms',
    api_key: '',
    sender_id: 'PirgachaNet',
    is_active: true,
});

const testForm = useForm({
    recipient: '',
    message: 'Test SMS from Pirgacha Internet Gateway Integration.',
});

const editingGateway = ref(null);

// Watch driver change to autofill Alpha SMS / BulkSMSDhaka endpoints
watch(() => form.driver, (newDriver) => {
    if (newDriver === 'alphasms') {
        if (!form.name || form.name === 'Generic HTTP Gateway' || form.name.includes('BDBulkSMS') || form.name.includes('Bulk SMS Dhaka')) form.name = 'Alpha SMS (sms.net.bd)';
        form.api_url = 'https://api.sms.net.bd/sendsms';
    } else if (newDriver === 'bulksmsdhaka') {
        if (!form.name || form.name === 'Generic HTTP Gateway' || form.name.includes('Alpha SMS') || form.name.includes('BDBulkSMS')) form.name = 'Bulk SMS Dhaka';
        form.api_url = 'https://bulksmsdhaka.net/api';
    } else if (newDriver === 'bdbulksms') {
        if (!form.name || form.name === 'Generic HTTP Gateway' || form.name.includes('Alpha SMS') || form.name.includes('Bulk SMS Dhaka')) form.name = 'BDBulkSMS (bdbulksms.net)';
        form.api_url = 'https://api.bdbulksms.net/api.php';
    } else if (newDriver === 'greenweb') {
        if (!form.name) form.name = 'Greenweb SMS';
        form.api_url = 'https://api.greenweb.com.bd/api.php';
    } else if (newDriver === 'bulksmsbd') {
        if (!form.name) form.name = 'BulkSMSBD';
        form.api_url = 'http://bulksmsbd.net/api/smsapi';
    } else if (newDriver === 'log') {
        form.api_url = '';
    }
});

const openAddModal = () => {
    editingGateway.value = null;
    form.reset();
    form.name = 'Alpha SMS (sms.net.bd)';
    form.driver = 'alphasms';
    form.api_url = 'https://api.sms.net.bd/sendsms';
    form.api_key = '';
    form.sender_id = '';
    form.is_active = true;
    showAddModal.value = true;
};

const openEditModal = (gw) => {
    editingGateway.value = gw;
    form.name = gw.name;
    form.driver = gw.driver;
    form.api_url = gw.api_url || '';
    form.api_key = gw.api_key || '';
    form.sender_id = gw.sender_id || '';
    form.is_active = Boolean(gw.is_active);
    showAddModal.value = true;
};

const submitGateway = () => {
    if (editingGateway.value) {
        form.put(route('admin.sms.gateways.update', editingGateway.value.id), {
            onSuccess: () => {
                showAddModal.value = false;
                editingGateway.value = null;
                form.reset();
            }
        });
    } else {
        form.post(route('admin.sms.gateways.store'), {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            }
        });
    }
};

const activateGateway = (id) => {
    router.post(route('admin.sms.gateways.activate', id));
};

const openDeleteConfirm = (gw) => {
    gatewayToDelete.value = gw;
    showDeleteConfirm.value = true;
};

const confirmDeleteGateway = () => {
    if (!gatewayToDelete.value) return;
    deletingGateway.value = true;
    router.delete(route('admin.sms.gateways.destroy', gatewayToDelete.value.id), {
        onFinish: () => {
            deletingGateway.value = false;
            showDeleteConfirm.value = false;
            gatewayToDelete.value = null;
        }
    });
};

const openTestModal = (gw) => {
    selectedGatewayForTest.value = gw;
    testForm.reset();
    testForm.message = `Test SMS from Pirgacha Internet via ${gw.name} at ${new Date().toLocaleTimeString()}.`;
    showTestModal.value = true;
};

const submitTestSms = () => {
    if (!selectedGatewayForTest.value) return;
    testForm.post(route('admin.sms.gateways.test', selectedGatewayForTest.value.id), {
        onSuccess: () => {
            showTestModal.value = false;
            testForm.reset();
        }
    });
};

const checkLiveBalance = async (gw) => {
    checkingBalance.value = true;
    try {
        const res = await fetch(route('admin.sms.gateways.balance', gw.id));
        const data = await res.json();
        liveBalanceMap.value[gw.id] = data;
    } catch (e) {
        liveBalanceMap.value[gw.id] = { success: false, error: 'Connection failed' };
    } finally {
        checkingBalance.value = false;
    }
};
</script>

<template>
    <Head title="SMS Gateways Configuration - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Top Navigation & Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.sms.index')" class="text-xs text-brand-sky hover:underline flex items-center gap-1 font-medium">
                            ← SMS Logs Hub
                        </Link>
                    </div>
                    <h1 class="text-2xl font-bold text-white tracking-tight mt-1 flex items-center gap-2.5">
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-brand-sky border border-brand-navy">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </span>
                        SMS Gateways & API Configuration
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">
                        Integrated Alpha SMS (sms.net.bd), Greenweb, and custom HTTP BD aggregator gateways for automated OTP, billing notices & payment receipts.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="openAddModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-orange to-brand-amber hover:opacity-95 text-white text-xs font-bold rounded-xl shadow-lg shadow-brand-orange/20 transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New Gateway
                    </button>
                </div>
            </div>

            <!-- Active Gateway Live Status Banner -->
            <div v-if="balanceInfo && balanceInfo.success" class="rounded-2xl border border-emerald-500/40 bg-gradient-to-r from-emerald-950/40 via-[#091A2E] to-brand-sky/10 p-5 shadow-xl backdrop-blur-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="h-10 w-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 font-bold text-lg">
                        ৳
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                            <span>Active Provider Balance</span>
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        </div>
                        <div class="text-2xl font-black text-white font-mono mt-0.5">
                            ৳{{ balanceInfo.balance }}
                        </div>
                    </div>
                </div>

                <div class="text-right text-xs text-slate-400">
                    <div>Status: <span class="text-emerald-400 font-semibold">Live & Connected</span></div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Ready for automated billing & customer dispatch</div>
                </div>
            </div>

            <!-- Gateways Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="gw in gateways"
                    :key="gw.id"
                    class="bg-[#091A2E]/90 backdrop-blur-md rounded-2xl border border-brand-navy p-6 shadow-xl flex flex-col justify-between transition hover:border-brand-sky/40"
                >
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-brand-navy">
                            <div>
                                <h3 class="font-bold text-white text-base">{{ gw.name }}</h3>
                                <span class="text-[10px] font-mono text-brand-sky uppercase">{{ gw.driver }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button
                                    @click="openEditModal(gw)"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-brand-navy hover:border-brand-sky text-slate-300 hover:text-white transition flex items-center gap-1 bg-[#0B1E36]"
                                    title="Configure Gateway Credentials"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                                <button
                                    @click="openDeleteConfirm(gw)"
                                    class="px-2 py-1 rounded-lg text-[11px] font-semibold border border-rose-900/40 hover:border-rose-500/60 text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 transition flex items-center gap-1 bg-[#0B1E36]"
                                    title="Delete Gateway"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider',
                                        gw.is_active 
                                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' 
                                            : 'bg-slate-800 text-slate-400 border border-slate-700'
                                    ]"
                                >
                                    {{ gw.is_active ? '● Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2.5 text-xs text-slate-300">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-400">Provider Driver:</span>
                                <span class="font-mono text-white font-bold">{{ gw.driver }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-400">Sender ID / Masking:</span>
                                <span class="font-mono text-brand-orange font-bold">{{ gw.sender_id || 'Non-masking' }}</span>
                            </div>

                            <div>
                                <span class="font-semibold text-slate-400">Endpoint:</span>
                                <span class="font-mono text-slate-400 text-[11px] block truncate mt-0.5 bg-[#071322] px-2 py-1 rounded-lg border border-brand-navy/60">
                                    {{ gw.api_url || 'Internal Logger' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-400">API Key:</span>
                                <span class="font-mono text-slate-400">{{ gw.api_key ? '••••••••••••••••' : 'Using .env' }}</span>
                            </div>

                            <!-- Live Balance Display if requested -->
                            <div v-if="liveBalanceMap[gw.id]" class="mt-3 p-2.5 rounded-xl border border-brand-sky/30 bg-brand-sky/10 text-xs">
                                <div v-if="liveBalanceMap[gw.id].success" class="flex items-center justify-between">
                                    <span class="text-brand-sky font-semibold">Account Balance:</span>
                                    <span class="font-mono font-bold text-white">৳{{ liveBalanceMap[gw.id].balance }}</span>
                                </div>
                                <div v-else class="text-rose-400 text-[11px]">
                                    {{ liveBalanceMap[gw.id].error }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-brand-navy space-y-2.5">
                        <!-- Balance & Test Action Row -->
                        <div class="flex items-center gap-2">
                            <button
                                v-if="gw.driver === 'alphasms' || gw.driver === 'bulksmsdhaka'"
                                @click="checkLiveBalance(gw)"
                                :disabled="checkingBalance"
                                class="flex-1 py-1.5 px-2.5 text-center text-[11px] font-bold rounded-xl border border-brand-navy bg-[#0B1E36] hover:bg-[#102B4D] text-brand-sky hover:text-white transition"
                            >
                                💰 Check Balance
                            </button>

                            <button
                                @click="openTestModal(gw)"
                                class="flex-1 py-1.5 px-2.5 text-center text-[11px] font-bold rounded-xl border border-brand-navy bg-[#0B1E36] hover:bg-[#102B4D] text-amber-400 hover:text-white transition"
                            >
                                ✉️ Test Send
                            </button>
                        </div>

                        <!-- Activate Button -->
                        <div class="pt-1">
                            <button
                                v-if="!gw.is_active"
                                @click="activateGateway(gw.id)"
                                class="w-full py-2 text-xs font-bold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl transition shadow-md shadow-emerald-950/40"
                            >
                                Set as Primary Active Gateway
                            </button>
                            <div v-else class="py-2 text-center text-xs font-bold text-emerald-400 flex items-center justify-center gap-1.5 bg-emerald-950/30 rounded-xl border border-emerald-500/20">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Primary Dispatch Gateway
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Gateway Modal -->
            <div v-if="showAddModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-[#091A2E] rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-brand-navy">
                    <div class="flex justify-between items-center pb-4 border-b border-brand-navy">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>{{ editingGateway ? 'Update SMS Gateway Credentials' : 'Configure New SMS Gateway' }}</span>
                        </h2>
                        <button @click="showAddModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg">✕</button>
                    </div>

                    <form @submit.prevent="submitGateway" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">Driver Architecture *</label>
                            <select
                                v-model="form.driver"
                                required
                                class="w-full text-xs rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky"
                            >
                                <option value="alphasms">Alpha SMS (sms.net.bd) [Recommended]</option>
                                <option value="bulksmsdhaka">Bulk SMS Dhaka (bulksmsdhaka.com / mysoftit)</option>
                                <option value="bdbulksms">BDBulkSMS (bdbulksms.net / Greenweb)</option>
                                <option value="greenweb">Greenweb Driver</option>
                                <option value="generic_http">Generic HTTP / POST Aggregator</option>
                                <option value="bulksmsbd">BulkSMSBD Driver</option>
                                <option value="log">Local System Log File</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">Gateway Label Name *</label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                placeholder="e.g. Bulk SMS Dhaka"
                                class="w-full text-xs rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">API Endpoint URL</label>
                                <input
                                    type="url"
                                    v-model="form.api_url"
                                    placeholder="https://bulksmsdhaka.net/api"
                                    class="w-full text-xs rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky font-mono"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">Sender ID / Masking</label>
                                <input
                                    type="text"
                                    v-model="form.sender_id"
                                    placeholder="e.g. 1234 or Masking Name"
                                    class="w-full text-xs rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky font-mono"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">API Secret Key / Token *</label>
                            <input
                                type="password"
                                v-model="form.api_key"
                                placeholder="Enter API Key from provider portal"
                                class="w-full text-xs rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky font-mono"
                            />
                            <div v-if="form.driver === 'alphasms'" class="text-[11px] text-brand-sky mt-1.5">
                                Tip: You can get this key from Alpha SMS Dashboard (sms.net.bd) &gt; API &gt; API Key.
                            </div>
                            <div v-else-if="form.driver === 'bulksmsdhaka'" class="text-[11px] text-brand-sky mt-1.5">
                                Tip: Enter your Bulk SMS Dhaka API Key (bulksmsdhaka.com). Also supports live balance check!
                            </div>
                            <div v-else-if="form.driver === 'bdbulksms' || form.driver === 'greenweb'" class="text-[11px] text-emerald-400 mt-1.5">
                                Tip: Enter your API Token from sms.greenweb.com.bd / bdbulksms.net Developer Zone.
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 pt-1">
                            <input
                                type="checkbox"
                                id="gw_active"
                                v-model="form.is_active"
                                class="rounded bg-[#071322] border-brand-navy text-brand-sky focus:ring-brand-sky"
                            />
                            <label for="gw_active" class="text-xs font-semibold text-slate-300 cursor-pointer">
                                Set as Primary Active SMS Gateway
                            </label>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-brand-navy">
                            <div>
                                <button
                                    v-if="editingGateway"
                                    type="button"
                                    @click="const target = editingGateway; showAddModal = false; openDeleteConfirm(target);"
                                    class="px-3 py-2 bg-rose-950/40 hover:bg-rose-900/60 text-rose-400 hover:text-rose-300 border border-rose-900/50 text-xs font-bold rounded-xl transition flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete Gateway
                                </button>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="showAddModal = false"
                                    class="px-4 py-2 border border-brand-navy text-slate-300 text-xs font-bold rounded-xl hover:bg-brand-navy/60 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-5 py-2 bg-gradient-to-r from-brand-sky to-brand-blue text-white text-xs font-black rounded-xl shadow-md transition"
                                >
                                    {{ form.processing ? 'Saving...' : (editingGateway ? 'Save Changes' : 'Register Gateway') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Send Test SMS Modal -->
            <div v-if="showTestModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-[#091A2E] rounded-3xl max-w-md w-full p-6 shadow-2xl border border-brand-navy">
                    <div class="flex justify-between items-center pb-4 border-b border-brand-navy">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>Send Test SMS: {{ selectedGatewayForTest?.name }}</span>
                        </h2>
                        <button @click="showTestModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg">✕</button>
                    </div>

                    <form @submit.prevent="submitTestSms" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">Recipient Number *</label>
                            <input
                                type="text"
                                v-model="testForm.recipient"
                                required
                                placeholder="017XXXXXXXX or +88017XXXXXXXX"
                                class="w-full text-xs rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky font-mono"
                            />
                            <div v-if="testForm.errors.recipient" class="text-rose-400 text-[11px] mt-1">{{ testForm.errors.recipient }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">Test Message *</label>
                            <textarea
                                v-model="testForm.message"
                                rows="3"
                                required
                                class="w-full text-xs rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky"
                            ></textarea>
                            <div v-if="testForm.errors.message" class="text-rose-400 text-[11px] mt-1">{{ testForm.errors.message }}</div>
                            <div v-if="testForm.errors.test_error" class="text-rose-400 text-[11px] mt-1 p-2 rounded-lg bg-rose-950/30 border border-rose-500/30">
                                {{ testForm.errors.test_error }}
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-brand-navy">
                            <button
                                type="button"
                                @click="showTestModal = false"
                                class="px-4 py-2 border border-brand-navy text-slate-300 text-xs font-bold rounded-xl"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="testForm.processing"
                                class="px-5 py-2 bg-gradient-to-r from-amber-500 to-brand-orange text-white text-xs font-black rounded-xl shadow-md transition"
                            >
                                {{ testForm.processing ? 'Dispatching...' : 'Send Live SMS' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Professional Delete Confirmation Modal -->
            <ConfirmModal
                :show="showDeleteConfirm"
                :title="`Delete SMS Gateway: ${gatewayToDelete?.name || ''}`"
                :message="`Are you sure you want to delete this gateway (${gatewayToDelete?.name})? If this is currently the primary gateway, the system will automatically fall back to the next available gateway to ensure uninterrupted message delivery.`"
                confirm-text="Delete Gateway"
                cancel-text="Keep Gateway"
                type="danger"
                :processing="deletingGateway"
                @confirm="confirmDeleteGateway"
                @cancel="showDeleteConfirm = false; gatewayToDelete = null;"
            />
        </div>
    </AdminLayout>
</template>
