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
                <div class="p-4 border-b border-brand-navy flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white tracking-wide">Dispatch History & Audit</h3>
                    <span class="text-xs text-slate-400">{{ logs?.data?.length || 0 }} entries on this page</span>
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
                        class="p-4 space-y-3 hover:bg-brand-navy/20 transition"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-brand-sky">
                                {{ log.recipient }}
                            </span>
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

                        <div v-if="log.status === 'failed'" class="pt-2 border-t border-brand-navy/40 flex justify-end">
                            <button
                                @click="retrySms(log.id)"
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-brand-sky/20 hover:bg-brand-sky/30 border border-brand-sky/40 text-brand-sky font-bold text-xs rounded-lg transition"
                            >
                                🔁 Retry Send
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive View 2: Desktop Table Layout (hidden md:block) -->
                <div v-if="logs.data && logs.data.length > 0" class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-navy/60 text-left text-sm">
                        <thead class="bg-[#071322]/80 text-slate-400 text-xs uppercase font-semibold whitespace-nowrap">
                            <tr>
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
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-brand-navy/30 transition">
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
                                    <button
                                        v-if="log.status === 'failed'"
                                        @click="retrySms(log.id)"
                                        class="text-brand-sky hover:text-sky-300 font-bold hover:underline"
                                    >
                                        🔁 Retry
                                    </button>
                                    <span v-else class="text-slate-600 text-[11px]">Completed</span>
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
        </div>
    </AdminLayout>
</template>

