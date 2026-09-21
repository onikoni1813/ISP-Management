<script setup>
import { ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    staffMembers: Array,
    filters: Object,
    totalStaffCount: Number,
    activeStaffCount: Number,
    inactiveStaffCount: Number,
});

const showDeleteConfirm = ref(false);
const staffToDelete = ref(null);
const deletingStaff = ref(false);

const showStatusConfirm = ref(false);
const staffToToggle = ref(null);
const togglingStatus = ref(false);

const searchQuery = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');

const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingStaff = ref(null);

const addForm = useForm({
    name: '',
    email: '',
    phone: '',
    username: '',
    password: '',
    role: 'staff',
    status: 'active',
});

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    username: '',
    password: '',
    role: 'staff',
    status: 'active',
});

let searchDebounceTimer = null;
const executeSearch = () => {
    router.get(route('admin.staff-members.index'), {
        search: searchQuery.value,
        status: selectedStatus.value,
    }, { preserveState: true, replace: true });
};

watch(searchQuery, () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        executeSearch();
    }, 350);
});

const setStatusFilter = (status) => {
    selectedStatus.value = status;
    executeSearch();
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = '';
    executeSearch();
};

const openAddModal = () => {
    addForm.reset();
    isAddModalOpen.value = true;
};

const submitAddStaff = () => {
    addForm.post(route('admin.staff-members.store'), {
        onSuccess: () => {
            isAddModalOpen.value = false;
            addForm.reset();
        },
    });
};

const openEditModal = (staff) => {
    editingStaff.value = staff;
    editForm.name = staff.name;
    editForm.email = staff.email;
    editForm.phone = staff.phone || '';
    editForm.username = staff.username || '';
    editForm.password = '';
    editForm.role = staff.is_admin ? 'admin' : 'staff';
    editForm.status = staff.status;
    isEditModalOpen.value = true;
};

const submitEditStaff = () => {
    if (!editingStaff.value) return;
    editForm.patch(route('admin.staff-members.update', editingStaff.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingStaff.value = null;
        },
    });
};

const openToggleStaffStatus = (staff) => {
    staffToToggle.value = staff;
    showStatusConfirm.value = true;
};

const confirmToggleStaffStatus = () => {
    if (!staffToToggle.value) return;
    togglingStatus.value = true;
    router.post(route('admin.staff-members.toggle-status', staffToToggle.value.id), {}, {
        onFinish: () => {
            togglingStatus.value = false;
            showStatusConfirm.value = false;
            staffToToggle.value = null;
        }
    });
};

const openDeleteStaff = (staff) => {
    staffToDelete.value = staff;
    showDeleteConfirm.value = true;
};

const confirmDeleteStaff = () => {
    if (!staffToDelete.value) return;
    deletingStaff.value = true;
    router.delete(route('admin.staff-members.destroy', staffToDelete.value.id), {
        onFinish: () => {
            deletingStaff.value = false;
            showDeleteConfirm.value = false;
            staffToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Staff & Personnel Management - Pirgacha Internet" />

    <AdminLayout title="Staff Management">
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span>Staff Management & Access Control</span>
                        <span class="rounded-lg bg-brand-sky/20 border border-brand-sky/40 px-2.5 py-0.5 text-xs font-bold text-brand-sky">
                            Personnel
                        </span>
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">
                        Manage field operators, technicians, and administrators. Grant role-based portal access and control account status.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="openAddModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber hover:opacity-95 px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-brand-orange/20 transition active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New Staff
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                <button
                    @click="setStatusFilter('')"
                    class="text-left rounded-2xl border transition-all p-4 backdrop-blur-sm"
                    :class="selectedStatus === '' ? 'border-brand-sky bg-brand-sky/10 shadow-lg shadow-brand-sky/10' : 'border-brand-navy/60 bg-[#0B1E36]/80 hover:border-brand-navy'"
                >
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Personnel</div>
                    <div class="text-2xl font-black text-white mt-1">{{ totalStaffCount }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">Authorized platform operators</div>
                </button>
                <button
                    @click="setStatusFilter('active')"
                    class="text-left rounded-2xl border transition-all p-4 backdrop-blur-sm"
                    :class="selectedStatus === 'active' ? 'border-emerald-400 bg-emerald-950/40 shadow-lg shadow-emerald-500/10' : 'border-emerald-500/30 bg-emerald-950/20 hover:border-emerald-500/50'"
                >
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Active Staff</div>
                    <div class="text-2xl font-black text-emerald-400 mt-1">{{ activeStaffCount }}</div>
                    <div class="text-[11px] text-emerald-500/80 mt-1">Can log in & perform actions</div>
                </button>
                <button
                    @click="setStatusFilter('inactive')"
                    class="text-left rounded-2xl border transition-all p-4 backdrop-blur-sm"
                    :class="selectedStatus === 'inactive' ? 'border-rose-400 bg-rose-950/40 shadow-lg shadow-rose-500/10' : 'border-rose-500/30 bg-rose-950/20 hover:border-rose-500/50'"
                >
                    <div class="text-[11px] font-bold uppercase tracking-wider text-rose-400">Deactivated</div>
                    <div class="text-2xl font-black text-rose-400 mt-1">{{ inactiveStaffCount }}</div>
                    <div class="text-[11px] text-rose-400/80 mt-1">Blocked from login & operations</div>
                </button>
            </div>

            <!-- Search & Filter Controls -->
            <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between rounded-2xl border border-brand-navy/60 bg-[#0B1E36]/40 p-3">
                <div class="relative flex-1">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by name, phone, email, or username..."
                        class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 pl-9 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                    />
                    <svg class="absolute left-3 top-3 h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-3 top-2.5 text-xs text-slate-400 hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedStatus"
                        @change="executeSearch"
                        class="w-full sm:w-auto rounded-xl border border-brand-navy bg-[#071322] px-3 py-2.5 text-xs text-slate-200 focus:border-brand-sky focus:outline-none"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>

                    <button
                        v-if="searchQuery || selectedStatus"
                        @click="resetFilters"
                        class="rounded-xl border border-rose-500/30 bg-rose-950/20 px-3.5 py-2.5 text-xs font-bold text-rose-400 hover:bg-rose-950/40 transition shrink-0"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Staff Members - Desktop Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-hidden rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/60 shadow-xl backdrop-blur-md">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-brand-navy/80 bg-[#071322]/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3.5">Staff Member</th>
                            <th class="px-5 py-3.5">Contact Info</th>
                            <th class="px-5 py-3.5">Assigned Role</th>
                            <th class="px-5 py-3.5">Today Collections</th>
                            <th class="px-5 py-3.5">Tickets</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-navy/50 text-xs">
                        <tr v-for="staff in staffMembers" :key="staff.id" class="hover:bg-brand-navy/30 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-bold text-white text-sm">{{ staff.name }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Username: <span class="font-mono text-brand-sky">{{ staff.username || '—' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-slate-200 font-mono">{{ staff.phone || '—' }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ staff.email }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    v-if="staff.is_admin"
                                    class="inline-flex items-center gap-1 rounded-md border border-brand-orange/40 bg-brand-orange/10 px-2.5 py-1 text-[10px] font-black text-brand-orange uppercase"
                                >
                                    Admin
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-md border border-brand-sky/40 bg-brand-sky/10 px-2.5 py-1 text-[10px] font-bold text-brand-sky uppercase"
                                >
                                    Staff / Field Op
                                </span>
                            </td>
                            <td class="px-5 py-4 font-bold">
                                <span v-if="staff.today_collections > 0" class="text-emerald-400 font-mono">
                                    ৳{{ staff.today_collections.toLocaleString() }}
                                </span>
                                <span v-else class="text-slate-500 font-mono">৳0.00</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-md bg-brand-navy px-2 py-0.5 text-xs text-slate-300 font-bold">
                                    {{ staff.assigned_complaints }} open
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    :class="[
                                        staff.status === 'active'
                                            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400'
                                             : 'border-rose-500/40 bg-rose-500/10 text-rose-400',
                                        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold capitalize'
                                    ]"
                                >
                                    <span :class="['h-1.5 w-1.5 rounded-full', staff.status === 'active' ? 'bg-emerald-400' : 'bg-rose-400']"></span>
                                    {{ staff.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <!-- Toggle Active/Inactive -->
                                    <button
                                        @click="openToggleStaffStatus(staff)"
                                        :title="staff.status === 'active' ? 'Deactivate Access' : 'Activate Access'"
                                        class="p-1.5 rounded-lg border text-xs font-semibold transition"
                                        :class="staff.status === 'active' 
                                            ? 'border-amber-500/30 text-amber-400 hover:bg-amber-500/10' 
                                            : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10'"
                                    >
                                        {{ staff.status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>

                                    <!-- Edit Staff -->
                                    <button
                                        @click="openEditModal(staff)"
                                        class="p-1.5 rounded-lg border border-brand-navy text-slate-300 hover:text-white hover:bg-brand-navy transition"
                                        title="Edit Profile"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Delete Staff -->
                                    <button
                                        @click="openDeleteStaff(staff)"
                                        class="p-1.5 rounded-lg border border-rose-500/20 text-rose-400 hover:bg-rose-500/10 transition"
                                        title="Remove Staff"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!staffMembers?.length">
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                No staff personnel found matching your filter criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Staff Members - Mobile Cards View (Visible on mobile/tablet screens) -->
            <div class="block md:hidden space-y-3">
                <div
                    v-for="staff in staffMembers"
                    :key="'mobile-' + staff.id"
                    class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/80 p-4 shadow-lg backdrop-blur-md space-y-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="text-sm font-black text-white">{{ staff.name }}</div>
                            <div class="text-xs text-brand-sky font-mono mt-0.5">@{{ staff.username || 'no-username' }}</div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                v-if="staff.is_admin"
                                class="rounded-md border border-brand-orange/40 bg-brand-orange/10 px-2 py-0.5 text-[9px] font-black text-brand-orange uppercase"
                            >
                                Admin
                            </span>
                            <span
                                v-else
                                class="rounded-md border border-brand-sky/40 bg-brand-sky/10 px-2 py-0.5 text-[9px] font-bold text-brand-sky uppercase"
                            >
                                Staff
                            </span>
                            <span
                                :class="[
                                    staff.status === 'active'
                                        ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400'
                                        : 'border-rose-500/40 bg-rose-500/10 text-rose-400',
                                    'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold capitalize'
                                ]"
                            >
                                <span :class="['h-1 w-1 rounded-full', staff.status === 'active' ? 'bg-emerald-400' : 'bg-rose-400']"></span>
                                {{ staff.status }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs py-2 border-y border-brand-navy/60">
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-400">Contact</div>
                            <div class="text-slate-200 font-mono mt-0.5">{{ staff.phone || '—' }}</div>
                            <div class="text-[10px] text-slate-400 truncate">{{ staff.email }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-400">Today Collections</div>
                            <div class="font-bold font-mono mt-0.5" :class="staff.today_collections > 0 ? 'text-emerald-400' : 'text-slate-500'">
                                ৳{{ staff.today_collections.toLocaleString() }}
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5">
                                {{ staff.assigned_complaints }} open tickets
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button
                            @click="openToggleStaffStatus(staff)"
                            class="px-3 py-1.5 rounded-lg border text-xs font-bold transition"
                            :class="staff.status === 'active' 
                                ? 'border-amber-500/30 text-amber-400 hover:bg-amber-500/10' 
                                : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10'"
                        >
                            {{ staff.status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button
                            @click="openEditModal(staff)"
                            class="px-3 py-1.5 rounded-lg border border-brand-navy bg-[#071322] text-xs font-bold text-slate-200 hover:text-white hover:bg-brand-navy transition"
                        >
                            Edit
                        </button>
                        <button
                            @click="openDeleteStaff(staff)"
                            class="px-3 py-1.5 rounded-lg border border-rose-500/20 text-xs font-bold text-rose-400 hover:bg-rose-500/10 transition"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <div v-if="!staffMembers?.length" class="rounded-2xl border border-brand-navy/80 bg-[#0B1E36]/40 p-8 text-center text-slate-400 text-xs">
                    No staff personnel found matching your filter criteria.
                </div>
            </div>

            <!-- Add Staff Modal -->
            <div v-if="isAddModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md overflow-y-auto">
                <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border border-brand-navy bg-[#0B1E36] p-5 sm:p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-4 border-b border-brand-navy">
                        <h2 class="text-base sm:text-lg font-black text-white">Create New Staff Member</h2>
                        <button @click="isAddModalOpen = false" class="text-slate-400 hover:text-white p-1">✕</button>
                    </div>

                    <form @submit.prevent="submitAddStaff" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Full Name *</label>
                            <input
                                v-model="addForm.name"
                                type="text"
                                required
                                placeholder="e.g. Zahid Hasan (Field Tech)"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                            />
                            <div v-if="addForm.errors.name" class="text-rose-400 text-[11px] mt-1">{{ addForm.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Mobile Phone *</label>
                                <input
                                    v-model="addForm.phone"
                                    type="text"
                                    required
                                    placeholder="017XXXXXXXX"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                                />
                                <div v-if="addForm.errors.phone" class="text-rose-400 text-[11px] mt-1">{{ addForm.errors.phone }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Email Address *</label>
                                <input
                                    v-model="addForm.email"
                                    type="email"
                                    required
                                    placeholder="tech@pirgacha.net"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                                />
                                <div v-if="addForm.errors.email" class="text-rose-400 text-[11px] mt-1">{{ addForm.errors.email }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Staff Username</label>
                                <input
                                    v-model="addForm.username"
                                    type="text"
                                    placeholder="Optional (e.g. zahid_tech)"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                                />
                                <div v-if="addForm.errors.username" class="text-rose-400 text-[11px] mt-1">{{ addForm.errors.username }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Login Password *</label>
                                <input
                                    v-model="addForm.password"
                                    type="password"
                                    required
                                    placeholder="Min 6 characters"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                                />
                                <div v-if="addForm.errors.password" class="text-rose-400 text-[11px] mt-1">{{ addForm.errors.password }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Role *</label>
                                <select
                                    v-model="addForm.role"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                                >
                                    <option value="staff">Staff / Field Technician</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Status *</label>
                                <select
                                    v-model="addForm.status"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                                >
                                    <option value="active">Active (Can Login)</option>
                                    <option value="inactive">Inactive (Blocked)</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-brand-navy">
                            <button
                                type="button"
                                @click="isAddModalOpen = false"
                                class="px-4 py-2 rounded-xl border border-brand-navy bg-[#071322] text-xs font-bold text-slate-300 hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="addForm.processing"
                                class="px-5 py-2 rounded-xl bg-brand-orange text-xs font-black text-white hover:opacity-90 shadow-md shadow-brand-orange/20"
                            >
                                {{ addForm.processing ? 'Creating...' : 'Create Staff Member' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Staff Modal -->
            <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md overflow-y-auto">
                <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border border-brand-navy bg-[#0B1E36] p-5 sm:p-6 shadow-2xl">
                    <div class="flex items-center justify-between pb-4 border-b border-brand-navy">
                        <h2 class="text-base sm:text-lg font-black text-white">Edit Staff Member: {{ editingStaff?.name }}</h2>
                        <button @click="isEditModalOpen = false" class="text-slate-400 hover:text-white p-1">✕</button>
                    </div>

                    <form @submit.prevent="submitEditStaff" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Full Name *</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                            />
                            <div v-if="editForm.errors.name" class="text-rose-400 text-[11px] mt-1">{{ editForm.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Mobile Phone *</label>
                                <input
                                    v-model="editForm.phone"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                                />
                                <div v-if="editForm.errors.phone" class="text-rose-400 text-[11px] mt-1">{{ editForm.errors.phone }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Email Address *</label>
                                <input
                                    v-model="editForm.email"
                                    type="email"
                                    required
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                                />
                                <div v-if="editForm.errors.email" class="text-rose-400 text-[11px] mt-1">{{ editForm.errors.email }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Staff Username</label>
                                <input
                                    v-model="editForm.username"
                                    type="text"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                                />
                                <div v-if="editForm.errors.username" class="text-rose-400 text-[11px] mt-1">{{ editForm.errors.username }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Reset Password</label>
                                <input
                                    v-model="editForm.password"
                                    type="password"
                                    placeholder="Leave blank to keep current"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                                />
                                <div v-if="editForm.errors.password" class="text-rose-400 text-[11px] mt-1">{{ editForm.errors.password }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Role *</label>
                                <select
                                    v-model="editForm.role"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                                >
                                    <option value="staff">Staff / Field Technician</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Status *</label>
                                <select
                                    v-model="editForm.status"
                                    class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                                >
                                    <option value="active">Active (Can Login)</option>
                                    <option value="inactive">Inactive (Blocked)</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-brand-navy">
                            <button
                                type="button"
                                @click="isEditModalOpen = false"
                                class="px-4 py-2 rounded-xl border border-brand-navy bg-[#071322] text-xs font-bold text-slate-300 hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-5 py-2 rounded-xl bg-brand-sky text-xs font-black text-white hover:opacity-90 shadow-md shadow-brand-sky/20"
                            >
                                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Professional Delete Confirmation Modal -->
            <ConfirmModal
                :show="showDeleteConfirm"
                :title="`Remove Staff Member: ${staffToDelete?.name || ''}`"
                :message="`Are you sure you want to completely remove staff member '${staffToDelete?.name}' (${staffToDelete?.email})? All associated portal access privileges will be permanently revoked.`"
                confirm-text="Remove Personnel"
                cancel-text="Keep Staff"
                type="danger"
                :processing="deletingStaff"
                @confirm="confirmDeleteStaff"
                @cancel="showDeleteConfirm = false; staffToDelete = null;"
            />

            <!-- Professional Status Toggle Confirmation Modal -->
            <ConfirmModal
                :show="showStatusConfirm"
                :title="`${staffToToggle?.status === 'active' ? 'Deactivate' : 'Activate'} Staff: ${staffToToggle?.name || ''}`"
                :message="`Are you sure you want to ${staffToToggle?.status === 'active' ? 'deactivate access for' : 'restore access for'} '${staffToToggle?.name}'? ${staffToToggle?.status === 'active' ? 'The staff member will be immediately blocked from logging into the staff portal and collecting payments.' : 'The staff member will regain access to field collections and customer search.'}`"
                :confirm-text="staffToToggle?.status === 'active' ? 'Deactivate Access' : 'Activate Access'"
                cancel-text="Cancel"
                :type="staffToToggle?.status === 'active' ? 'warning' : 'success'"
                :processing="togglingStatus"
                @confirm="confirmToggleStaffStatus"
                @cancel="showStatusConfirm = false; staffToToggle = null;"
            />
        </div>
    </AdminLayout>
</template>
