<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    complaints: Object,
    filters: Object,
    counts: Object,
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

const filterByStatus = (statusVal) => {
    status.value = statusVal;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    priority.value = '';
    applyFilters();
};

watch([status, priority], () => {
    applyFilters();
});

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 350);
});

// Selection & Bulk Deletion
const selectedIds = ref([]);
const isAllSelected = computed(() => {
    const list = props.complaints.data || [];
    return list.length > 0 && list.every(c => selectedIds.value.includes(c.id));
});

const toggleSelectAll = () => {
    const list = props.complaints.data || [];
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = list.map(c => c.id);
    }
};

const toggleSelect = (id) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

// Modals State
const isDeleting = ref(false);

// Single Delete Modal
const singleDeleteModal = ref(false);
const complaintToDelete = ref(null);

const openSingleDelete = (complaint) => {
    complaintToDelete.value = complaint;
    singleDeleteModal.value = true;
};

const confirmSingleDelete = () => {
    if (!complaintToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('admin.complaints.destroy', complaintToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            singleDeleteModal.value = false;
            selectedIds.value = selectedIds.value.filter(id => id !== complaintToDelete.value?.id);
            complaintToDelete.value = null;
        }
    });
};

// Bulk Delete Modal
const bulkDeleteModal = ref(false);
const confirmBulkDelete = () => {
    if (selectedIds.value.length === 0) return;
    isDeleting.value = true;
    router.delete(route('admin.complaints.bulk-destroy'), {
        data: { complaint_ids: selectedIds.value },
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            bulkDeleteModal.value = false;
            selectedIds.value = [];
        }
    });
};

// Clear History Modal
const clearHistoryModal = ref(false);
const historyFilterType = ref('resolved');
const confirmClearHistory = () => {
    isDeleting.value = true;
    router.delete(route('admin.complaints.clear-history'), {
        data: { filter_type: historyFilterType.value },
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            clearHistoryModal.value = false;
            selectedIds.value = [];
        }
    });
};
</script>

<template>
    <Head title="Support Tickets - Pirgacha Internet" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span>Support Tickets & Complaints</span>
                        <span class="rounded-lg bg-indigo-500/20 border border-indigo-500/40 px-2.5 py-0.5 text-xs font-bold text-indigo-400">
                            Helpdesk & SLA
                        </span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Monitor customer complaints, assign field technicians, track resolution SLA, and manage ticket history.
                    </p>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <!-- Clear History Button -->
                    <button
                        @click="clearHistoryModal = true"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-rose-500/30 bg-rose-950/30 hover:bg-rose-900/50 px-3.5 py-2.5 text-xs font-bold text-rose-300 hover:text-white transition shadow-sm"
                        title="Delete ticket history (resolved, older than 30 days, or all)"
                    >
                        <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Delete History</span>
                    </button>
                </div>
            </div>

            <!-- Ticket Metrics / Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <button
                    @click="filterByStatus('')"
                    class="text-left rounded-2xl border transition-all p-4 backdrop-blur-sm"
                    :class="status === '' ? 'border-brand-sky bg-brand-sky/10 shadow-lg shadow-brand-sky/10' : 'border-brand-navy/60 bg-[#0B1E36]/80 hover:border-brand-navy'"
                >
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Tickets</div>
                    <div class="text-2xl font-black text-white mt-1">{{ counts?.total ?? complaints?.total ?? 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">All complaints logged</div>
                </button>

                <button
                    @click="filterByStatus('open')"
                    class="text-left rounded-2xl border transition-all p-4 backdrop-blur-sm"
                    :class="status === 'open' ? 'border-rose-400 bg-rose-950/40 shadow-lg shadow-rose-500/10' : 'border-rose-500/30 bg-rose-950/20 hover:border-rose-500/50'"
                >
                    <div class="text-[11px] font-bold uppercase tracking-wider text-rose-400">Open Tickets</div>
                    <div class="text-2xl font-black text-rose-400 mt-1">{{ counts?.open ?? 0 }}</div>
                    <div class="text-[11px] text-rose-400/80 mt-1">Awaiting technician assignment</div>
                </button>

                <button
                    @click="filterByStatus('in_progress')"
                    class="text-left rounded-2xl border transition-all p-4 backdrop-blur-sm"
                    :class="status === 'in_progress' ? 'border-amber-400 bg-amber-950/40 shadow-lg shadow-amber-500/10' : 'border-amber-500/30 bg-amber-950/20 hover:border-amber-500/50'"
                >
                    <div class="text-[11px] font-bold uppercase tracking-wider text-amber-400">In Progress / Assigned</div>
                    <div class="text-2xl font-black text-amber-400 mt-1">{{ counts?.in_progress ?? 0 }}</div>
                    <div class="text-[11px] text-amber-400/80 mt-1">Technicians currently resolving</div>
                </button>

                <button
                    @click="filterByStatus('resolved')"
                    class="text-left rounded-2xl border transition-all p-4 backdrop-blur-sm"
                    :class="status === 'resolved' ? 'border-emerald-400 bg-emerald-950/40 shadow-lg shadow-emerald-500/10' : 'border-emerald-500/30 bg-emerald-950/20 hover:border-emerald-500/50'"
                >
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Resolved / Closed</div>
                    <div class="text-2xl font-black text-emerald-400 mt-1">{{ counts?.resolved ?? 0 }}</div>
                    <div class="text-[11px] text-emerald-400/80 mt-1">Issues fixed & closed</div>
                </button>
            </div>

            <!-- Filters Bar -->
            <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between rounded-2xl border border-brand-navy/60 bg-[#0B1E36]/40 p-3">
                <div class="relative flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search Ticket #, Customer Name, Phone, or Subject..."
                        class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 pl-9 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                    />
                    <svg class="absolute left-3 top-3 h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <button
                        v-if="search"
                        @click="search = ''"
                        class="absolute right-3 top-2.5 text-xs text-slate-400 hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="status"
                        class="w-full sm:w-auto rounded-xl border border-brand-navy bg-[#071322] px-3 py-2.5 text-xs text-slate-200 capitalize focus:border-brand-sky focus:outline-none"
                    >
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>

                    <select
                        v-model="priority"
                        class="w-full sm:w-auto rounded-xl border border-brand-navy bg-[#071322] px-3 py-2.5 text-xs text-slate-200 capitalize focus:border-brand-sky focus:outline-none"
                    >
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>

                    <button
                        v-if="search || status || priority"
                        @click="resetFilters"
                        class="rounded-xl border border-rose-500/30 bg-rose-950/20 px-3.5 py-2.5 text-xs font-bold text-rose-400 hover:bg-rose-950/40 transition shrink-0"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Bulk Action Toolbar (When tickets are selected) -->
            <div
                v-if="selectedIds.length > 0"
                class="flex items-center justify-between rounded-2xl border border-rose-500/40 bg-rose-950/40 p-4 shadow-xl backdrop-blur-md"
            >
                <div class="flex items-center gap-3">
                    <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-rose-500/20 text-xs font-black text-rose-300">
                        {{ selectedIds.length }}
                    </span>
                    <span class="text-xs font-bold text-white">
                        ticket(s) selected
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="selectedIds = []"
                        class="rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:text-white transition"
                    >
                        Deselect All
                    </button>
                    <button
                        @click="bulkDeleteModal = true"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 px-4 py-1.5 text-xs font-bold text-white shadow-lg shadow-rose-600/30 transition active:scale-95"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Selected ({{ selectedIds.length }})
                    </button>
                </div>
            </div>

            <!-- Complaints Desktop Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-hidden rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/60 shadow-xl backdrop-blur-md">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-brand-navy/80 bg-[#071322]/80 uppercase font-semibold text-slate-400">
                        <tr>
                            <th class="px-4 py-3.5 w-10 text-center">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    @change="toggleSelectAll"
                                    class="rounded border-slate-700 bg-[#071322] text-brand-sky focus:ring-0 cursor-pointer"
                                />
                            </th>
                            <th class="px-5 py-3.5">Ticket #</th>
                            <th class="px-5 py-3.5">Customer Info</th>
                            <th class="px-5 py-3.5">Subject</th>
                            <th class="px-5 py-3.5">Priority</th>
                            <th class="px-5 py-3.5">Technician</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-navy/50">
                        <tr v-for="c in complaints.data" :key="c.id" class="hover:bg-brand-navy/30 transition-colors">
                            <td class="px-4 py-4 text-center">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(c.id)"
                                    @change="toggleSelect(c.id)"
                                    class="rounded border-slate-700 bg-[#071322] text-brand-sky focus:ring-0 cursor-pointer"
                                />
                            </td>
                            <td class="px-5 py-4 font-mono font-bold text-brand-sky whitespace-nowrap">
                                {{ c.complaint_number }}
                            </td>
                            <td class="px-5 py-4">
                                <Link :href="route('admin.customers.show', c.customer_id)" class="font-bold text-white hover:text-brand-sky transition">
                                    {{ c.customer?.name }}
                                </Link>
                                <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                    {{ c.customer?.primary_contact?.phone || 'No phone' }}
                                </div>
                            </td>
                            <td class="px-5 py-4 max-w-xs">
                                <div class="font-medium text-slate-200 truncate">{{ c.subject }}</div>
                                <div class="text-[11px] text-slate-400 truncate mt-0.5">{{ c.description }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        c.priority === 'urgent' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : '',
                                        c.priority === 'high' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                                        c.priority === 'normal' ? 'bg-slate-800 text-slate-300 border-slate-700' : '',
                                        c.priority === 'low' ? 'bg-slate-900 text-slate-500 border-slate-800' : '',
                                        'rounded-lg border px-2.5 py-1 font-bold uppercase text-[10px]'
                                    ]"
                                >
                                    {{ c.priority }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div v-if="c.assignee" class="font-bold text-white">
                                    {{ c.assignee.name }}
                                </div>
                                <div v-else class="text-slate-500 italic">
                                    Unassigned
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        c.status === 'resolved' || c.status === 'closed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : '',
                                        c.status === 'in_progress' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : '',
                                        c.status === 'assigned' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                                        c.status === 'open' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : '',
                                        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold capitalize'
                                    ]"
                                >
                                    <span :class="[
                                        'h-1.5 w-1.5 rounded-full',
                                        c.status === 'resolved' || c.status === 'closed' ? 'bg-emerald-400' : '',
                                        c.status === 'in_progress' ? 'bg-indigo-400' : '',
                                        c.status === 'assigned' ? 'bg-amber-400' : '',
                                        c.status === 'open' ? 'bg-rose-400' : '',
                                    ]"></span>
                                    {{ c.status?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link
                                        :href="route('admin.complaints.show', c.id)"
                                        class="inline-flex items-center gap-1 rounded-xl border border-brand-navy bg-[#071322] hover:bg-brand-navy px-3 py-1.5 text-xs font-bold text-slate-200 hover:text-white transition shadow-sm"
                                    >
                                        Manage
                                    </Link>
                                    <button
                                        @click="openSingleDelete(c)"
                                        type="button"
                                        class="inline-flex items-center justify-center p-1.5 rounded-xl border border-rose-500/20 bg-rose-950/20 text-rose-400 hover:bg-rose-900/40 hover:text-rose-200 hover:border-rose-500/40 transition"
                                        title="Delete ticket"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="complaints.data.length === 0">
                            <td colspan="8" class="text-center py-12 text-slate-400 text-xs">
                                No support tickets found matching your filter criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Complaints Mobile Cards View (Visible on mobile/tablet) -->
            <div class="block md:hidden space-y-3">
                <div
                    v-for="c in complaints.data"
                    :key="'mobile-' + c.id"
                    class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/80 p-4 shadow-lg backdrop-blur-md space-y-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                :checked="selectedIds.includes(c.id)"
                                @change="toggleSelect(c.id)"
                                class="rounded border-slate-700 bg-[#071322] text-brand-sky focus:ring-0 cursor-pointer"
                            />
                            <div>
                                <div class="font-mono font-bold text-xs text-brand-sky">{{ c.complaint_number }}</div>
                                <div class="font-bold text-white text-sm mt-0.5">{{ c.subject }}</div>
                            </div>
                        </div>
                        <span
                            :class="[
                                c.status === 'resolved' || c.status === 'closed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : '',
                                c.status === 'in_progress' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : '',
                                c.status === 'assigned' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                                c.status === 'open' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : '',
                                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold capitalize'
                            ]"
                        >
                            {{ c.status?.replace('_', ' ') }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-300 line-clamp-2 bg-[#071322]/60 rounded-xl p-2.5 border border-brand-navy/40">
                        {{ c.description }}
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs py-2 border-y border-brand-navy/60">
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-400">Customer</div>
                            <Link :href="route('admin.customers.show', c.customer_id)" class="font-bold text-white hover:text-brand-sky block truncate">
                                {{ c.customer?.name }}
                            </Link>
                            <div class="text-[10px] text-slate-400 font-mono">{{ c.customer?.primary_contact?.phone || 'No phone' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-400">Assigned To</div>
                            <div class="font-bold text-white truncate">
                                {{ c.assignee?.name || 'Unassigned' }}
                            </div>
                            <div class="text-[10px] uppercase font-bold text-slate-400 mt-1">
                                Priority: <span class="capitalize text-brand-sky">{{ c.priority }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <Link
                            :href="route('admin.complaints.show', c.id)"
                            class="flex-1 text-center rounded-xl border border-brand-navy bg-[#071322] hover:bg-brand-navy px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white transition shadow-sm"
                        >
                            View & Manage Ticket →
                        </Link>
                        <button
                            @click="openSingleDelete(c)"
                            type="button"
                            class="p-2 rounded-xl border border-rose-500/20 bg-rose-950/20 text-rose-400 hover:bg-rose-900/40 transition shrink-0"
                            title="Delete ticket"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div v-if="complaints.data.length === 0" class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/40 p-8 text-center text-slate-400 text-xs">
                    No support tickets found matching your filter criteria.
                </div>
            </div>

            <!-- Pagination Bar -->
            <div v-if="complaints.links && complaints.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 border border-brand-navy/60 rounded-2xl px-5 py-3.5 text-xs text-slate-400 bg-[#0B1E36]/60 backdrop-blur-sm">
                <div class="text-center sm:text-left">Showing {{ complaints.from }} to {{ complaints.to }} of {{ complaints.total }} tickets</div>
                <div class="flex items-center gap-1 flex-wrap justify-center">
                    <Link
                        v-for="(link, i) in complaints.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            link.active ? 'bg-brand-blue text-white font-bold' : 'text-slate-400 hover:bg-brand-navy',
                            !link.url ? 'opacity-40 pointer-events-none' : '',
                            'rounded-lg px-3 py-1.5 transition'
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- 1. Single Ticket Delete Confirmation Modal -->
        <div v-if="singleDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="singleDeleteModal = false"></div>
            <div class="relative w-full max-w-md rounded-3xl border border-rose-500/30 bg-slate-900 p-6 shadow-2xl space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-white">Delete Ticket</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Are you sure you want to permanently delete ticket <strong class="text-brand-sky font-mono">{{ complaintToDelete?.complaint_number }}</strong>?
                    </p>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3 text-xs space-y-1">
                    <div class="text-slate-300 font-semibold">{{ complaintToDelete?.subject }}</div>
                    <div class="text-slate-500">Customer: {{ complaintToDelete?.customer?.name }}</div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        @click="singleDeleteModal = false"
                        :disabled="isDeleting"
                        class="flex-1 rounded-xl border border-slate-700 bg-slate-800 py-2.5 text-xs font-bold text-slate-300 hover:bg-slate-700 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmSingleDelete"
                        :disabled="isDeleting"
                        class="flex-1 rounded-xl bg-rose-600 hover:bg-rose-500 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-600/30 transition flex items-center justify-center gap-1.5"
                    >
                        <svg v-if="isDeleting" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Delete Ticket
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Bulk Delete Confirmation Modal -->
        <div v-if="bulkDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="bulkDeleteModal = false"></div>
            <div class="relative w-full max-w-md rounded-3xl border border-rose-500/30 bg-slate-900 p-6 shadow-2xl space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-white">Delete Selected Tickets</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Are you sure you want to permanently delete all <strong class="text-rose-400 font-bold">{{ selectedIds.length }}</strong> selected tickets? This will also remove their comments and status history.
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        @click="bulkDeleteModal = false"
                        :disabled="isDeleting"
                        class="flex-1 rounded-xl border border-slate-700 bg-slate-800 py-2.5 text-xs font-bold text-slate-300 hover:bg-slate-700 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmBulkDelete"
                        :disabled="isDeleting"
                        class="flex-1 rounded-xl bg-rose-600 hover:bg-rose-500 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-600/30 transition flex items-center justify-center gap-1.5"
                    >
                        <svg v-if="isDeleting" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Delete All ({{ selectedIds.length }})
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Clear History Modal (Delete Complaint History) -->
        <div v-if="clearHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="clearHistoryModal = false"></div>
            <div class="relative w-full max-w-lg rounded-3xl border border-rose-500/40 bg-slate-900 p-6 shadow-2xl space-y-5">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Clear Complaint History</h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Choose which complaint history records you want to permanently clear from the database.
                        </p>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <label
                        class="flex items-center justify-between p-3.5 rounded-2xl border cursor-pointer transition"
                        :class="historyFilterType === 'resolved' ? 'border-brand-sky bg-brand-sky/10' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                    >
                        <div class="flex items-center gap-3">
                            <input type="radio" v-model="historyFilterType" value="resolved" class="text-brand-sky focus:ring-0" />
                            <div>
                                <div class="text-xs font-bold text-white">Resolved & Closed Tickets (Recommended)</div>
                                <div class="text-[11px] text-slate-400">Clears all completed tickets, keeps open/in-progress issues active</div>
                            </div>
                        </div>
                        <span class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2 py-0.5">
                            {{ counts?.resolved ?? 0 }}
                        </span>
                    </label>

                    <label
                        class="flex items-center justify-between p-3.5 rounded-2xl border cursor-pointer transition"
                        :class="historyFilterType === 'older_than_30_days' ? 'border-brand-sky bg-brand-sky/10' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                    >
                        <div class="flex items-center gap-3">
                            <input type="radio" v-model="historyFilterType" value="older_than_30_days" class="text-brand-sky focus:ring-0" />
                            <div>
                                <div class="text-xs font-bold text-white">Older than 30 Days</div>
                                <div class="text-[11px] text-slate-400">Clears tickets logged over 1 month ago</div>
                            </div>
                        </div>
                    </label>

                    <label
                        class="flex items-center justify-between p-3.5 rounded-2xl border cursor-pointer transition"
                        :class="historyFilterType === 'older_than_90_days' ? 'border-brand-sky bg-brand-sky/10' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                    >
                        <div class="flex items-center gap-3">
                            <input type="radio" v-model="historyFilterType" value="older_than_90_days" class="text-brand-sky focus:ring-0" />
                            <div>
                                <div class="text-xs font-bold text-white">Older than 90 Days</div>
                                <div class="text-[11px] text-slate-400">Archive cleanup: removes complaints older than 3 months</div>
                            </div>
                        </div>
                    </label>

                    <label
                        class="flex items-center justify-between p-3.5 rounded-2xl border cursor-pointer transition"
                        :class="historyFilterType === 'all' ? 'border-rose-500 bg-rose-950/20' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                    >
                        <div class="flex items-center gap-3">
                            <input type="radio" v-model="historyFilterType" value="all" class="text-rose-500 focus:ring-0" />
                            <div>
                                <div class="text-xs font-bold text-rose-400">All Tickets (Complete Reset)</div>
                                <div class="text-[11px] text-slate-400">Warning: Clears all tickets regardless of current status</div>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="rounded-xl border border-rose-500/20 bg-rose-950/30 p-3 flex items-start gap-2.5 text-xs text-rose-300">
                    <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>This action is permanent and cannot be undone. All technician work comments and transition logs will be removed.</span>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        @click="clearHistoryModal = false"
                        :disabled="isDeleting"
                        class="flex-1 rounded-xl border border-slate-700 bg-slate-800 py-2.5 text-xs font-bold text-slate-300 hover:bg-slate-700 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmClearHistory"
                        :disabled="isDeleting"
                        class="flex-1 rounded-xl bg-rose-600 hover:bg-rose-500 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-600/30 transition flex items-center justify-center gap-1.5"
                    >
                        <svg v-if="isDeleting" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Clear History Now
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
