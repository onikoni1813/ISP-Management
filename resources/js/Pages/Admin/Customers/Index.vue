<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    customers: Object,
    filters: Object,
    areas: Array,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const areaId = ref(props.filters.area_id || '');

const applyFilters = () => {
    router.get(route('admin.customers.index'), {
        search: search.value,
        status: status.value,
        area_id: areaId.value,
    }, {
        preserveState: true,
        replace: true,
    });
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
</script>

<template>
    <Head title="Customer Directory - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Customer Management</h1>
                <p class="text-sm text-slate-400 mt-1">Manage active subscribers, connections, and service history.</p>
            </div>
            <Link
                :href="route('admin.customers.create')"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Customer
            </Link>
        </div>

        <!-- Filter Controls -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="relative">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search Code, Name, Phone, PPPoE, IP..."
                    class="w-full rounded-xl border-slate-800 bg-slate-900/80 py-2.5 pl-10 pr-4 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500"
                />
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <select
                v-model="status"
                class="rounded-xl border-slate-800 bg-slate-900/80 py-2.5 px-3 text-sm text-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="suspended">Suspended</option>
                <option value="disconnected">Disconnected</option>
            </select>

            <select
                v-model="areaId"
                class="rounded-xl border-slate-800 bg-slate-900/80 py-2.5 px-3 text-sm text-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All Areas</option>
                <option v-for="area in areas" :key="area.id" :value="area.id">
                    {{ area.name }} ({{ area.code }})
                </option>
            </select>
        </div>

        <!-- Customer Data Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-950/50 text-xs uppercase font-semibold text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Customer</th>
                            <th class="px-5 py-4">Contact</th>
                            <th class="px-5 py-4">Area</th>
                            <th class="px-5 py-4">Package</th>
                            <th class="px-5 py-4">PPPoE User</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4">
                                <Link :href="route('admin.customers.show', customer.id)" class="font-bold text-white hover:text-indigo-400 transition">
                                    {{ customer.name }}
                                </Link>
                                <div class="text-xs text-slate-500 font-mono">{{ customer.customer_code }}</div>
                            </td>
                            <td class="px-5 py-4 font-mono text-slate-200">
                                {{ customer.primary_contact?.phone || 'N/A' }}
                            </td>
                            <td class="px-5 py-4">
                                {{ customer.area?.name || 'Unassigned' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-medium text-emerald-400">
                                    {{ customer.connections[0]?.current_package?.name || 'None' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-indigo-300">
                                {{ customer.connections[0]?.pppoe_credential?.username || 'None' }}
                            </td>
                            <td class="px-5 py-4">
                                <span 
                                    :class="[
                                        customer.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                        'inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold uppercase'
                                    ]"
                                >
                                    {{ customer.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <Link
                                    :href="route('admin.customers.show', customer.id)"
                                    class="inline-flex items-center gap-1 rounded-lg bg-slate-800 hover:bg-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 transition"
                                >
                                    View Profile
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                                No customers found matching your criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="customers.links && customers.links.length > 3" class="flex items-center justify-between border-t border-slate-800 px-5 py-3 text-xs text-slate-400">
                <div>Showing {{ customers.from }} to {{ customers.to }} of {{ customers.total }} customers</div>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="(link, i) in customers.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            link.active ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:bg-slate-800',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                            'rounded-lg px-3 py-1.5 transition'
                        ]"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
