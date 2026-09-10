<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    logs: Object,
    templates: Array,
    gateways: Array,
    filters: Object,
});

const showSendModal = ref(false);
const filterStatus = ref(props.filters.status || '');
const filterSearch = ref(props.filters.search || '');

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

const applyFilters = () => {
    router.get(route('admin.sms.index'), {
        status: filterStatus.value || undefined,
        search: filterSearch.value || undefined,
    }, { preserveState: true, replace: true });
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
</script>

<template>
    <Head title="SMS Engine & Dispatch Ledger - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">SMS Gateway & Message Ledger</h1>
                    <p class="text-sm text-gray-500">Monitor outgoing text notifications, dispatch manual alerts, and configure gateways.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="route('admin.sms.templates')"
                        class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg shadow-sm transition"
                    >
                        📝 Templates
                    </a>
                    <a
                        :href="route('admin.sms.gateways')"
                        class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg shadow-sm transition"
                    >
                        ⚙️ Gateways
                    </a>
                    <button
                        @click="showSendModal = true"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Send Instant SMS
                    </button>
                </div>
            </div>

            <!-- Gateways Quick Status Ribbon -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Active Gateway:</span>
                    <div v-for="gw in gateways" :key="gw.id">
                        <span
                            v-if="gw.is_active"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ gw.name }} ({{ gw.driver }})
                        </span>
                    </div>
                </div>
                <div class="text-xs text-gray-500">
                    Showing <span class="font-bold text-gray-800">{{ logs.total }}</span> total dispatched message logs
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Search Phone or Message</label>
                    <input
                        type="text"
                        v-model="filterSearch"
                        @keyup.enter="applyFilters"
                        placeholder="e.g. 01712... or content keyword"
                        class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Status</label>
                    <select
                        v-model="filterStatus"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div>
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold rounded-lg shadow-sm"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- SMS Logs Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Recipient</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Message Content</th>
                                <th class="px-4 py-3">Gateway</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Timestamp</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="!logs.data || logs.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                    No SMS records found matching the filter criteria.
                                </td>
                            </tr>
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-bold text-gray-900">
                                    {{ log.recipient }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div v-if="log.customer" class="font-medium text-gray-900 text-xs">
                                        {{ log.customer.name }}
                                        <span class="text-gray-400 block font-mono">{{ log.customer.customer_code }}</span>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700 max-w-md">
                                    <p class="line-clamp-2">{{ log.message }}</p>
                                    <span v-if="log.error_message" class="text-[11px] text-rose-500 block mt-1">
                                        Err: {{ log.error_message }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                    {{ log.gateway?.name || 'Log Driver' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase',
                                            log.status === 'sent' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'
                                        ]"
                                    >
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                    {{ log.sent_at ? new Date(log.sent_at).toLocaleString() : new Date(log.created_at).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
                                    <button
                                        v-if="log.status === 'failed'"
                                        @click="retrySms(log.id)"
                                        class="text-indigo-600 hover:text-indigo-900 font-bold hover:underline"
                                    >
                                        🔁 Retry
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.links && logs.links.length > 3" class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex gap-1">
                        <template v-for="(link, i) in logs.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1 text-xs rounded border transition',
                                    link.active ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-100 border-gray-300'
                                ]"
                                v-html="link.label"
                            ></button>
                            <span v-else class="px-3 py-1 text-xs text-gray-400 border border-transparent" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Send Instant SMS Modal -->
            <div v-if="showSendModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-gray-100 animate-in fade-in zoom-in-95">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900">Send Instant SMS Alert</h2>
                        <button @click="showSendModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="submitManualSms" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Load Template (Optional)</label>
                            <select
                                v-model="selectedTemplate"
                                @change="onTemplateSelect"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select a pre-built template...</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Recipient Mobile Number *</label>
                            <input
                                type="text"
                                v-model="form.recipient"
                                required
                                placeholder="017XXXXXXXX"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-semibold text-gray-700 uppercase">Message Body *</label>
                                <span class="text-[11px] text-gray-400">{{ form.message.length }} characters</span>
                            </div>
                            <textarea
                                v-model="form.message"
                                required
                                rows="4"
                                placeholder="Type custom SMS notification here..."
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="showSendModal = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm disabled:opacity-50"
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
