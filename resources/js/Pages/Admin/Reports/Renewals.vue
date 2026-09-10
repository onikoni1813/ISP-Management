<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    summary: Object,
    renewals: Object,
    packages: Array,
    filters: Object,
});

const filterStartDate = ref(props.filters.start_date || props.summary.start_date);
const filterEndDate = ref(props.filters.end_date || props.summary.end_date);
const filterPackage = ref(props.filters.package_id || '');

const applyFilters = () => {
    router.get(route('admin.reports.renewals'), {
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
        package_id: filterPackage.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Package Renewals Performance Report - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <a :href="route('admin.reports.index')" class="text-xs text-indigo-600 hover:underline">← Reports Hub</a>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Renewals Performance Report</h1>
                    <p class="text-sm text-gray-500">Track paid renewals vs validity-shift renewals and package retention.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">From Date</label>
                    <input
                        type="date"
                        v-model="filterStartDate"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">To Date</label>
                    <input
                        type="date"
                        v-model="filterEndDate"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Package</label>
                    <select
                        v-model="filterPackage"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Packages</option>
                        <option v-for="p in packages" :key="p.id" :value="p.id">
                            {{ p.name }} ({{ p.speed_mbps }} Mbps)
                        </option>
                    </select>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Renewals</span>
                    <div class="text-2xl font-extrabold text-gray-900 mt-2">
                        {{ summary.total_renewals }}
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Paid Renewals</span>
                    <div class="text-2xl font-extrabold text-emerald-600 mt-2">
                        {{ summary.paid_renewals_count }}
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Zero-Charge Shifts</span>
                    <div class="text-2xl font-extrabold text-indigo-600 mt-2">
                        {{ summary.zero_charge_renewals_count }}
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Renewal Revenue</span>
                    <div class="text-2xl font-extrabold text-purple-600 mt-2">
                        ৳ {{ Number(summary.total_revenue).toLocaleString() }}
                    </div>
                </div>
            </div>

            <!-- Renewals Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Renewal No</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Package</th>
                                <th class="px-4 py-3">Days</th>
                                <th class="px-4 py-3">Expiry Shift</th>
                                <th class="px-4 py-3">Type / Charge</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="!renewals.data || renewals.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                    No renewal records found for this period.
                                </td>
                            </tr>
                            <tr v-for="r in renewals.data" :key="r.id" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-bold text-gray-800">
                                    {{ r.renewal_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-xs">
                                    {{ new Date(r.renewed_at).toLocaleDateString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold text-gray-900">{{ r.customer?.name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ r.customer?.customer_code }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                    {{ r.package?.name }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-gray-900">
                                    +{{ r.validity_days }} days
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 font-mono">
                                    {{ r.previous_expiry || '—' }} → <span class="font-bold text-indigo-600">{{ r.new_expiry }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span v-if="r.is_zero_charge" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                        Zero-Charge Rule
                                    </span>
                                    <span v-else class="font-bold text-emerald-600">
                                        ৳ {{ Number(r.amount).toLocaleString() }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="renewals.links && renewals.links.length > 3" class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex gap-1">
                        <template v-for="(link, i) in renewals.links" :key="i">
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
        </div>
    </AdminLayout>
</template>
