<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    complaints: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const priority = ref(props.filters.priority || '');

const applyFilters = () => {
    router.get(route('admin.complaints.index'), {
        search: search.value,
        status: status.value,
        priority: priority.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([status, priority], () => {
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
    <Head title="Support Tickets - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Support Tickets & Complaints</h1>
                <p class="text-sm text-slate-400 mt-1">Manage network issues, field technicians, and SLA resolutions.</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <input
                v-model="search"
                type="text"
                placeholder="Search Ticket #, Customer, Subject..."
                class="rounded-xl border-slate-800 bg-slate-900/80 p-2.5 text-xs text-white"
            />

            <select v-model="status" class="rounded-xl border-slate-800 bg-slate-900/80 p-2.5 text-xs text-slate-200 capitalize">
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="assigned">Assigned</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
            </select>

            <select v-model="priority" class="rounded-xl border-slate-800 bg-slate-900/80 p-2.5 text-xs text-slate-200 capitalize">
                <option value="">All Priorities</option>
                <option value="low">Low</option>
                <option value="normal">Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>

        <!-- Complaints Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-950/50 uppercase font-semibold text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Ticket #</th>
                            <th class="px-5 py-4">Customer</th>
                            <th class="px-5 py-4">Subject</th>
                            <th class="px-5 py-4">Priority</th>
                            <th class="px-5 py-4">Technician</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <tr v-for="c in complaints.data" :key="c.id" class="hover:bg-slate-800/40">
                            <td class="px-5 py-3 font-mono font-bold text-indigo-400">{{ c.complaint_number }}</td>
                            <td class="px-5 py-3">
                                <Link :href="route('admin.customers.show', c.customer_id)" class="font-bold text-white hover:text-indigo-300">
                                    {{ c.customer?.name }}
                                </Link>
                                <div class="text-[10px] text-slate-500 font-mono">{{ c.customer?.primary_contact?.phone }}</div>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-200">{{ c.subject }}</td>
                            <td class="px-5 py-3">
                                <span
                                    :class="[
                                        c.priority === 'urgent' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : '',
                                        c.priority === 'high' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                                        c.priority === 'normal' ? 'bg-slate-800 text-slate-300 border-slate-700' : '',
                                        c.priority === 'low' ? 'bg-slate-900 text-slate-500 border-slate-800' : '',
                                        'rounded border px-2 py-0.5 font-bold uppercase text-[10px]'
                                    ]"
                                >
                                    {{ c.priority }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-300">{{ c.assignee?.name || 'Unassigned' }}</td>
                            <td class="px-5 py-3">
                                <span
                                    :class="[
                                        c.status === 'resolved' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : '',
                                        c.status === 'in_progress' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : '',
                                        c.status === 'assigned' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                                        c.status === 'open' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : '',
                                        'rounded border px-2 py-0.5 font-bold uppercase text-[10px]'
                                    ]"
                                >
                                    {{ c.status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <Link
                                    :href="route('admin.complaints.show', c.id)"
                                    class="rounded-lg bg-slate-800 hover:bg-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200"
                                >
                                    Manage
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="complaints.data.length === 0">
                            <td colspan="7" class="text-center py-8 text-slate-500">No support tickets found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
