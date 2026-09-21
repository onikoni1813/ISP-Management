<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    logs: Object,
    staffUsers: Array,
    modules: Array,
    actions: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const userId = ref(props.filters.user_id || '');
const moduleName = ref(props.filters.module || '');
const actionName = ref(props.filters.action || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const applyFilters = () => {
    router.get(route('admin.audit.index'), {
        search: search.value,
        user_id: userId.value,
        module: moduleName.value,
        action: actionName.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([userId, moduleName, actionName, startDate, endDate], () => {
    applyFilters();
});

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});
</script>

<template>
    <Head title="System Audit Logs - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Audit Trail Ledger</h1>
                <p class="text-sm text-slate-400 mt-1">Traceable logs answering who performed what, when, IP address, and payload data.</p>
            </div>
            <Link
                :href="route('admin.audit.staff-report')"
                class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2.5 text-xs font-bold text-indigo-400 hover:bg-indigo-500/20 transition shadow-sm"
            >
                📊 Staff Activity & Accountability Report
            </Link>
        </div>

        <!-- Filter Controls -->
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="sm:col-span-2">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search action, IP, user name..."
                    class="w-full rounded-xl border-slate-800 bg-slate-900/80 p-2.5 text-xs text-white placeholder-slate-500"
                />
            </div>

            <div>
                <select v-model="userId" class="w-full rounded-xl border-slate-800 bg-slate-900/80 p-2.5 text-xs text-slate-200">
                    <option value="">All Staff / Users</option>
                    <option v-for="u in staffUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
            </div>

            <div>
                <select v-model="moduleName" class="w-full rounded-xl border-slate-800 bg-slate-900/80 p-2.5 text-xs text-slate-200 capitalize">
                    <option value="">All Modules</option>
                    <option v-for="m in modules" :key="m" :value="m">{{ m }}</option>
                </select>
            </div>

            <div>
                <input v-model="startDate" type="date" class="w-full rounded-xl border-slate-800 bg-slate-900/80 p-2.5 text-xs text-slate-300 font-mono" />
            </div>

            <div>
                <input v-model="endDate" type="date" class="w-full rounded-xl border-slate-800 bg-slate-900/80 p-2.5 text-xs text-slate-300 font-mono" />
            </div>
        </div>

        <!-- Audit Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-950/50 uppercase font-semibold text-slate-400">
                        <tr>
                            <th class="px-5 py-4">User</th>
                            <th class="px-5 py-4">Action</th>
                            <th class="px-5 py-4">Module</th>
                            <th class="px-5 py-4">Entity</th>
                            <th class="px-5 py-4">IP Address</th>
                            <th class="px-5 py-4">Timestamp</th>
                            <th class="px-5 py-4 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3">
                                <div class="font-bold text-white">{{ log.user?.name || 'System / CLI' }}</div>
                                <div class="text-[10px] text-slate-500">{{ log.user?.email }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2 py-0.5 font-mono text-[11px] font-bold">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="px-5 py-3 capitalize text-slate-300">
                                {{ log.module }}
                            </td>
                            <td class="px-5 py-3 font-mono text-slate-400">
                                {{ log.entity_type ? log.entity_type.split('\\').pop() : 'N/A' }} #{{ log.entity_id || '-' }}
                            </td>
                            <td class="px-5 py-3 font-mono text-slate-400">
                                {{ log.ip_address || '127.0.0.1' }}
                            </td>
                            <td class="px-5 py-3 font-mono text-slate-400">
                                {{ formatDateTime(log.created_at) }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span v-if="log.new_values" :title="JSON.stringify(log.new_values)" class="cursor-pointer text-indigo-400 hover:text-indigo-300 font-semibold underline">
                                    Payload
                                </span>
                                <span v-else class="text-slate-600">-</span>
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                                No audit records found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="logs.links && logs.links.length > 3" class="flex items-center justify-between border-t border-slate-800 px-5 py-3 text-xs text-slate-400">
                <div>Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }} records</div>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="(link, i) in logs.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            link.active ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:bg-slate-800',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                            'rounded-lg px-2.5 py-1 transition'
                        ]"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
