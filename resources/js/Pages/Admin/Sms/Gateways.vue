<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    gateways: Array,
});

const showAddModal = ref(false);

const form = useForm({
    name: '',
    driver: 'generic_http',
    api_url: '',
    api_key: '',
    sender_id: 'PIRGACHA',
    is_active: true,
});

const submitGateway = () => {
    form.post(route('admin.sms.gateways.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        }
    });
};

const activateGateway = (id) => {
    router.post(route('admin.sms.gateways.activate', id));
};
</script>

<template>
    <Head title="SMS Gateways Configuration - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <a :href="route('admin.sms.index')" class="text-xs text-indigo-600 hover:underline">← SMS Logs Hub</a>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">SMS Gateways Configuration</h1>
                    <p class="text-sm text-gray-500">Provider abstraction layer supporting Greenweb, BulkSMSBD, AlphaSMS, and custom HTTP endpoints.</p>
                </div>
                <button
                    @click="showAddModal = true"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition"
                >
                    + Add New Gateway
                </button>
            </div>

            <!-- Gateways Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="gw in gateways"
                    :key="gw.id"
                    class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900">{{ gw.name }}</h3>
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase',
                                    gw.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-400'
                                ]"
                            >
                                {{ gw.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-2 text-xs text-gray-600">
                            <div><span class="font-semibold text-gray-400">Driver:</span> <span class="font-mono">{{ gw.driver }}</span></div>
                            <div><span class="font-semibold text-gray-400">Sender ID:</span> <span class="font-bold text-gray-800">{{ gw.sender_id || 'N/A' }}</span></div>
                            <div class="truncate"><span class="font-semibold text-gray-400">API Endpoint:</span> <span class="font-mono text-gray-500">{{ gw.api_url || 'Local Log Channel' }}</span></div>
                            <div><span class="font-semibold text-gray-400">API Key:</span> <span class="font-mono text-gray-400">{{ gw.api_key ? '••••••••••••••••' : 'None' }}</span></div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-[11px] text-gray-400">Registered Gateway</span>
                        <button
                            v-if="!gw.is_active"
                            @click="activateGateway(gw.id)"
                            class="px-3 py-1.5 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg transition"
                        >
                            Activate This Gateway
                        </button>
                        <span v-else class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                            ✓ Currently In Use
                        </span>
                    </div>
                </div>
            </div>

            <!-- Add Gateway Modal -->
            <div v-if="showAddModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-gray-100 animate-in fade-in zoom-in-95">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900">Configure New SMS Gateway</h2>
                        <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="submitGateway" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Gateway Name *</label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                placeholder="e.g. Greenweb Bangladesh"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Driver Architecture *</label>
                                <select
                                    v-model="form.driver"
                                    required
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="generic_http">Generic HTTP / POST</option>
                                    <option value="greenweb">Greenweb Driver</option>
                                    <option value="bulksmsbd">BulkSMSBD Driver</option>
                                    <option value="log">Log File (Dev/Test)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Sender ID / Masking</label>
                                <input
                                    type="text"
                                    v-model="form.sender_id"
                                    placeholder="e.g. PIRGACHA"
                                    class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">API Endpoint URL</label>
                            <input
                                type="url"
                                v-model="form.api_url"
                                placeholder="https://api.sms-provider.com/send"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">API Key / Token</label>
                            <input
                                type="password"
                                v-model="form.api_key"
                                placeholder="Enter provider API Secret Token"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono text-xs"
                            />
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="gw_active"
                                v-model="form.is_active"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="gw_active" class="text-xs font-semibold text-gray-700 cursor-pointer">
                                Set as Primary Active SMS Gateway
                            </label>
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
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm disabled:opacity-50"
                            >
                                {{ form.processing ? 'Saving...' : 'Register Gateway' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
