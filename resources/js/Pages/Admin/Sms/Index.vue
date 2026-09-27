<script setup>
import { ref, watch, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    logs: Object,
    templates: Array,
    gateways: Array,
    metrics: Object,
    filters: Object,
});

const showSendModal = ref(false);
const filterStatus = ref(props.filters?.status || '');
const filterSearch = ref(props.filters?.search || '');
const filterGateway = ref(props.filters?.gateway_id || '');

const form = useForm({
    recipient: '',
    message: '',
    customer_id: null,
});

const selectedTemplate = ref('');

const onTemplateSelect = () => {
    if (!selectedTemplate.value) return;
    const tmpl = props.templates.find(t => t.id === Number(selectedTemplate.value));
    if (tmpl) {
        form.message = tmpl.template;
    }
};

let searchDebounceTimeout = null;

const applyFilters = () => {
    router.get(route('admin.sms.index'), {
        status: filterStatus.value || undefined,
        search: filterSearch.value || undefined,
        gateway_id: filterGateway.value || undefined,
    }, { preserveState: true, replace: true });
};

watch(filterSearch, () => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const resetFilters = () => {
    filterStatus.value = '';
    filterSearch.value = '';
    filterGateway.value = '';
    applyFilters();
};

const submitManualSms = () => {
    form.post(route('admin.sms.send'), {
        onSuccess: () => {
            showSendModal.value = false;
            form.reset();
            selectedTemplate.value = '';
        }
    });
};

const retrySms = (logId) => {
    router.post(route('admin.sms.retry', logId));
};

// Character count and SMS segment calculation
const smsSegments = computed(() => {
    const len = form.message.length;
    if (len === 0) return 0;
    // Check if contains non-GSM characters (like Bengali unicode)
    const isUnicode = /[^\u0000-\u007F]/.test(form.message);
    if (isUnicode) {
        return len <= 70 ? 1 : Math.ceil(len / 67);
    } else {
        return len <= 160 ? 1 : Math.ceil(len / 153);
    }
});

// Selection & Bulk Deletion State
const selectedLogIds = ref([]);
const selectAllCurrentPage = ref(false);
const targetAllFiltered = ref(false);
const isDeleteModalOpen = ref(false);
const logToDelete = ref(null); // null if bulk
const deleteForm = useForm({});

const toggleSelectAll = () => {
    if (selectAllCurrentPage.value) {
        selectedLogIds.value = props.logs.data ? props.logs.data.map(l => l.id) : [];
    } else {
        selectedLogIds.value = [];
        targetAllFiltered.value = false;
    }
};

watch(() => props.logs.data, () => {
    selectedLogIds.value = [];
    selectAllCurrentPage.value = false;
    targetAllFiltered.value = false;
});

watch(selectedLogIds, (newVal) => {
    if (props.logs.data && props.logs.data.length > 0) {
        selectAllCurrentPage.value = newVal.length === props.logs.data.length;
    } else {
        selectAllCurrentPage.value = false;
    }
});

const openBulkDeleteModal = (targetEntireFilter = false) => {
    targetAllFiltered.value = targetEntireFilter;
    logToDelete.value = null;
    isDeleteModalOpen.value = true;
};

const confirmDeleteSingle = (log) => {
    logToDelete.value = log;
    targetAllFiltered.value = false;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (logToDelete.value) {
        deleteForm.delete(route('admin.sms.logs.destroy', logToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                logToDelete.value = null;
            }
        });
    } else {
        deleteForm.transform(() => ({
            log_ids: targetAllFiltered.value ? [] : selectedLogIds.value,
            target_all_filtered: targetAllFiltered.value,
            status: filterStatus.value || undefined,
            search: filterSearch.value || undefined,
            gateway_id: filterGateway.value || undefined,
        })).delete(route('admin.sms.logs.bulk-destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                selectedLogIds.value = [];
                selectAllCurrentPage.value = false;
                targetAllFiltered.value = false;
            }
        });
    }
};
</script>

<template>
    <Head title="SMS Engine & Dispatch Ledger - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2.5">
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-brand-sky border border-brand-navy shadow-inner">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </span>
                        SMS Gateway & Message Ledger
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">Monitor outgoing text notifications, dispatch manual alerts, and configure gateways.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Link
                        :href="route('admin.sms.templates')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#0B1E36] hover:bg-[#102B4D] border border-brand-navy hover:border-brand-sky/40 text-slate-200 hover:text-white text-xs font-semibold rounded-xl shadow-sm transition"
                    >
                        📝 Templates
                    </Link>
                    <Link
                        :href="route('admin.sms.gateways')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#0B1E36] hover:bg-[#102B4D] border border-brand-navy hover:border-brand-sky/40 text-slate-200 hover:text-white text-xs font-semibold rounded-xl shadow-sm transition"
                    >
                        ⚙️ Gateways
                    </Link>
                    <button
                        @click="showSendModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-brand-sky to-brand-blue hover:from-sky-400 hover:to-blue-600 text-white text-xs font-semibold rounded-xl shadow-md shadow-sky-900/30 transition border border-brand-sky/30 active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Send Instant SMS
                    </button>
                </div>
            </div>

            <!-- Top Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Sent</div>
                        <div class="text-xl font-bold text-white font-mono mt-0.5">
                            {{ metrics?.total_sent ?? 0 }}
                        </div>
                    </div>
                </div>

                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Failed / Undelivered</div>
                        <div class="text-xl font-bold text-rose-400 font-mono mt-0.5">
                            {{ metrics?.total_failed ?? 0 }}
                        </div>
                    </div>
                </div>

                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-brand-sky/10 border border-brand-sky/30 flex items-center justify-center text-brand-sky">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Dispatched Today</div>
                        <div class="text-xl font-bold text-brand-sky font-mono mt-0.5">
                            {{ metrics?.today_dispatched ?? 0 }}
                        </div>
                    </div>
                </div>

                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Dispatch Logs</div>
                        <div class="text-xl font-bold text-white font-mono mt-0.5">
                            {{ metrics?.total_logs ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gateways Quick Status Ribbon -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Gateway:</span>
                    <div v-for="gw in gateways" :key="gw.id">
                        <span
                            v-if="gw.is_active"
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-950/60 text-emerald-400 border border-emerald-800/50 shadow-sm"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-sm shadow-emerald-400"></span>
                            {{ gw.name }} <span class="font-mono text-[10px] text-slate-400">({{ gw.driver }})</span>
                        </span>
                    </div>
                    <span v-if="!gateways.some(g => g.is_active)" class="text-xs text-amber-400 bg-amber-950/40 border border-amber-800/40 px-3 py-1 rounded-xl">
                        ⚠️ No external gateway active (Logging locally)
                    </span>
                </div>
                <div class="text-xs text-slate-400">
                    Showing <span class="font-bold text-brand-sky font-mono">{{ logs.total }}</span> total dispatched message logs
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex flex-col md:flex-row gap-4 items-stretch md:items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Search Phone, Customer or Message</label>
                    <div class="relative">
                        <input
                            type="text"
                            v-model="filterSearch"
                            placeholder="e.g. 01712..., customer name, code or keyword"
                            class="w-full pl-9 text-sm rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                        />
                        <svg class="w-4 h-4 text-slate-500 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <div class="w-full md:w-44">
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Status</label>
                    <select
                        v-model="filterStatus"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Statuses</option>
                        <option value="sent">Sent / Delivered</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Gateway</label>
                    <select
                        v-model="filterGateway"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Gateways</option>
                        <option v-for="gw in gateways" :key="gw.id" :value="gw.id">
                            {{ gw.name }}
                        </option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="applyFilters"
                        class="flex-1 md:flex-initial px-4 py-2 bg-[#0B1E36] hover:bg-[#102B4D] text-white text-sm font-semibold rounded-xl border border-brand-navy hover:border-brand-sky/40 transition shadow-sm"
                    >
                        Filter
                    </button>
                    <button
                        v-if="filterStatus || filterSearch || filterGateway"
                        @click="resetFilters"
                        class="px-3 py-2 bg-[#071322] hover:bg-brand-navy/60 text-slate-400 hover:text-white text-sm font-semibold rounded-xl border border-brand-navy transition"
                        title="Reset Filters"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- SMS Logs Container -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-brand-navy shadow-xl overflow-hidden">
                <div class="p-4 border-b border-brand-navy flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="selectAllCurrentPage"
                                @change="toggleSelectAll"
                                class="rounded bg-[#071322] border-brand-navy text-brand-sky focus:ring-brand-sky focus:ring-offset-0 w-4 h-4 cursor-pointer"
                            />
                            <span class="text-xs font-semibold text-slate-300">সব সিলেক্ট করুন (বর্তমান পেজ)</span>
                        </label>
                        <span class="text-xs text-slate-400">| {{ logs?.data?.length || 0 }} entries on this page</span>
                    </div>

                    <!-- Bulk Actions Toolbar -->
                    <div v-if="selectedLogIds.length > 0" class="flex flex-wrap items-center gap-2">
                        <span class="text-xs text-brand-sky font-semibold">
                            {{ targetAllFiltered ? logs.total : selectedLogIds.length }} টি মেসেজ সিলেক্টেড
                        </span>

                        <button
                            @click="openBulkDeleteModal(targetAllFiltered)"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow-md shadow-rose-900/40 transition active:scale-95"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            সিলেক্ট করা মেসেজ ডিলিট ({{ targetAllFiltered ? logs.total : selectedLogIds.length }})
                        </button>
                    </div>
                </div>

                <!-- Multi-page Select All Notification Banner (Gmail Style) -->
                <div
                    v-if="selectAllCurrentPage && logs.total > logs.data.length"
                    class="bg-brand-sky/10 border-b border-brand-sky/20 px-4 py-2.5 text-center text-xs text-slate-300 flex items-center justify-center gap-2 flex-wrap"
                >
                    <span v-if="!targetAllFiltered">
                        বর্তমান পেজের <strong>{{ logs.data.length }}</strong> টি মেসেজ সিলেক্ট করা হয়েছে।
                    </span>
                    <span v-else class="text-brand-sky font-semibold">
                        🎉 সকল পেজের সর্বমোট <strong>{{ logs.total }}</strong> টি মেসেজ সিলেক্ট করা হয়েছে!
                    </span>

                    <button
                        v-if="!targetAllFiltered"
                        @click="targetAllFiltered = true"
                        class="text-brand-sky hover:text-sky-300 font-bold underline hover:no-underline ml-1"
                    >
                        সবগুলো পেজের মোট {{ logs.total }} টি মেসেজই সিলেক্ট করুন
                    </button>
                    <button
                        v-else
                        @click="targetAllFiltered = false"
                        class="text-amber-400 hover:text-amber-300 font-bold underline hover:no-underline ml-1"
                    >
                        শুধুমাত্র বর্তমান পেজ ({{ logs.data.length }}) সিলেক্টে ফিরে যান
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="!logs.data || logs.data.length === 0" class="p-12 text-center text-slate-500">
                    <div class="flex flex-col items-center justify-center gap-2">
                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        <span class="text-slate-400 text-sm">No SMS records found matching the filter criteria.</span>
                    </div>
                </div>

                <!-- Responsive View 1: Mobile Card Layout (block md:hidden) -->
                <div v-else class="block md:hidden divide-y divide-brand-navy/60">
                    <div
                        v-for="log in logs.data"
                        :key="log.id"
                        :class="[
                            'p-4 space-y-3 transition',
                            selectedLogIds.includes(log.id) ? 'bg-brand-navy/40' : 'hover:bg-brand-navy/20'
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <input
                                    type="checkbox"
                                    :value="log.id"
                                    v-model="selectedLogIds"
                                    class="rounded bg-[#071322] border-brand-navy text-brand-sky focus:ring-brand-sky focus:ring-offset-0 w-4 h-4 cursor-pointer"
                                />
                                <span class="font-mono text-xs font-bold text-brand-sky">
                                    {{ log.recipient }}
                                </span>
                            </div>
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase',
                                    (log.status === 'sent' || log.status === 'delivered')
                                        ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/40'
                                        : 'bg-rose-950/60 text-rose-400 border border-rose-800/40'
                                ]"
                            >
                                {{ log.status }}
                            </span>
                        </div>

                        <div v-if="log.customer" class="text-xs">
                            <span class="text-slate-400">Customer: </span>
                            <span class="font-semibold text-white">{{ log.customer.name }}</span>
                            <span class="text-brand-sky font-mono text-[11px] ml-1">({{ log.customer.customer_code }})</span>
                        </div>

                        <div class="p-3 bg-[#071322] rounded-xl border border-brand-navy/60 text-xs text-slate-300 leading-relaxed font-sans whitespace-pre-wrap">
                            {{ log.message }}
                            <div v-if="log.error_message" class="text-[11px] text-rose-400 mt-1 font-mono">
                                ⚠️ {{ log.error_message }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                            <div>
                                <span class="text-slate-500">Via:</span> {{ log.gateway?.name || 'Log Driver' }}
                            </div>
                            <div class="font-mono">
                                {{ formatDateTime(log.sent_at || log.created_at) }}
                            </div>
                        </div>

                        <div class="pt-2 border-t border-brand-navy/40 flex items-center justify-end gap-2">
                            <button
                                v-if="log.status === 'failed'"
                                @click="retrySms(log.id)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-brand-sky/20 hover:bg-brand-sky/30 border border-brand-sky/40 text-brand-sky font-bold text-xs rounded-lg transition"
                            >
                                🔁 Retry
                            </button>
                            <button
                                @click="confirmDeleteSingle(log)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/40 text-rose-400 hover:text-rose-300 font-semibold text-xs rounded-lg transition"
                            >
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive View 2: Desktop Table Layout (hidden md:block) -->
                <div v-if="logs.data && logs.data.length > 0" class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-navy/60 text-left text-sm">
                        <thead class="bg-[#071322]/80 text-slate-400 text-xs uppercase font-semibold whitespace-nowrap">
                            <tr>
                                <th class="w-10 px-4 py-3.5 text-center">
                                    <input
                                        type="checkbox"
                                        v-model="selectAllCurrentPage"
                                        @change="toggleSelectAll"
                                        class="rounded bg-[#071322] border-brand-navy text-brand-sky focus:ring-brand-sky focus:ring-offset-0 w-4 h-4 cursor-pointer"
                                    />
                                </th>
                                <th class="px-4 py-3.5">Recipient</th>
                                <th class="px-4 py-3.5">Customer</th>
                                <th class="px-4 py-3.5">Message Content</th>
                                <th class="px-4 py-3.5">Gateway</th>
                                <th class="px-4 py-3.5">Status</th>
                                <th class="px-4 py-3.5">Timestamp</th>
                                <th class="px-4 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-navy/40 whitespace-nowrap">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                :class="[
                                    'transition',
                                    selectedLogIds.includes(log.id) ? 'bg-brand-navy/40' : 'hover:bg-brand-navy/30'
                                ]"
                            >
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <input
                                        type="checkbox"
                                        :value="log.id"
                                        v-model="selectedLogIds"
                                        class="rounded bg-[#071322] border-brand-navy text-brand-sky focus:ring-brand-sky focus:ring-offset-0 w-4 h-4 cursor-pointer"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-bold text-brand-sky">
                                    {{ log.recipient }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div v-if="log.customer" class="font-medium text-white text-xs">
                                        {{ log.customer.name }}
                                        <span class="text-slate-400 block font-mono text-[10px]">{{ log.customer.customer_code }}</span>
                                    </div>
                                    <span v-else class="text-xs text-slate-500">—</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-300 max-w-md whitespace-normal">
                                    <p class="line-clamp-2">{{ log.message }}</p>
                                    <span v-if="log.error_message" class="text-[11px] text-rose-400 block mt-1 font-mono">
                                        Err: {{ log.error_message }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-400">
                                    {{ log.gateway?.name || 'Log Driver' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase',
                                            (log.status === 'sent' || log.status === 'delivered')
                                                ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/40'
                                                : 'bg-rose-950/60 text-rose-400 border border-rose-800/40'
                                        ]"
                                    >
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-400 font-mono">
                                    {{ formatDateTime(log.sent_at || log.created_at) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="log.status === 'failed'"
                                            @click="retrySms(log.id)"
                                            class="text-brand-sky hover:text-sky-300 font-bold hover:underline"
                                        >
                                            🔁 Retry
                                        </button>
                                        <button
                                            @click="confirmDeleteSingle(log)"
                                            class="text-rose-400 hover:text-rose-300 font-bold hover:underline"
                                            title="Delete SMS Log"
                                        >
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.links && logs.links.length > 3" class="px-4 py-3 bg-[#071322]/80 border-t border-brand-navy flex items-center justify-between">
                    <div class="text-xs text-slate-400">
                        Page {{ logs.current_page }} of {{ logs.last_page }}
                    </div>
                    <div class="flex gap-1.5">
                        <template v-for="(link, i) in logs.links" :key="i">
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

            <!-- Send Instant SMS Modal -->
            <div v-if="showSendModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-[#091A2E] rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-4 sm:p-6 shadow-2xl border border-brand-navy animate-in fade-in zoom-in-95">
                    <div class="flex justify-between items-center pb-3 border-b border-brand-navy">
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            <svg class="h-5 w-5 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            Send Instant SMS Alert
                        </h2>
                        <button @click="showSendModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-brand-navy/60 transition">✕</button>
                    </div>

                    <form @submit.prevent="submitManualSms" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Load Template (Optional)</label>
                            <select
                                v-model="selectedTemplate"
                                @change="onTemplateSelect"
                                class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                            >
                                <option value="">Select a pre-built template...</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Recipient Mobile Number *</label>
                            <input
                                type="text"
                                v-model="form.recipient"
                                required
                                placeholder="017XXXXXXXX"
                                class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky font-mono"
                            />
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="text-xs font-semibold text-slate-300 uppercase">Message Body *</label>
                                <div class="text-[11px] text-slate-400 font-mono space-x-2">
                                    <span>{{ form.message.length }} chars</span>
                                    <span class="text-brand-sky">({{ smsSegments }} SMS)</span>
                                </div>
                            </div>
                            <textarea
                                v-model="form.message"
                                required
                                rows="4"
                                placeholder="Type custom SMS notification here..."
                                class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                            ></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Standard: 160 chars / SMS (English), 70 chars / SMS (Bengali/Unicode).
                            </p>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-brand-navy">
                            <button
                                type="button"
                                @click="showSendModal = false"
                                class="px-4 py-2 border border-brand-navy text-slate-300 text-sm font-medium rounded-xl hover:bg-brand-navy/60 transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 bg-gradient-to-r from-brand-sky to-brand-blue hover:from-sky-400 hover:to-blue-600 text-white text-sm font-semibold rounded-xl shadow-md disabled:opacity-50 transition"
                            >
                                {{ form.processing ? 'Dispatching...' : 'Send SMS Now' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div
                v-if="isDeleteModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-in fade-in duration-200"
            >
                <div class="bg-[#091A2E] rounded-2xl max-w-md w-full p-6 shadow-2xl border border-rose-500/30 text-white space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">
                                {{ logToDelete ? 'SMS লগ ডিলিট নিশ্চিতকরণ' : 'নির্বাচিত SMS লগগুলো ডিলিট নিশ্চিতকরণ' }}
                            </h3>
                            <p class="text-xs text-slate-400">এই প্রক্রিয়াটি অপরিবর্তনীয়।</p>
                        </div>
                    </div>

                    <div class="p-3 bg-[#071322] rounded-xl border border-brand-navy/60 text-xs text-slate-300">
                        <div v-if="logToDelete">
                            আপনি কি নিশ্চিত যে প্রাপক <span class="font-mono font-bold text-brand-sky">{{ logToDelete.recipient }}</span> এর এই SMS লগটি ডিলিট করতে চান?
                        </div>
                        <div v-else-if="targetAllFiltered">
                            আপনি ফিল্টারকৃত <span class="font-bold text-rose-400 font-mono">{{ logs.total }}</span> টি সব SMS লগ ডিলিট করতে যাচ্ছেন! আপনি কি নিশ্চিত?
                        </div>
                        <div v-else>
                            আপনি নির্বাচিত <span class="font-bold text-rose-400 font-mono">{{ selectedLogIds.length }}</span> টি SMS লগ ডিলিট করতে যাচ্ছেন! আপনি কি নিশ্চিত?
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="isDeleteModalOpen = false"
                            class="px-4 py-2 border border-brand-navy text-slate-300 text-xs font-semibold rounded-xl hover:bg-brand-navy/60 transition"
                        >
                            বাতিল করুন
                        </button>
                        <button
                            type="button"
                            @click="executeDelete"
                            :disabled="deleteForm.processing"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-rose-900/40 disabled:opacity-50 transition active:scale-95"
                        >
                            {{ deleteForm.processing ? 'ডিলিট হচ্ছে...' : 'হ্যাঁ, নিশ্চিত ডিলিট করুন' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

