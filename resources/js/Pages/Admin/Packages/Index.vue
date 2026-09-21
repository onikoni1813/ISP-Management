<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    packages: {
        type: Array,
        default: () => [],
    },
    areas: {
        type: Array,
        default: () => [],
    },
});

const showPackageDeleteConfirm = ref(false);
const packageToDelete = ref(null);
const deletingPackage = ref(false);

const showAreaDeleteConfirm = ref(false);
const areaToDelete = ref(null);
const deletingArea = ref(false);

const activeTab = ref('packages'); // 'packages' | 'areas'

// --- SEARCH & FILTERS ---
const packageSearch = ref('');
const packageStatusFilter = ref('all');
const areaSearch = ref('');
const areaStatusFilter = ref('all');

// --- PRICE HISTORY MODAL STATE ---
const isHistoryModalOpen = ref(false);
const viewingPackageHistory = ref(null);

const openHistoryModal = (pkg) => {
    viewingPackageHistory.value = pkg;
    isHistoryModalOpen.value = true;
};

const closeHistoryModal = () => {
    isHistoryModalOpen.value = false;
    viewingPackageHistory.value = null;
};

// --- KPI COMPUTED STATS ---
const totalPackages = computed(() => props.packages.length);
const activePackagesCount = computed(() => props.packages.filter(p => p.status === 'active').length);
const totalSubscribers = computed(() => props.packages.reduce((sum, p) => sum + (Number(p.connections_count) || 0), 0));
const totalRevenuePotential = computed(() => {
    return props.packages.reduce((sum, p) => {
        const price = Number(p.current_price?.price) || 0;
        const subs = Number(p.connections_count) || 0;
        return sum + (price * subs);
    }, 0);
});

const totalAreas = computed(() => props.areas.length);
const rootAreasCount = computed(() => props.areas.filter(a => !a.parent_id).length);
const totalAreaConnections = computed(() => props.areas.reduce((sum, a) => sum + (Number(a.connections_count) || 0), 0));

// Filtered Lists
const filteredPackages = computed(() => {
    return props.packages.filter(pkg => {
        const matchesStatus = packageStatusFilter.value === 'all' || pkg.status === packageStatusFilter.value;
        const q = packageSearch.value.trim().toLowerCase();
        if (!q) return matchesStatus;
        const matchesQuery = 
            (pkg.name && pkg.name.toLowerCase().includes(q)) ||
            (pkg.code && pkg.code.toLowerCase().includes(q)) ||
            (pkg.speed_mbps && pkg.speed_mbps.toString().includes(q)) ||
            (pkg.description && pkg.description.toLowerCase().includes(q));
        return matchesStatus && matchesQuery;
    });
});

const filteredAreas = computed(() => {
    return props.areas.filter(area => {
        const matchesStatus = areaStatusFilter.value === 'all' || area.status === areaStatusFilter.value;
        const q = areaSearch.value.trim().toLowerCase();
        if (!q) return matchesStatus;
        const matchesQuery = 
            (area.name && area.name.toLowerCase().includes(q)) ||
            (area.code && area.code.toLowerCase().includes(q)) ||
            (area.parent?.name && area.parent.name.toLowerCase().includes(q)) ||
            (area.description && area.description.toLowerCase().includes(q));
        return matchesStatus && matchesQuery;
    });
});

// --- PACKAGE MODAL & FORM STATE ---
const isPackageModalOpen = ref(false);
const editingPackage = ref(null);

const defaultFeatures = [
    { name: 'YouTube', value: '100 Mbps', enabled: true },
    { name: 'Facebook', value: 'Unlimited', enabled: true },
    { name: 'BDIX Cache', value: '100 Mbps', enabled: true },
    { name: 'IMO / WhatsApp', value: 'Unlimited', enabled: true },
    { name: 'TikTok', value: 'Unlimited', enabled: false },
    { name: 'Gaming Ping', value: 'Ultra-Low (<5ms)', enabled: true },
];

const packageForm = useForm({
    name: '',
    code: '',
    speed_mbps: 10,
    recommended_devices: '',
    price: 500,
    validity_days: 30,
    status: 'active',
    description: '',
    features: JSON.parse(JSON.stringify(defaultFeatures)),
});

const openCreatePackageModal = () => {
    editingPackage.value = null;
    packageForm.reset();
    packageForm.clearErrors();
    packageForm.status = 'active';
    packageForm.validity_days = 30;
    packageForm.recommended_devices = '3 - 5 Devices';
    packageForm.features = JSON.parse(JSON.stringify(defaultFeatures));
    isPackageModalOpen.value = true;
};

const openEditPackageModal = (pkg) => {
    editingPackage.value = pkg;
    packageForm.clearErrors();
    packageForm.name = pkg.name;
    packageForm.code = pkg.code;
    packageForm.speed_mbps = pkg.speed_mbps;
    packageForm.recommended_devices = pkg.recommended_devices || '';
    packageForm.price = pkg.current_price?.price ? Number(pkg.current_price.price) : 500;
    packageForm.validity_days = pkg.current_price?.validity_days ? Number(pkg.current_price.validity_days) : 30;
    packageForm.status = pkg.status;
    packageForm.description = pkg.description || '';
    
    // Merge existing or default features
    if (Array.isArray(pkg.features) && pkg.features.length > 0) {
        packageForm.features = JSON.parse(JSON.stringify(pkg.features));
    } else {
        packageForm.features = JSON.parse(JSON.stringify(defaultFeatures));
    }
    
    isPackageModalOpen.value = true;
};

const addCustomFeature = () => {
    packageForm.features.push({
        name: '',
        value: 'Unlimited',
        enabled: true
    });
};

const removeFeature = (index) => {
    packageForm.features.splice(index, 1);
};

const closePackageModal = () => {
    isPackageModalOpen.value = false;
    editingPackage.value = null;
    packageForm.reset();
};

const submitPackage = () => {
    if (editingPackage.value) {
        packageForm.patch(route('admin.packages.update', editingPackage.value.id), {
            onSuccess: () => closePackageModal(),
        });
    } else {
        packageForm.post(route('admin.packages.store'), {
            onSuccess: () => closePackageModal(),
        });
    }
};

const openDeletePackageModal = (pkg) => {
    packageToDelete.value = pkg;
    showPackageDeleteConfirm.value = true;
};

const confirmDeletePackage = () => {
    if (!packageToDelete.value) return;
    deletingPackage.value = true;
    router.delete(route('admin.packages.destroy', packageToDelete.value.id), {
        onFinish: () => {
            deletingPackage.value = false;
            showPackageDeleteConfirm.value = false;
            packageToDelete.value = null;
        }
    });
};

// --- AREA MODAL & FORM STATE ---
const isAreaModalOpen = ref(false);
const editingArea = ref(null);

const areaForm = useForm({
    parent_id: '',
    name: '',
    code: '',
    status: 'active',
    description: '',
});

const openCreateAreaModal = () => {
    editingArea.value = null;
    areaForm.reset();
    areaForm.clearErrors();
    areaForm.status = 'active';
    areaForm.parent_id = '';
    isAreaModalOpen.value = true;
};

const openEditAreaModal = (area) => {
    editingArea.value = area;
    areaForm.clearErrors();
    areaForm.parent_id = area.parent_id || '';
    areaForm.name = area.name;
    areaForm.code = area.code;
    areaForm.status = area.status;
    areaForm.description = area.description || '';
    isAreaModalOpen.value = true;
};

const closeAreaModal = () => {
    isAreaModalOpen.value = false;
    editingArea.value = null;
    areaForm.reset();
};

const submitArea = () => {
    if (editingArea.value) {
        areaForm.patch(route('admin.areas.update', editingArea.value.id), {
            onSuccess: () => closeAreaModal(),
        });
    } else {
        areaForm.post(route('admin.areas.store'), {
            onSuccess: () => closeAreaModal(),
        });
    }
};

const openDeleteAreaModal = (area) => {
    areaToDelete.value = area;
    showAreaDeleteConfirm.value = true;
};

const confirmDeleteArea = () => {
    if (!areaToDelete.value) return;
    deletingArea.value = true;
    router.delete(route('admin.areas.destroy', areaToDelete.value.id), {
        onFinish: () => {
            deletingArea.value = false;
            showAreaDeleteConfirm.value = false;
            areaToDelete.value = null;
        }
    });
};

// Format Date Utility
const formatDate = (dateString) => {
    if (!dateString) return 'Current';
    const d = new Date(dateString);
    return isNaN(d.getTime()) ? dateString : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Packages & Coverage Areas - Pirgacha Internet" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header with Action Buttons -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <Link :href="route('admin.dashboard')" class="hover:text-white transition">Admin</Link>
                        <span>/</span>
                        <span class="text-slate-200">Catalog & Network</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white mt-1">Packages & Coverage Areas</h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Configure broadband bandwidth tiers, monthly prices, and geographical fiber distribution zones.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        v-if="activeTab === 'packages'"
                        @click="openCreatePackageModal"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber hover:from-brand-amber hover:to-brand-gold px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-brand-orange/25 hover:shadow-brand-orange/40 transition active:scale-95 cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New Package
                    </button>
                    <button
                        v-if="activeTab === 'areas'"
                        @click="openCreateAreaModal"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue hover:from-brand-cyan hover:to-brand-sky px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-brand-sky/25 hover:shadow-brand-sky/40 transition active:scale-95 cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Coverage Area
                    </button>
                </div>
            </div>

            <!-- KPI Metric Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/90 p-4 shadow-lg flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Total Packages</span>
                        <span class="text-2xl font-mono font-black text-white mt-1 block">{{ totalPackages }}</span>
                        <span class="text-[10px] text-emerald-400 font-bold mt-0.5 inline-block">{{ activePackagesCount }} Active</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-brand-orange/15 border border-brand-orange/30 flex items-center justify-center text-brand-orange text-lg">
                        📦
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/90 p-4 shadow-lg flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Total Subscribers</span>
                        <span class="text-2xl font-mono font-black text-brand-sky mt-1 block">{{ totalSubscribers }}</span>
                        <span class="text-[10px] text-slate-400 mt-0.5 inline-block">Active Lines</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-brand-sky/15 border border-brand-sky/30 flex items-center justify-center text-brand-sky text-lg">
                        👥
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/90 p-4 shadow-lg flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Monthly Potential</span>
                        <span class="text-2xl font-mono font-black text-emerald-400 mt-1 block">৳{{ totalRevenuePotential.toLocaleString() }}</span>
                        <span class="text-[10px] text-slate-400 mt-0.5 inline-block">Gross MRR</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg">
                        ৳
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/90 p-4 shadow-lg flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Coverage Zones</span>
                        <span class="text-2xl font-mono font-black text-white mt-1 block">{{ totalAreas }}</span>
                        <span class="text-[10px] text-brand-orange font-bold mt-0.5 inline-block">{{ rootAreasCount }} Major Hubs</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-purple-400 text-lg">
                        📍
                    </div>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="flex border-b border-brand-navy space-x-6">
                <button
                    @click="activeTab = 'packages'"
                    type="button"
                    :class="[
                        activeTab === 'packages'
                            ? 'border-brand-orange text-brand-orange'
                            : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700',
                        'pb-3 border-b-2 font-bold text-sm flex items-center gap-2 transition cursor-pointer'
                    ]"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Internet Packages
                    <span class="rounded-full bg-brand-navy px-2 py-0.5 text-[10px] text-slate-300 font-mono">{{ packages.length }}</span>
                </button>

                <button
                    @click="activeTab = 'areas'"
                    type="button"
                    :class="[
                        activeTab === 'areas'
                            ? 'border-brand-sky text-brand-sky'
                            : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700',
                        'pb-3 border-b-2 font-bold text-sm flex items-center gap-2 transition cursor-pointer'
                    ]"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Coverage Areas & Zones
                    <span class="rounded-full bg-brand-navy px-2 py-0.5 text-[10px] text-slate-300 font-mono">{{ areas.length }}</span>
                </button>
            </div>

            <!-- TAB 1: PACKAGES LIST -->
            <div v-if="activeTab === 'packages'" class="space-y-6">
                <!-- Search & Filters -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-[#081729] p-3 rounded-2xl border border-brand-navy">
                    <div class="relative w-full sm:w-80">
                        <span class="absolute left-3.5 top-2.5 text-slate-400 text-xs">🔍</span>
                        <input
                            v-model="packageSearch"
                            type="text"
                            placeholder="Search packages by name, code or speed..."
                            class="w-full pl-9 pr-8 py-2 rounded-xl bg-[#061220] border border-brand-navy text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                        />
                        <button
                            v-if="packageSearch"
                            @click="packageSearch = ''"
                            type="button"
                            class="absolute right-2.5 top-2 text-slate-400 hover:text-white text-xs"
                        >✕</button>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <span class="text-xs text-slate-400 hidden sm:inline">Status:</span>
                        <select
                            v-model="packageStatusFilter"
                            class="rounded-xl border border-brand-navy bg-[#061220] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                        >
                            <option value="all">All Packages ({{ packages.length }})</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive Only</option>
                        </select>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredPackages.length === 0" class="text-center py-16 rounded-2xl border border-dashed border-brand-navy bg-[#091A2E]/50 p-8 space-y-3">
                    <div class="text-3xl">📦</div>
                    <h3 class="text-base font-bold text-white">No packages found</h3>
                    <p class="text-xs text-slate-400">Try adjusting your search criteria or add a new broadband package.</p>
                    <button
                        @click="packageSearch = ''; packageStatusFilter = 'all'"
                        class="px-4 py-2 rounded-xl bg-brand-navy text-xs font-bold text-white hover:bg-brand-navy/80 transition"
                    >
                        Reset Filter
                    </button>
                </div>

                <!-- Cards Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    <div
                        v-for="pkg in filteredPackages"
                        :key="pkg.id"
                        class="rounded-2xl border border-brand-navy bg-[#091A2E]/90 p-5 flex flex-col justify-between hover:border-brand-sky/50 transition-all duration-200 shadow-lg relative group"
                    >
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-mono font-bold text-brand-sky tracking-wider">{{ pkg.code }}</span>
                                <div class="flex items-center gap-1.5">
                                    <span 
                                        :class="[
                                            pkg.status === 'active' 
                                                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' 
                                                : 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                                            'rounded-full px-2 py-0.5 text-[10px] font-semibold border capitalize'
                                        ]"
                                    >
                                        {{ pkg.status }}
                                    </span>
                                </div>
                            </div>

                            <h3 class="text-lg font-black text-white mt-2">{{ pkg.name }}</h3>
                            <p class="text-xs text-slate-400 mt-1 line-clamp-2">{{ pkg.description || 'Optical fiber broadband connection tier.' }}</p>

                            <!-- Speed & Price Badges -->
                            <div class="mt-4 p-3 rounded-xl bg-[#071322] border border-brand-navy/80 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-slate-400 uppercase font-semibold block">Bandwidth</span>
                                    <span class="text-xl font-mono font-black text-brand-sky">{{ pkg.speed_mbps }} <span class="text-xs font-normal text-slate-400">Mbps</span></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-slate-400 uppercase font-semibold block">Monthly Charge</span>
                                    <span class="text-xl font-mono font-black text-brand-orange">৳{{ Number(pkg.current_price?.price || 0) }}</span>
                                </div>
                            </div>

                            <!-- Metrics -->
                            <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                                <span>Validity: <strong class="text-slate-200">{{ pkg.current_price?.validity_days || 30 }} days</strong></span>
                                <span>Subscribers: <strong class="text-brand-orange">{{ pkg.connections_count || 0 }}</strong></span>
                            </div>

                            <div v-if="pkg.recommended_devices" class="mt-2 text-[11px] text-slate-300 flex items-center gap-1.5">
                                <span class="text-brand-sky font-bold">📱 Devices:</span>
                                <span class="font-semibold text-emerald-400">{{ pkg.recommended_devices }}</span>
                            </div>

                            <!-- Social & Bandwidth Feature Tags in Admin Card -->
                            <div v-if="pkg.features && pkg.features.length > 0" class="mt-3 pt-3 border-t border-brand-navy/60 flex flex-wrap gap-1.5">
                                <span
                                    v-for="(f, fIndex) in pkg.features.filter(item => item.enabled)"
                                    :key="fIndex"
                                    class="inline-flex items-center gap-1 rounded-md bg-[#0B2038] border border-brand-sky/20 px-2 py-0.5 text-[10px] font-medium text-slate-300"
                                >
                                    <span class="text-brand-orange font-bold font-mono">{{ f.name }}:</span>
                                    <span class="text-brand-sky font-semibold">{{ f.value }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons & Price History Trigger -->
                        <div class="mt-5 pt-4 border-t border-brand-navy flex items-center justify-between gap-2">
                            <!-- Price History Trigger -->
                            <button
                                @click="openHistoryModal(pkg)"
                                type="button"
                                class="p-2 rounded-xl bg-[#071322] hover:bg-brand-navy text-slate-300 hover:text-white transition border border-brand-navy"
                                title="View Price Revision History"
                            >
                                <svg class="h-4 w-4 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>

                            <button
                                @click="openEditPackageModal(pkg)"
                                type="button"
                                class="flex-1 rounded-xl bg-brand-navy/80 hover:bg-brand-sky hover:text-white px-3 py-2 text-xs font-bold text-slate-200 transition border border-brand-navy flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Package
                            </button>

                            <button
                                @click="openDeletePackageModal(pkg)"
                                type="button"
                                :disabled="pkg.connections_count > 0"
                                :class="[
                                    pkg.connections_count > 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-rose-500/20 text-rose-400 border-rose-500/30 cursor-pointer',
                                    'p-2 rounded-xl border border-transparent transition'
                                ]"
                                :title="pkg.connections_count > 0 ? 'Package has active connections and cannot be deleted' : 'Delete Package'"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: COVERAGE AREAS LIST -->
            <div v-if="activeTab === 'areas'" class="space-y-6">
                <!-- Search & Filters -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-[#081729] p-3 rounded-2xl border border-brand-navy">
                    <div class="relative w-full sm:w-80">
                        <span class="absolute left-3.5 top-2.5 text-slate-400 text-xs">🔍</span>
                        <input
                            v-model="areaSearch"
                            type="text"
                            placeholder="Search zones by name, code or parent..."
                            class="w-full pl-9 pr-8 py-2 rounded-xl bg-[#061220] border border-brand-navy text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                        />
                        <button
                            v-if="areaSearch"
                            @click="areaSearch = ''"
                            type="button"
                            class="absolute right-2.5 top-2 text-slate-400 hover:text-white text-xs"
                        >✕</button>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <span class="text-xs text-slate-400 hidden sm:inline">Status:</span>
                        <select
                            v-model="areaStatusFilter"
                            class="rounded-xl border border-brand-navy bg-[#061220] px-3 py-2 text-xs text-white focus:border-brand-sky focus:outline-none"
                        >
                            <option value="all">All Zones ({{ areas.length }})</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive Only</option>
                        </select>
                    </div>
                </div>

                <!-- Table View -->
                <div class="overflow-hidden rounded-2xl border border-brand-navy bg-[#091A2E]/80 backdrop-blur-sm shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-300">
                            <thead class="border-b border-brand-navy bg-[#071322] text-xs uppercase font-semibold text-slate-400">
                                <tr>
                                    <th class="px-5 py-4">Area / Zone Name</th>
                                    <th class="px-5 py-4">Code</th>
                                    <th class="px-5 py-4">Parent Zone</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th class="px-5 py-4">Customers</th>
                                    <th class="px-5 py-4">Connections</th>
                                    <th class="px-5 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-navy/60">
                                <tr v-for="area in filteredAreas" :key="area.id" class="hover:bg-brand-navy/30 transition">
                                    <td class="px-5 py-4 font-bold text-white">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-brand-orange"></span>
                                            <span>{{ area.name }}</span>
                                        </div>
                                        <div v-if="area.description" class="text-xs text-slate-400 mt-0.5">{{ area.description }}</div>
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs font-semibold text-brand-sky">{{ area.code }}</td>
                                    <td class="px-5 py-4 text-xs text-slate-400">
                                        <span v-if="area.parent" class="rounded-lg bg-slate-800 px-2.5 py-1 text-slate-300 border border-slate-700">
                                            {{ area.parent.name }}
                                        </span>
                                        <span v-else class="text-slate-500 italic">Root Zone</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            :class="[
                                                area.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                                                'rounded-full px-2.5 py-0.5 text-xs font-semibold border capitalize'
                                            ]"
                                        >
                                            {{ area.status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-mono font-bold text-white">{{ area.customers_count || 0 }}</td>
                                    <td class="px-5 py-4 font-mono font-bold text-brand-sky">{{ area.connections_count || 0 }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                @click="openEditAreaModal(area)"
                                                class="rounded-xl border border-brand-navy bg-[#0B1E36] p-2 text-xs font-semibold text-brand-sky hover:bg-[#102B4D] hover:text-white transition"
                                                title="Edit Area"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="openDeleteAreaModal(area)"
                                                :disabled="(area.customers_count || 0) > 0"
                                                :class="[
                                                    (area.customers_count || 0) > 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-rose-500/20 text-rose-400 border-rose-500/30',
                                                    'rounded-xl border border-transparent p-2 transition'
                                                ]"
                                                title="Delete Area"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredAreas.length === 0">
                                    <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                                        No coverage areas found matching your query.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PACKAGE MODAL (CREATE / EDIT) -->
        <div v-if="isPackageModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="closePackageModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-3xl border border-brand-navy bg-[#091A2E] p-4 sm:p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-brand-navy pb-4">
                    <div>
                        <h2 class="text-lg font-black text-white">
                            {{ editingPackage ? 'Edit Package: ' + editingPackage.name : 'Create New Internet Package' }}
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Define speed quota, monthly charge rate, and active status.</p>
                    </div>
                    <button @click="closePackageModal" type="button" class="rounded-xl p-1 text-slate-400 hover:text-white hover:bg-brand-navy/60 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitPackage" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Package Name *</label>
                            <input
                                v-model="packageForm.name"
                                type="text"
                                required
                                placeholder="e.g. Starter 10 Mbps"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                            <div v-if="packageForm.errors.name" class="text-[11px] text-rose-400 mt-1">{{ packageForm.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Package Code *</label>
                            <input
                                v-model="packageForm.code"
                                type="text"
                                required
                                placeholder="e.g. PKG-10M"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs font-mono uppercase text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                            <div v-if="packageForm.errors.code" class="text-[11px] text-rose-400 mt-1">{{ packageForm.errors.code }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Speed (Mbps) *</label>
                            <input
                                v-model.number="packageForm.speed_mbps"
                                type="number"
                                min="1"
                                required
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs font-mono text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                            <div v-if="packageForm.errors.speed_mbps" class="text-[11px] text-rose-400 mt-1">{{ packageForm.errors.speed_mbps }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Devices Supported</label>
                            <input
                                v-model="packageForm.recommended_devices"
                                type="text"
                                placeholder="e.g. 3-5 Devices"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                            <div v-if="packageForm.errors.recommended_devices" class="text-[11px] text-rose-400 mt-1">{{ packageForm.errors.recommended_devices }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Price (BDT) *</label>
                            <input
                                v-model.number="packageForm.price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs font-mono text-brand-orange font-bold focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                            <div v-if="packageForm.errors.price" class="text-[11px] text-rose-400 mt-1">{{ packageForm.errors.price }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Validity (Days) *</label>
                            <input
                                v-model.number="packageForm.validity_days"
                                type="number"
                                min="1"
                                required
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs font-mono text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                            <div v-if="packageForm.errors.validity_days" class="text-[11px] text-rose-400 mt-1">{{ packageForm.errors.validity_days }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Status *</label>
                        <select
                            v-model="packageForm.status"
                            class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                        >
                            <option value="active">Active (Available for customer subscription)</option>
                            <option value="inactive">Inactive / Archived</option>
                        </select>
                        <div v-if="packageForm.errors.status" class="text-[11px] text-rose-400 mt-1">{{ packageForm.errors.status }}</div>
                    </div>

                    <!-- Social Media, BDIX & Special Bandwidth Features -->
                    <div class="rounded-2xl border border-brand-navy bg-[#061220] p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold text-white uppercase tracking-wider">Social Media & Bandwidth Perks</label>
                                <p class="text-[11px] text-slate-400">Specify speed/unlimited caps (e.g. YouTube 100 Mbps, Facebook Unlimited, IMO, etc.)</p>
                            </div>
                            <button
                                type="button"
                                @click="addCustomFeature"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-brand-sky hover:text-white bg-brand-sky/10 border border-brand-sky/30 px-2.5 py-1 rounded-lg transition cursor-pointer"
                            >
                                + Add Perk
                            </button>
                        </div>

                        <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                            <div
                                v-for="(feat, fIdx) in packageForm.features"
                                :key="fIdx"
                                class="flex items-center gap-2 bg-[#091A2E] p-2.5 rounded-xl border border-brand-navy/80"
                            >
                                <input
                                    type="checkbox"
                                    v-model="feat.enabled"
                                    class="rounded bg-slate-800 border-slate-700 text-brand-orange focus:ring-brand-orange h-4 w-4"
                                    title="Show on Website"
                                />
                                <input
                                    type="text"
                                    v-model="feat.name"
                                    placeholder="e.g. YouTube, Facebook, IMO"
                                    class="flex-1 rounded-lg border border-brand-navy bg-[#061220] px-2.5 py-1.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                                />
                                <div class="relative w-36">
                                    <input
                                        type="text"
                                        v-model="feat.value"
                                        placeholder="100 Mbps / Unlimited"
                                        class="w-full rounded-lg border border-brand-navy bg-[#061220] px-2.5 py-1.5 text-xs font-semibold text-brand-orange placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                                    />
                                </div>
                                <button
                                    type="button"
                                    @click="removeFeature(fIdx)"
                                    class="text-slate-500 hover:text-rose-400 p-1 rounded-lg transition"
                                    title="Remove Perk"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 pt-1 text-[10px] text-slate-400">
                            <span class="text-slate-500">Quick presets:</span>
                            <button
                                type="button"
                                @click="packageForm.features.push({ name: 'IMO / WhatsApp', value: 'Unlimited', enabled: true })"
                                class="hover:text-brand-sky underline cursor-pointer"
                            >+ IMO Unlimited</button>
                            <button
                                type="button"
                                @click="packageForm.features.push({ name: 'Facebook', value: 'Unlimited', enabled: true })"
                                class="hover:text-brand-sky underline cursor-pointer"
                            >+ Facebook Unlimited</button>
                            <button
                                type="button"
                                @click="packageForm.features.push({ name: 'YouTube', value: '100 Mbps', enabled: true })"
                                class="hover:text-brand-sky underline cursor-pointer"
                            >+ YouTube 100M</button>
                            <button
                                type="button"
                                @click="packageForm.features.push({ name: 'BDIX Cache', value: '100 Mbps', enabled: true })"
                                class="hover:text-brand-sky underline cursor-pointer"
                            >+ BDIX 100M</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Description</label>
                        <textarea
                            v-model="packageForm.description"
                            rows="2"
                            placeholder="Optional package marketing highlights..."
                            class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-navy">
                        <button
                            type="button"
                            @click="closePackageModal"
                            class="rounded-xl border border-brand-navy bg-transparent px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="packageForm.processing"
                            class="rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber hover:opacity-95 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-brand-orange/20 transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ packageForm.processing ? 'Saving...' : (editingPackage ? 'Update Package' : 'Create Package') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- AREA MODAL (CREATE / EDIT) -->
        <div v-if="isAreaModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="closeAreaModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-3xl border border-brand-navy bg-[#091A2E] p-4 sm:p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-brand-navy pb-4">
                    <div>
                        <h2 class="text-lg font-black text-white">
                            {{ editingArea ? 'Edit Coverage Area: ' + editingArea.name : 'Add New Coverage Area' }}
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Create fiber coverage zones and hierarchical subdivisions.</p>
                    </div>
                    <button @click="closeAreaModal" type="button" class="rounded-xl p-1 text-slate-400 hover:text-white hover:bg-brand-navy/60 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitArea" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Area Name *</label>
                            <input
                                v-model="areaForm.name"
                                type="text"
                                required
                                placeholder="e.g. Pirgacha College Road"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                            <div v-if="areaForm.errors.name" class="text-[11px] text-rose-400 mt-1">{{ areaForm.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Area Code *</label>
                            <input
                                v-model="areaForm.code"
                                type="text"
                                required
                                placeholder="e.g. ZONE-COLLEGE"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs font-mono uppercase text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            />
                            <div v-if="areaForm.errors.code" class="text-[11px] text-rose-400 mt-1">{{ areaForm.errors.code }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Parent Zone (Optional)</label>
                            <select
                                v-model="areaForm.parent_id"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            >
                                <option value="">-- No Parent (Root Zone) --</option>
                                <option 
                                    v-for="a in areas.filter(x => !editingArea || x.id !== editingArea.id)" 
                                    :key="a.id" 
                                    :value="a.id"
                                >
                                    {{ a.name }} ({{ a.code }})
                                </option>
                            </select>
                            <div v-if="areaForm.errors.parent_id" class="text-[11px] text-rose-400 mt-1">{{ areaForm.errors.parent_id }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Status *</label>
                            <select
                                v-model="areaForm.status"
                                class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div v-if="areaForm.errors.status" class="text-[11px] text-rose-400 mt-1">{{ areaForm.errors.status }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Description</label>
                        <textarea
                            v-model="areaForm.description"
                            rows="3"
                            placeholder="Optional notes regarding this area, optical nodes, or splitters..."
                            class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-navy">
                        <button
                            type="button"
                            @click="closeAreaModal"
                            class="rounded-xl border border-brand-navy bg-transparent px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="areaForm.processing"
                            class="rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue hover:opacity-95 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-brand-sky/20 transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ areaForm.processing ? 'Saving...' : (editingArea ? 'Update Area' : 'Create Area') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- PRICE HISTORY MODAL -->
        <div v-if="isHistoryModalOpen && viewingPackageHistory" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="closeHistoryModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-brand-navy bg-[#091A2E] shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-brand-navy p-5 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-black text-white">Price History</h2>
                            <span class="rounded-md bg-brand-sky/10 border border-brand-sky/30 px-2 py-0.5 font-mono text-[11px] font-bold text-brand-sky">
                                {{ viewingPackageHistory.code }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">{{ viewingPackageHistory.name }} ({{ viewingPackageHistory.speed_mbps }} Mbps)</p>
                    </div>
                    <button @click="closeHistoryModal" type="button" class="rounded-xl p-1 text-slate-400 hover:text-white hover:bg-brand-navy/60 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 pt-0 space-y-3 max-h-[60vh] overflow-y-auto">
                    <div v-if="viewingPackageHistory.prices && viewingPackageHistory.prices.length > 0" class="space-y-2.5">
                        <div
                            v-for="(pVer, pIdx) in viewingPackageHistory.prices"
                            :key="pVer.id || pIdx"
                            class="p-3.5 rounded-2xl border flex items-center justify-between"
                            :class="[
                                pVer.status === 'active'
                                    ? 'border-emerald-500/40 bg-emerald-500/5'
                                    : 'border-brand-navy bg-[#071322]'
                            ]"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-base font-mono font-black text-white">৳{{ Number(pVer.price) }}</span>
                                    <span class="text-xs text-slate-400">/ {{ pVer.validity_days }} days</span>
                                    <span
                                        :class="[
                                            pVer.status === 'active'
                                                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                                                : 'bg-slate-700/30 text-slate-400 border-slate-700',
                                            'rounded-md px-2 py-0.5 text-[10px] font-bold border capitalize'
                                        ]"
                                    >
                                        {{ pVer.status }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                    <span>From: <strong class="text-slate-300 font-mono">{{ formatDate(pVer.effective_from) }}</strong></span>
                                    <span v-if="pVer.effective_to">To: <strong class="text-slate-300 font-mono">{{ formatDate(pVer.effective_to) }}</strong></span>
                                </div>
                            </div>

                            <span v-if="pVer.status === 'active'" class="text-xs font-bold text-emerald-400 flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                                Active Rate
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-slate-400 text-xs">
                        No historical pricing records found.
                    </div>
                </div>

                <div class="p-4 border-t border-brand-navy text-right">
                    <button
                        @click="closeHistoryModal"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-brand-navy text-xs font-bold text-white hover:bg-brand-navy/80 transition"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Professional Delete Package Modal -->
        <ConfirmModal
            :show="showPackageDeleteConfirm"
            :title="`Delete Internet Package: ${packageToDelete?.name || ''}`"
            :message="`Are you sure you want to delete package '${packageToDelete?.name}' (${packageToDelete?.code})? If this package has active customer subscriptions, deletion will be blocked by system integrity.`"
            confirm-text="Delete Package"
            cancel-text="Keep Package"
            type="danger"
            :processing="deletingPackage"
            @confirm="confirmDeletePackage"
            @cancel="showPackageDeleteConfirm = false; packageToDelete = null;"
        />

        <!-- Professional Delete Area Modal -->
        <ConfirmModal
            :show="showAreaDeleteConfirm"
            :title="`Delete Coverage Zone: ${areaToDelete?.name || ''}`"
            :message="`Are you sure you want to remove coverage area '${areaToDelete?.name}' (${areaToDelete?.code})? Areas with connected customer installations cannot be removed.`"
            confirm-text="Delete Coverage Zone"
            cancel-text="Keep Area"
            type="danger"
            :processing="deletingArea"
            @confirm="confirmDeleteArea"
            @cancel="showAreaDeleteConfirm = false; areaToDelete = null;"
        />
    </AdminLayout>
</template>
