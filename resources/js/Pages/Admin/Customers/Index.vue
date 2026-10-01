<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomerRenewalModal from '@/Components/CustomerRenewalModal.vue';
import CustomerMoveModal from '@/Components/CustomerMoveModal.vue';
import SmsCharacterCounter from '@/Components/SmsCharacterCounter.vue';
import axios from 'axios';

const props = defineProps({
    customers: Object,
    filters: Object,
    areas: Array,
    packages: {
        type: Array,
        default: () => [],
    },
    filterCounts: Object,
    smsTemplates: Array,
});

const customerToRenew = ref(null);
const isRenewModalOpen = ref(false);

const openRenewModal = (customer) => {
    customerToRenew.value = customer;
    isRenewModalOpen.value = true;
};

// Customer Move Modal State
const isMoveModalOpen = ref(false);
const customerToMove = ref(null);
const isBulkMove = ref(false);

const openSingleMoveModal = (customer) => {
    customerToMove.value = customer;
    isBulkMove.value = false;
    isMoveModalOpen.value = true;
};

const openBulkMoveModal = (isAllFiltered = false) => {
    targetAllFiltered.value = isAllFiltered;
    customerToMove.value = null;
    isBulkMove.value = true;
    isMoveModalOpen.value = true;
};

const onMoveSuccess = () => {
    selectedCustomerIds.value = [];
    selectAllCurrentPage.value = false;
    targetAllFiltered.value = false;
    router.reload({ only: ['customers', 'filterCounts'] });
};

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const areaId = ref(props.filters.area_id || '');
const advancedFilter = ref(props.filters.advanced_filter || '');

watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        search.value = newFilters.search || '';
        status.value = newFilters.status || '';
        areaId.value = newFilters.area_id || '';
        advancedFilter.value = newFilters.advanced_filter || '';
    }
}, { deep: true });

const applyFilters = () => {
    router.get(route('admin.customers.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        area_id: areaId.value || undefined,
        advanced_filter: advancedFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const setAdvancedFilter = (key) => {
    advancedFilter.value = advancedFilter.value === key ? '' : key;
    applyFilters();
};

watch([status, areaId], () => {
    applyFilters();
});

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

// Selection State for Bulk Action
const selectedCustomerIds = ref([]);
const selectAllCurrentPage = ref(false);
const targetAllFiltered = ref(false);

const toggleSelectAll = () => {
    if (selectAllCurrentPage.value) {
        selectedCustomerIds.value = props.customers.data.map(c => c.id);
    } else {
        selectedCustomerIds.value = [];
        targetAllFiltered.value = false;
    }
};

watch(() => props.customers.data, () => {
    selectedCustomerIds.value = [];
    selectAllCurrentPage.value = false;
    targetAllFiltered.value = false;
});

watch(selectedCustomerIds, (newVal) => {
    if (props.customers.data && props.customers.data.length > 0) {
        selectAllCurrentPage.value = newVal.length === props.customers.data.length;
    } else {
        selectAllCurrentPage.value = false;
    }
});

// Bulk SMS Modal State
const isSmsModalOpen = ref(false);
const selectedTemplateId = ref('');
const bulkSmsForm = useForm({
    message: '',
    customer_ids: [],
    target_all_filtered: false,
    search: '',
    status: '',
    area_id: '',
    advanced_filter: '',
});

const openBulkSmsModal = (targetEntireFilter = false) => {
    targetAllFiltered.value = targetEntireFilter;
    bulkSmsForm.reset();
    bulkSmsForm.clearErrors();
    bulkSmsForm.target_all_filtered = targetEntireFilter;
    bulkSmsForm.customer_ids = targetEntireFilter ? [] : selectedCustomerIds.value;
    bulkSmsForm.search = search.value;
    bulkSmsForm.status = status.value;
    bulkSmsForm.area_id = areaId.value;
    bulkSmsForm.advanced_filter = advancedFilter.value;
    selectedTemplateId.value = '';
    isSmsModalOpen.value = true;
};

// Quick Bengali Preset Templates for ISP
const banglaPresets = [
    {
        id: 'preset_expiry_3d',
        name: '⏰ মেয়াদ শেষের সতর্কতা',
        message: 'প্রিয় {name}, আপনার ইন্টারনেট সংযোগের মেয়াদ {expiry_date} তারিখে শেষ হবে। সংযোগ সচল রাখতে দ্রুত বিল পরিশোধ করুন। - পীরগাছা ইন্টারনেট',
    },
    {
        id: 'preset_due_reminder',
        name: '💰 বকেয়া বিল রিমাইন্ডার',
        message: 'সুধী {name}, আপনার চলতি বিল {due_amount} টাকা বকেয়া রয়েছে। অনুগ্রহ করে দ্রুত পরিশোধ করুন। ধন্যবাদ - পীরগাছা ইন্টারনেট',
    },
    {
        id: 'preset_emergency_notice',
        name: '⚡ জরুরি নোটিশ/মেইনটেন্যান্স',
        message: 'সম্মানিত গ্রাহক, ব্যাকবোন অপটিক্যাল ফাইবার মেরামতের কারণে সাময়িক ইন্টারনেট বিঘ্ন হতে পারে। দ্রুত সমাধানের চেষ্টা চলছে। - পীরগাছা ইন্টারনেট',
    },
    {
        id: 'preset_zero_charge',
        name: '🎁 রিনিউ কনফার্মেশন',
        message: 'প্রিয় {name}, আপনার {package} প্যাকেজের সংযোগ {expiry_date} পর্যন্ত নবায়ন করা হয়েছে। - পীরগাছা ইন্টারনেট',
    },
    {
        id: 'preset_expired',
        name: '🚫 লাইন বন্ধের নোটিশ',
        message: 'প্রিয় {name}, আপনার ইন্টারনেটের মেয়াদ শেষ হওয়ায় লাইন স্থগিত করা হয়েছে। বিল পরিশোধ করে পুনরায় সচল করুন। - পীরগাছা ইন্টারনেট',
    },
    {
        id: 'preset_pppoe_credentials',
        name: '🔑 PPPoE আইডি, পাসওয়ার্ড ও পোর্টাল লিংক',
        message: 'প্রিয় {name}, আপনার পীরগাছা ইন্টারনেট কানেকশন প্রস্তুত। PPPoE আইডি: {pppoe_username}, পাসওয়ার্ড: {pppoe_password}। পোর্টাল: {login_url}',
    },
];

const applyTemplate = () => {
    if (!selectedTemplateId.value) return;

    // Check Bangla presets first
    const preset = banglaPresets.find(p => p.id === selectedTemplateId.value);
    if (preset) {
        bulkSmsForm.message = preset.message;
        return;
    }

    // Otherwise check database templates
    const tmpl = props.smsTemplates?.find(t => t.id === Number(selectedTemplateId.value));
    if (tmpl) {
        bulkSmsForm.message = tmpl.template;
    }
};

const insertTag = (tag) => {
    bulkSmsForm.message += tag;
};

const submitBulkSms = () => {
    bulkSmsForm.post(route('admin.sms.bulk-send'), {
        onSuccess: () => {
            isSmsModalOpen.value = false;
            selectedCustomerIds.value = [];
            selectAllCurrentPage.value = false;
            targetAllFiltered.value = false;
        }
    });
};

// PPPoE Password Reveal state map (keyed by credential ID)
const revealedPasswords = ref({});
const revealingIds = ref({});
const copiedId = ref(null);

const toggleRevealPassword = async (credentialId) => {
    if (!credentialId) return;

    if (revealedPasswords.value[credentialId]) {
        delete revealedPasswords.value[credentialId];
        return;
    }

    revealingIds.value[credentialId] = true;
    try {
        const res = await axios.post(route('admin.pppoe.reveal-password', credentialId));
        revealedPasswords.value[credentialId] = res.data.password;
    } catch (err) {
        alert('Unauthorized or unable to decrypt PPPoE password.');
    } finally {
        delete revealingIds.value[credentialId];
    }
};

const copyPassword = (credentialId) => {
    const pwd = revealedPasswords.value[credentialId];
    if (!pwd) return;
    navigator.clipboard.writeText(pwd);
    copiedId.value = credentialId;
    setTimeout(() => {
        if (copiedId.value === credentialId) {
            copiedId.value = null;
        }
    }, 2000);
};

// Customer Delete Modal State
const customerToDelete = ref(null);
const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const confirmDeleteCustomer = (customer) => {
    customerToDelete.value = customer;
    isDeleteModalOpen.value = true;
};

const executeCustomerDelete = () => {
    if (!customerToDelete.value) return;
    deleteForm.delete(route('admin.customers.destroy', customerToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            customerToDelete.value = null;
        },
    });
};
</script>


<template>
    <Head title="Customer Directory - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Customer Management</h1>
                <p class="text-sm text-slate-400 mt-1">Manage active subscribers, fiber connections, and PPPoE credentials.</p>
            </div>
            <Link
                :href="route('admin.customers.create')"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-orange via-brand-amber to-brand-gold hover:opacity-95 px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-brand-orange/30 transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Customer
            </Link>
        </div>

        <!-- Advanced Operation Category Filter Tabs -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 mb-5">
            <button
                type="button"
                @click="setAdvancedFilter('')"
                :class="[
                    'p-3 rounded-2xl border text-left transition-all relative overflow-hidden',
                    !advancedFilter
                        ? 'bg-brand-sky/20 border-brand-sky text-white shadow-lg shadow-brand-sky/20'
                        : 'bg-[#091A2E]/80 border-brand-navy/60 text-slate-400 hover:text-white hover:bg-[#0B1E36]'
                ]"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider">সকল গ্রাহক</div>
                <div class="text-xl font-black font-mono text-white mt-0.5">{{ filterCounts?.all || customers.total }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">টোটাল ডিরেক্টরি</div>
            </button>

            <button
                type="button"
                @click="setAdvancedFilter('paid_this_month')"
                :class="[
                    'p-3 rounded-2xl border text-left transition-all relative overflow-hidden',
                    advancedFilter === 'paid_this_month'
                        ? 'bg-emerald-500/20 border-emerald-400 text-white shadow-lg shadow-emerald-500/25'
                        : 'bg-[#091A2E]/80 border-brand-navy/60 text-slate-400 hover:text-slate-200 hover:bg-[#0B1E36]'
                ]"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 flex items-center justify-between">
                    <span>চলতি বিল পরিশোধিত</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                </div>
                <div class="text-xl font-black font-mono text-emerald-400 mt-0.5">{{ filterCounts?.paid_this_month || 0 }}</div>
                <div class="text-[10px] text-emerald-300/80 mt-0.5">বকেয়ামুক্ত বিল পে</div>
            </button>

            <button
                type="button"
                @click="setAdvancedFilter('expiring_3d')"
                :class="[
                    'p-3 rounded-2xl border text-left transition-all relative overflow-hidden',
                    advancedFilter === 'expiring_3d'
                        ? 'bg-amber-500/20 border-amber-400 text-white shadow-lg shadow-amber-500/25'
                        : 'bg-[#091A2E]/80 border-brand-navy/60 text-slate-400 hover:text-slate-200 hover:bg-[#0B1E36]'
                ]"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-amber-400 flex items-center justify-between">
                    <span>৩ দিনে মেয়াদ শেষ</span>
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                </div>
                <div class="text-xl font-black font-mono text-amber-400 mt-0.5">{{ filterCounts?.expiring_3d || 0 }}</div>
                <div class="text-[10px] text-amber-300/80 mt-0.5">আসন্ন মেয়াদ শেষ</div>
            </button>

            <button
                type="button"
                @click="setAdvancedFilter('zero_charge_renewed')"
                :class="[
                    'p-3 rounded-2xl border text-left transition-all relative overflow-hidden',
                    advancedFilter === 'zero_charge_renewed'
                        ? 'bg-purple-500/20 border-purple-400 text-white shadow-lg shadow-purple-500/20'
                        : 'bg-[#091A2E]/80 border-brand-navy/60 text-slate-400 hover:text-slate-200 hover:bg-[#0B1E36]'
                ]"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-purple-400 flex items-center justify-between">
                    <span>টাকা ছাড়া রিনিউ</span>
                    <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                </div>
                <div class="text-xl font-black font-mono text-purple-400 mt-0.5">{{ filterCounts?.zero_charge_renewed || 0 }}</div>
                <div class="text-[10px] text-purple-300/80 mt-0.5">এডভান্স গ্রেস প্রাপ্ত</div>
            </button>

            <button
                type="button"
                @click="setAdvancedFilter('due')"
                :class="[
                    'p-3 rounded-2xl border text-left transition-all relative overflow-hidden',
                    advancedFilter === 'due'
                        ? 'bg-rose-500/20 border-rose-500 text-white shadow-lg shadow-rose-500/20'
                        : 'bg-[#091A2E]/80 border-brand-navy/60 text-slate-400 hover:text-slate-200 hover:bg-[#0B1E36]'
                ]"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-rose-400 flex items-center justify-between">
                    <span>বকেয়া রয়েছে</span>
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                </div>
                <div class="text-xl font-black font-mono text-rose-400 mt-0.5">{{ filterCounts?.due || 0 }}</div>
                <div class="text-[10px] text-rose-300/80 mt-0.5">টাকা বাকি গ্রাহক</div>
            </button>

            <button
                type="button"
                @click="setAdvancedFilter('expired')"
                :class="[
                    'p-3 rounded-2xl border text-left transition-all relative overflow-hidden',
                    advancedFilter === 'expired'
                        ? 'bg-rose-950/40 border-rose-600 text-white shadow-lg'
                        : 'bg-[#091A2E]/80 border-brand-navy/60 text-slate-400 hover:text-slate-200 hover:bg-[#0B1E36]'
                ]"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">মেয়াদোত্তীর্ণ</div>
                <div class="text-xl font-black font-mono text-slate-300 mt-0.5">{{ filterCounts?.expired || 0 }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">সংযোগ বিচ্ছিন্নযোগ্য</div>
            </button>
        </div>

        <!-- Filter Controls & Search Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-5">
            <div class="relative sm:col-span-2">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search Code, Name, Phone, PPPoE, IP..."
                    class="w-full rounded-xl border border-brand-navy bg-[#091A2E]/80 py-2.5 pl-10 pr-4 text-xs text-white placeholder-slate-400 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky transition"
                />
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <select
                v-model="status"
                class="rounded-xl border border-brand-navy bg-[#091A2E]/80 py-2.5 px-3 text-xs text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky transition"
            >
                <option value="" class="bg-[#071322] text-slate-400">All Statuses</option>
                <option value="active" class="bg-[#071322] text-white">Active</option>
                <option value="expired" class="bg-[#071322] text-white">Expired</option>
                <option value="suspended" class="bg-[#071322] text-white">Suspended</option>
                <option value="disconnected" class="bg-[#071322] text-white">Disconnected</option>
            </select>

            <select
                v-model="areaId"
                class="rounded-xl border border-brand-navy bg-[#091A2E]/80 py-2.5 px-3 text-xs text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky transition"
            >
                <option value="" class="bg-[#071322] text-slate-400">All Areas</option>
                <option v-for="area in areas" :key="area.id" :value="area.id" class="bg-[#071322] text-white">
                    {{ area.name }} ({{ area.code }})
                </option>
            </select>
        </div>

        <!-- Modern Floating Bulk Actions Toolbar (Sleek SaaS Ribbon) -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform -translate-y-2 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform -translate-y-2 opacity-0"
        >
            <div
                v-if="selectedCustomerIds.length > 0 || advancedFilter !== ''"
                class="mb-4 flex flex-wrap items-center justify-between gap-4 px-4 py-3 rounded-2xl border border-brand-sky/30 bg-[#0A1B2E]/90 backdrop-blur-xl shadow-lg shadow-black/25"
            >
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-brand-sky/15 text-brand-sky border border-brand-sky/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-300 font-medium">নির্বাচিত গ্রাহক:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black font-mono bg-brand-sky/20 text-brand-sky border border-brand-sky/30 shadow-sm">
                            {{ selectedCustomerIds.length }} / {{ customers.data.length }}
                        </span>
                        <span v-if="selectedCustomerIds.length === customers.data.length" class="text-[11px] font-semibold text-emerald-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            বর্তমান পেজের সবাই নির্বাচিত
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- Bulk Move Button -->
                    <button
                        v-if="targetAllFiltered || selectedCustomerIds.length > 0"
                        type="button"
                        @click="openBulkMoveModal(targetAllFiltered)"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-purple-600 via-indigo-600 to-cyan-600 text-white shadow-md shadow-purple-600/25 hover:shadow-purple-600/40 hover:opacity-95 cursor-pointer transition transform active:scale-95"
                    >
                        <span>🔀</span>
                        <span>{{ targetAllFiltered ? `ফিল্টারকৃত সকল (${customers.total}) জনকে মুভ করুন` : `নির্বাচিত (${selectedCustomerIds.length}) জনকে মুভ করুন` }}</span>
                    </button>

                    <!-- Specific customers selected or all filtered -->
                    <button
                        v-if="targetAllFiltered || (selectedCustomerIds.length > 0 && selectedCustomerIds.length !== customers.total)"
                        type="button"
                        @click="openBulkSmsModal(targetAllFiltered)"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-brand-sky via-cyan-500 to-brand-blue text-white shadow-md shadow-brand-sky/25 hover:shadow-brand-sky/40 hover:opacity-95 cursor-pointer transition transform active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        {{ targetAllFiltered ? `ফিল্টারকৃত সকল (${customers.total}) জনকে এসএমএস পাঠান` : `নির্বাচিত (${selectedCustomerIds.length}) জনকে এসএমএস পাঠান` }}
                    </button>

                    <!-- Send to all filtered / all total customers -->
                    <button
                        v-if="customers.total > 0 && selectedCustomerIds.length === 0 && !targetAllFiltered"
                        type="button"
                        @click="openBulkSmsModal(true)"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-brand-orange via-amber-500 to-brand-gold text-white shadow-md shadow-brand-orange/25 hover:shadow-brand-orange/40 hover:opacity-95 cursor-pointer transition transform active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        ফিল্টারকৃত সকলকে ({{ customers.total }}) এমারজেন্সি নোটিশ
                    </button>
                </div>
            </div>
        </transition>

        <!-- Customer Data Table -->
        <div class="overflow-hidden rounded-2xl border border-brand-navy/60 bg-[#091A2E]/80 shadow-xl shadow-black/20 backdrop-blur-sm">
            <!-- Multi-page Select All Notification Banner (Gmail Style) -->
            <div
                v-if="selectAllCurrentPage && customers.total > customers.data.length"
                class="bg-brand-sky/10 border-b border-brand-sky/20 px-4 py-2.5 text-center text-xs text-slate-300 flex items-center justify-center gap-2 flex-wrap"
            >
                <span v-if="!targetAllFiltered">
                    বর্তমান পেজের <strong>{{ customers.data.length }}</strong> জন গ্রাহক সিলেক্ট করা হয়েছে।
                </span>
                <span v-else class="text-brand-sky font-semibold">
                    🎉 সকল পেজের সর্বমোট <strong>{{ customers.total }}</strong> জন ফিল্টারকৃত গ্রাহক সিলেক্ট করা হয়েছে!
                </span>

                <button
                    v-if="!targetAllFiltered"
                    @click="targetAllFiltered = true"
                    class="text-brand-sky hover:text-sky-300 font-bold underline hover:no-underline ml-1"
                >
                    সবগুলো পেজের মোট {{ customers.total }} জন গ্রাহককেই সিলেক্ট করুন
                </button>
                <button
                    v-else
                    @click="targetAllFiltered = false"
                    class="text-amber-400 hover:text-amber-300 font-bold underline hover:no-underline ml-1"
                >
                    শুধুমাত্র বর্তমান পেজ ({{ customers.data.length }}) সিলেক্টে ফিরে যান
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-brand-navy bg-[#071322]/80 uppercase font-semibold text-slate-400 whitespace-nowrap">
                        <tr>
                            <th class="px-4 py-3.5 text-center w-10">
                                <label class="inline-flex items-center justify-center cursor-pointer select-none" title="পেজের সকলকে এক ক্লিকে সিলেক্ট করুন">
                                    <input
                                        type="checkbox"
                                        v-model="selectAllCurrentPage"
                                        @change="toggleSelectAll"
                                        class="rounded bg-[#071322] border-brand-navy text-brand-sky focus:ring-brand-sky h-4 w-4 cursor-pointer"
                                    />
                                </label>
                            </th>
                            <th class="px-3 py-3.5 text-center w-12">#SL</th>
                            <th class="px-5 py-3.5">Customer</th>
                            <th class="px-5 py-3.5">Contact</th>
                            <th class="px-5 py-3.5">Area</th>
                            <th class="px-5 py-3.5">Package & Expiry</th>
                            <th class="px-5 py-3.5">PPPoE Username</th>
                            <th class="px-5 py-3.5">PPPoE Password</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-navy/60 whitespace-nowrap">
                        <tr v-for="(customer, index) in customers.data" :key="customer.id" class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3.5 text-center">
                                <input
                                    type="checkbox"
                                    :value="customer.id"
                                    v-model="selectedCustomerIds"
                                    class="rounded bg-[#071322] border-brand-navy text-brand-sky focus:ring-brand-sky h-4 w-4 cursor-pointer"
                                />
                            </td>
                            <td class="px-3 py-3.5 text-center font-mono font-bold text-slate-400 text-xs">
                                {{ ((customers.from || 1) + index) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <Link :href="route('admin.customers.show', customer.id)" class="font-bold text-white hover:text-brand-sky transition">
                                    {{ customer.name }}
                                </Link>
                                <div class="text-[11px] text-slate-400 font-mono">{{ customer.customer_code }}</div>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-300">
                                {{ customer.primary_contact?.phone || 'N/A' }}
                            </td>
                            <td class="px-5 py-3.5 text-slate-300">
                                {{ customer.area?.name || 'Unassigned' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="font-semibold text-emerald-400 block">
                                    {{ customer.connections[0]?.current_package?.name || 'None' }}
                                </span>
                                <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                    <span v-if="customer.connections[0]?.expiry_date" class="text-[10px] font-mono text-amber-300/90 flex items-center gap-1">
                                        📅 {{ customer.connections[0].expiry_date }}
                                    </span>
                                    <span v-if="customer.has_active_zero_charge" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30 animate-pulse" title="টাকা ছাড়া গ্রেস রিনিউ সচল">
                                        🎁 গ্রেস (+{{ customer.zero_charge_days || 0 }}d)
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-mono font-semibold text-sky-400">
                                {{ customer.connections[0]?.pppoe_credential?.username || '—' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <template v-if="customer.connections[0]?.pppoe_credential">
                                    <div class="flex items-center gap-1.5 font-mono">
                                        <span v-if="revealedPasswords[customer.connections[0].pppoe_credential.id]" class="font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                                            {{ revealedPasswords[customer.connections[0].pppoe_credential.id] }}
                                        </span>
                                        <span v-else class="text-slate-400 tracking-widest text-[11px]">
                                            ••••••••
                                        </span>

                                        <!-- Reveal / Hide Button -->
                                        <button
                                            type="button"
                                            @click="toggleRevealPassword(customer.connections[0].pppoe_credential.id)"
                                            :disabled="revealingIds[customer.connections[0].pppoe_credential.id]"
                                            :title="revealedPasswords[customer.connections[0].pppoe_credential.id] ? 'Hide Password' : 'Show Password (Audited)'"
                                            class="p-1 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition"
                                        >
                                            <svg v-if="revealingIds[customer.connections[0].pppoe_credential.id]" class="h-3.5 w-3.5 animate-spin text-brand-sky" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else-if="revealedPasswords[customer.connections[0].pppoe_credential.id]" class="h-3.5 w-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                            </svg>
                                            <svg v-else class="h-3.5 w-3.5 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Copy button when revealed -->
                                        <button
                                            v-if="revealedPasswords[customer.connections[0].pppoe_credential.id]"
                                            type="button"
                                            @click="copyPassword(customer.connections[0].pppoe_credential.id)"
                                            title="Copy Password"
                                            class="p-1 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-emerald-400 transition"
                                        >
                                            <svg v-if="copiedId === customer.connections[0].pppoe_credential.id" class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <span v-else class="text-slate-400 font-mono">—</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span 
                                    :class="[
                                        customer.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                        'inline-flex items-center rounded-lg border px-2 py-0.5 text-[11px] font-semibold uppercase'
                                    ]"
                                >
                                    {{ customer.status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openSingleMoveModal(customer)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-cyan-500/40 bg-cyan-500/15 hover:bg-cyan-500/30 px-2.5 py-1.5 text-xs font-bold text-cyan-300 hover:text-white transition shadow-sm cursor-pointer"
                                        title="ক্যাটাগরি বা ফিল্টার পরিবর্তন করুন"
                                    >
                                        🔀 মুভ
                                    </button>
                                    <button
                                        type="button"
                                        @click="openRenewModal(customer)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-purple-500/40 bg-purple-500/15 hover:bg-purple-500/30 px-2.5 py-1.5 text-xs font-bold text-purple-300 hover:text-white transition shadow-sm cursor-pointer"
                                        title="সংযোগ রিনিউ বা টাকা ছাড়া গ্রেস প্রদান"
                                    >
                                        ⚡ রিনিউ
                                    </button>
                                    <Link
                                        :href="route('admin.customers.show', customer.id)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-brand-navy bg-[#071322] hover:bg-brand-navy px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:text-white transition"
                                        title="প্রোফাইল দেখুন"
                                    >
                                        View Profile
                                    </Link>
                                    <button
                                        type="button"
                                        @click="confirmDeleteCustomer(customer)"
                                        class="inline-flex items-center justify-center p-1.5 rounded-lg border border-rose-500/20 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 transition"
                                        title="গ্রাহক মুছে ফেলুন (Delete)"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="10" class="px-5 py-8 text-center text-slate-400">
                                No customers found matching your criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="customers.links && customers.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-brand-navy px-5 py-3.5 text-xs text-slate-400 bg-[#071322]/40">
                <div class="text-center sm:text-left">Showing {{ customers.from }} to {{ customers.to }} of {{ customers.total }} customers</div>
                <div class="flex items-center gap-1 flex-wrap justify-center">
                    <Link
                        v-for="(link, i) in customers.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            link.active ? 'bg-brand-blue text-white font-bold' : 'text-slate-400 hover:bg-slate-800',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                            'rounded-lg px-3 py-1.5 transition'
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- BULK SMS CAMPAIGN MODAL -->
        <div v-if="isSmsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="isSmsModalOpen = false" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-xl max-h-[92vh] overflow-y-auto rounded-3xl border border-brand-navy bg-[#091A2E] p-5 sm:p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-brand-navy pb-3">
                    <div>
                        <h2 class="text-lg font-black text-white flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-brand-sky/20 text-brand-sky">✉️</span>
                            বাল্ক এসএমএস ও এমারজেন্সি নোটিশ
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">
                            <span v-if="targetAllFiltered" class="text-amber-400 font-bold">
                                সম্পূর্ণ ফিল্টারকৃত মোট {{ customers.total }} জন গ্রাহককে মেসেজ পাঠানো হবে।
                            </span>
                            <span v-else class="text-brand-sky font-bold">
                                নির্বাচিত {{ bulkSmsForm.customer_ids.length }} জন গ্রাহককে মেসেজ পাঠানো হবে।
                            </span>
                        </p>
                    </div>
                    <button @click="isSmsModalOpen = false" class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">✕</button>
                </div>

                <form @submit.prevent="submitBulkSms" class="space-y-4">
                    <!-- Template Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            এসএমএস টেমপ্লেট লোড করুন (ঐচ্ছিক)
                        </label>
                        <select
                            v-model="selectedTemplateId"
                            @change="applyTemplate"
                            class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky cursor-pointer"
                        >
                            <option value="">-- টেমপ্লেট নির্বাচন করুন --</option>
                            <optgroup label="⚡ জনপ্রিয় বাংলা টেমপ্লেট" class="bg-[#091A2E] text-brand-sky font-semibold">
                                <option v-for="bp in banglaPresets" :key="bp.id" :value="bp.id" class="bg-[#071322] text-white">
                                    {{ bp.name }}
                                </option>
                            </optgroup>
                            <optgroup v-if="smsTemplates?.length" label="📁 অন্যান্য সিস্টেম টেমপ্লেট" class="bg-[#091A2E] text-slate-400 font-semibold">
                                <option v-for="t in smsTemplates" :key="t.id" :value="t.id" class="bg-[#071322] text-slate-300">
                                    {{ t.name }}
                                </option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Dynamic Insert Tags -->
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">ডায়নামিক ভ্যারিয়েবল যুক্ত করুন:</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                @click="insertTag('{name}')"
                                class="px-2.5 py-1 rounded-lg bg-[#071322] border border-brand-navy text-xs font-mono text-brand-sky hover:border-brand-sky hover:text-white transition"
                            >
                                + {name}
                            </button>
                            <button
                                type="button"
                                @click="insertTag('{expiry_date}')"
                                class="px-2.5 py-1 rounded-lg bg-[#071322] border border-brand-navy text-xs font-mono text-amber-400 hover:border-amber-400 hover:text-white transition"
                            >
                                + {expiry_date}
                            </button>
                            <button
                                type="button"
                                @click="insertTag('{package}')"
                                class="px-2.5 py-1 rounded-lg bg-[#071322] border border-brand-navy text-xs font-mono text-emerald-400 hover:border-emerald-400 hover:text-white transition"
                            >
                                + {package}
                            </button>
                            <button
                                type="button"
                                @click="insertTag('{code}')"
                                class="px-2.5 py-1 rounded-lg bg-[#071322] border border-brand-navy text-xs font-mono text-purple-400 hover:border-purple-400 hover:text-white transition"
                            >
                                + {code}
                            </button>
                            <button
                                type="button"
                                @click="insertTag('{due_amount}')"
                                class="px-2.5 py-1 rounded-lg bg-[#071322] border border-brand-navy text-xs font-mono text-rose-400 hover:border-rose-400 hover:text-white transition"
                            >
                                + {due_amount}
                            </button>
                            <button
                                type="button"
                                @click="insertTag('{pppoe_username}')"
                                class="px-2.5 py-1 rounded-lg bg-[#071322] border border-violet-500/40 text-xs font-mono text-violet-400 hover:border-violet-400 hover:text-white transition"
                            >
                                + {pppoe_username}
                            </button>
                            <button
                                type="button"
                                @click="insertTag('{pppoe_password}')"
                                class="px-2.5 py-1 rounded-lg bg-[#071322] border border-violet-500/40 text-xs font-mono text-violet-400 hover:border-violet-400 hover:text-white transition"
                            >
                                + {pppoe_password}
                            </button>
                            <button
                                type="button"
                                @click="insertTag('{login_url}')"
                                class="px-2.5 py-1 rounded-lg bg-[#071322] border border-emerald-500/40 text-xs font-mono text-emerald-400 hover:border-emerald-400 hover:text-white transition"
                            >
                                + {login_url}
                            </button>
                        </div>
                    </div>

                    <!-- Message Body -->
                    <div>
                        <label class="text-xs font-semibold text-slate-300 uppercase tracking-wider block mb-1.5">মেসেজের বিবরণ *</label>
                        <textarea
                            v-model="bulkSmsForm.message"
                            required
                            rows="4"
                            placeholder="প্রিয় {name}, আপনার ইন্টারনেট বিল বকেয়া আছে..."
                            class="w-full rounded-2xl border border-brand-navy bg-[#071322] p-3.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky leading-relaxed"
                        ></textarea>
                        <div v-if="bulkSmsForm.errors.message" class="text-rose-400 text-xs mt-1">{{ bulkSmsForm.errors.message }}</div>
                        <SmsCharacterCounter :text="bulkSmsForm.message" />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-navy">
                        <button
                            type="button"
                            @click="isSmsModalOpen = false"
                            class="rounded-xl border border-brand-navy bg-transparent px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white transition"
                        >
                            বাতিল
                        </button>
                        <button
                            type="submit"
                            :disabled="bulkSmsForm.processing || !bulkSmsForm.message"
                            class="rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue hover:opacity-95 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-brand-sky/25 transition disabled:opacity-50 flex items-center gap-2"
                        >
                            <svg v-if="bulkSmsForm.processing" class="w-4 h-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" /></svg>
                            <span>{{ bulkSmsForm.processing ? 'মেসেজ পাঠানো হচ্ছে...' : 'এসএমএস পাঠান ✓' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CUSTOMER DELETE CONFIRMATION MODAL -->
        <div v-if="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="isDeleteModalOpen = false" class="fixed inset-0 bg-black/80 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-md rounded-3xl border border-rose-500/30 bg-[#091A2E] p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-400 border border-rose-500/30">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">গ্রাহক মুছে ফেলার নিশ্চিতকরণ</h3>
                        <p class="text-xs text-slate-400">এই কাজটি অপরিবর্তনীয় (Irreversible action)</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-navy bg-[#071322] p-4 text-xs text-slate-300 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-400">নাম:</span>
                        <span class="font-bold text-white">{{ customerToDelete?.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">কাস্টমার আইডি:</span>
                        <span class="font-mono text-brand-sky">{{ customerToDelete?.customer_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">ফোন:</span>
                        <span class="font-mono text-slate-200">{{ customerToDelete?.primary_contact?.phone || 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">এলাকা:</span>
                        <span class="text-slate-200">{{ customerToDelete?.area?.name || 'Unassigned' }}</span>
                    </div>
                </div>

                <p class="text-xs text-rose-300/90 leading-relaxed bg-rose-500/10 p-3 rounded-xl border border-rose-500/20">
                    ⚠️ সতর্কবার্তা: গ্রাহক ডিলিট করলে এর সাথে যুক্ত সংযোগ (Connection), PPPoE ক্রেডেনশিয়াল এবং সংশ্লিষ্ট তথ্য ডাটাবেজ থেকে মুছে যাবে।
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="isDeleteModalOpen = false"
                        :disabled="deleteForm.processing"
                        class="rounded-xl border border-brand-navy bg-slate-800/80 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white transition cursor-pointer"
                    >
                        বাতিল করুন
                    </button>
                    <button
                        type="button"
                        @click="executeCustomerDelete"
                        :disabled="deleteForm.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 px-5 py-2 text-xs font-bold text-white shadow-lg shadow-rose-600/30 transition disabled:opacity-50 cursor-pointer"
                    >
                        <svg v-if="deleteForm.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ deleteForm.processing ? 'ডিলিট হচ্ছে...' : 'হ্যাঁ, ডিলিট করুন' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Reusable Connection Renewal Modal -->
        <CustomerRenewalModal
            :is-open="isRenewModalOpen"
            :customer="customerToRenew"
            :packages="packages"
            @close="isRenewModalOpen = false; customerToRenew = null"
            @success="router.reload({ only: ['customers', 'filterCounts'] })"
        />

        <!-- Reusable Customer Move Modal -->
        <CustomerMoveModal
            :is-open="isMoveModalOpen"
            :customer="customerToMove"
            :is-bulk="isBulkMove"
            :customer-ids="selectedCustomerIds"
            :target-all-filtered="targetAllFiltered"
            :total-filtered-count="customers.total"
            :filter-params="{ search, status, area_id: areaId, advanced_filter: advancedFilter }"
            :packages="packages"
            @close="isMoveModalOpen = false; customerToMove = null; isBulkMove = false"
            @success="onMoveSuccess"
        />
    </AdminLayout>
</template>
