<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { syncService } from '@/Services/syncService';
import { formatDateTime } from '@/Utils/date';
import axios from 'axios';

const props = defineProps({
    metrics: Object,
    today_handover: Object,
    recent_collections: Array,
    open_complaints: Array,
    areas: Array,
});

const page = usePage();
const user = page.props.auth.user;

// Clean staff display name (removes 'Field Technician' or 'Field' prefix if present)
const staffDisplayName = computed(() => {
    if (!user?.name) return 'Staff';
    return user.name
        .replace(/^Field\s+Technician\s+/i, '')
        .replace(/^Field\s+/i, '')
        .trim() || user.name;
});

// Read initial tab from URL query params (e.g., ?tab=complaints)
const urlParams = new URLSearchParams(window.location.search);
const initialTab = urlParams.get('tab') === 'complaints' ? 'complaints' : 'collections';

const activeTab = ref(initialTab); // 'collections' or 'complaints'
const selectedPeriod = ref('today'); // 'today', 'week', 'month'

const currentMetrics = computed(() => {
    const p = selectedPeriod.value;
    if (p === 'week') {
        return {
            collection: props.metrics?.week_collection || 0,
            count: props.metrics?.week_collection_count || 0,
            renewals: props.metrics?.week_renewals_count || 0,
            cash: props.metrics?.week_cash || 0,
            digital: props.metrics?.week_digital || 0,
        };
    }
    if (p === 'month') {
        return {
            collection: props.metrics?.month_collection || 0,
            count: props.metrics?.month_collection_count || 0,
            renewals: props.metrics?.month_renewals_count || 0,
            cash: props.metrics?.month_cash || 0,
            digital: props.metrics?.month_digital || 0,
        };
    }
    if (p === 'year') {
        return {
            collection: props.metrics?.year_collection || 0,
            count: props.metrics?.year_collection_count || 0,
            renewals: props.metrics?.year_renewals_count || 0,
            cash: props.metrics?.year_cash || 0,
            digital: props.metrics?.year_digital || 0,
        };
    }
    return {
        collection: props.metrics?.today_collection || 0,
        count: props.metrics?.today_collection_count || 0,
        renewals: props.metrics?.today_renewals_count || 0,
        cash: props.metrics?.today_cash || 0,
        digital: props.metrics?.today_digital || 0,
    };
});

// Search state
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const isOfflineResult = ref(false);
let debounceTimeout = null;

// Operational Filter tabs & state
const selectedArea = ref('');
const activeFilter = ref('due'); // 'due', 'paid', 'renewed', 'expiring_72h', 'expired'
const filterCounts = ref({
    all: 0,
    due: 0,
    paid: 0,
    renewed: 0,
    expiring_72h: 0,
    expired: 0,
});
const filteredCustomersList = ref([]);
const isFiltering = ref(false);

watch(searchQuery, (newVal) => {
    clearTimeout(debounceTimeout);
    if (!newVal || newVal.trim().length < 2) {
        searchResults.value = [];
        isOfflineResult.value = false;
        return;
    }

    isSearching.value = true;
    debounceTimeout = setTimeout(async () => {
        try {
            if (navigator.onLine) {
                const res = await axios.get(route('staff.api.search'), { params: { q: newVal } });
                searchResults.value = res.data;
                isOfflineResult.value = false;
            } else {
                const offlineMatches = await syncService.searchOfflineCustomers(newVal);
                searchResults.value = offlineMatches.map(c => ({
                    id: c.id,
                    name: c.name,
                    customer_code: c.customer_code,
                    primary_contact: { phone: c.phone },
                    connections: [{
                        current_package: { name: c.package_name },
                        pppoe_credential: { username: c.pppoe_username },
                    }],
                }));
                isOfflineResult.value = true;
            }
        } catch (e) {
            console.error('Search failed:', e);
        } finally {
            isSearching.value = false;
        }
    }, 300);
});

const loadFilteredCustomers = async () => {
    isFiltering.value = true;
    try {
        if (navigator.onLine) {
            const res = await axios.get(route('staff.api.filtered-customers'), {
                params: {
                    area_id: selectedArea.value || undefined,
                    filter: activeFilter.value,
                }
            });
            filteredCustomersList.value = res.data.customers || [];
            if (res.data.counts) {
                filterCounts.value = res.data.counts;
            }
        } else {
            // Offline Mode: Pull from local IndexedDB
            const { getAllFromStore } = await import('@/Services/offlineStorage');
            const localCustomers = await getAllFromStore('customers');
            let matched = localCustomers;
            
            if (selectedArea.value) {
                matched = matched.filter(c => c.area_name === selectedArea.value);
            }
            
            const todayStr = new Date().toISOString().slice(0, 10);
            if (activeFilter.value === 'due') {
                matched = matched.filter(c => Number(c.balance) > 0 || (c.expiry_date && c.expiry_date < todayStr));
            } else if (activeFilter.value === 'paid') {
                matched = matched.filter(c => Number(c.balance) <= 0 && (!c.expiry_date || c.expiry_date >= todayStr));
            }
            
            filteredCustomersList.value = matched.map(c => ({
                id: c.id,
                name: c.name,
                customer_code: c.customer_code,
                balance: c.balance,
                primary_contact: { phone: c.phone },
                connections: [{
                    current_package: { 
                        name: c.package_name, 
                        current_price: { price: c.monthly_rate } 
                    },
                    expiry_date: c.expiry_date,
                    pppoe_credential: { username: c.pppoe_username },
                }],
            }));
            
            filterCounts.value = {
                all: localCustomers.length,
                due: localCustomers.filter(c => Number(c.balance) > 0 || (c.expiry_date && c.expiry_date < todayStr)).length,
                paid: localCustomers.filter(c => Number(c.balance) <= 0 && (!c.expiry_date || c.expiry_date >= todayStr)).length,
                renewed: 0,
                expiring_72h: 0,
                expired: 0,
            };
        }
    } catch (error) {
        console.error('Filter fetch failed:', error);
    } finally {
        isFiltering.value = false;
    }
};

const setFilter = (filterKey) => {
    activeFilter.value = filterKey;
    loadFilteredCustomers();
};

// Expiry countdown / remaining hours helper
const getExpiryRemainingText = (expiryDate) => {
    if (!expiryDate) return null;
    const now = new Date();
    const exp = new Date(expiryDate);
    const diffHours = Math.round((exp - now) / (1000 * 60 * 60));

    if (diffHours < 0) {
        const daysAgo = Math.abs(Math.round(diffHours / 24));
        return { text: `${daysAgo} দিন আগে মেয়াদ শেষ`, type: 'expired' };
    }
    if (diffHours <= 24) {
        return { text: `বাকি ${diffHours} ঘণ্টা!`, type: 'critical' };
    }
    if (diffHours <= 72) {
        const days = Math.ceil(diffHours / 24);
        return { text: `বাকি ${days} দিন (${diffHours}h)`, type: 'warning' };
    }
    return null;
};

watch([selectedArea], () => {
    loadFilteredCustomers();
});

onMounted(() => {
    loadFilteredCustomers();
});
</script>

<template>
    <Head title="Staff Field Console - Pirgacha Internet" />

    <StaffLayout>
        <!-- Background decorative glows -->
        <div class="fixed inset-0 pointer-events-none z-[-1] overflow-hidden">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-brand-sky/10 blur-[120px]"></div>
            <div class="absolute top-[40%] -right-[20%] w-[60%] h-[60%] rounded-full bg-brand-orange/5 blur-[120px]"></div>
        </div>

        <div class="space-y-6 animate-fade-in-up">
            <!-- Header Section -->
            <div class="flex flex-col space-y-1.5">
                <h2 class="text-2xl md:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-slate-400">
                    Welcome back, {{ staffDisplayName }}
                </h2>
                <p class="text-slate-400 text-sm">Manage collections and complaints.</p>
            </div>

            <!-- Tabs -->
            <div class="flex p-1 space-x-1 bg-[#0B1E36]/80 rounded-xl backdrop-blur-xl border border-white/10 shadow-lg">
                <button
                    @click="activeTab = 'collections'"
                    :class="[
                        'w-full py-2.5 text-sm font-bold rounded-lg transition-all duration-300 flex justify-center items-center gap-2',
                        activeTab === 'collections' ? 'bg-gradient-to-r from-brand-sky to-brand-cyan text-white shadow-lg' : 'text-slate-400 hover:text-white hover:bg-white/5'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    বিল আদায়
                </button>
                <button
                    @click="activeTab = 'complaints'"
                    :class="[
                        'w-full py-2.5 text-sm font-bold rounded-lg transition-all duration-300 flex justify-center items-center gap-2',
                        activeTab === 'complaints' ? 'bg-gradient-to-r from-rose-500 to-rose-400 text-white shadow-lg' : 'text-slate-400 hover:text-white hover:bg-white/5'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    কমপ্লেইন
                    <span v-if="props.open_complaints?.length > 0" class="bg-white text-rose-500 text-[10px] px-1.5 py-0.5 rounded-full ml-1 font-black">{{ props.open_complaints.length }}</span>
                </button>
            </div>

            <!-- Bill Collection Tab -->
            <div v-show="activeTab === 'collections'" class="space-y-6">
                
                <!-- Collection Period Selector & Operational Metrics -->
                <div class="space-y-4">
                    <!-- Period Filter Tabs (Centered & Balanced Layout) -->
                    <div class="flex justify-center w-full">
                        <div class="grid grid-cols-4 w-full p-1 bg-[#0B1E36]/90 rounded-2xl border border-white/10 backdrop-blur-xl shadow-inner gap-1">
                            <button
                                v-for="period in [
                                    { key: 'today', label: 'আজ' },
                                    { key: 'week', label: 'এই সপ্তাহ' },
                                    { key: 'month', label: 'এই মাস' },
                                    { key: 'year', label: 'এই বছর' }
                                ]"
                                :key="period.key"
                                @click="selectedPeriod = period.key"
                                :class="[
                                    'py-2 text-center text-xs sm:text-sm font-bold rounded-xl transition-all duration-200',
                                    selectedPeriod === period.key
                                        ? 'bg-brand-sky text-white shadow-md shadow-brand-sky/20'
                                        : 'text-slate-400 hover:text-white hover:bg-white/5'
                                ]"
                            >
                                {{ period.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Main KPIs Grid -->
                    <div class="grid grid-cols-2 gap-3.5">
                        <!-- Total Collection KPI -->
                        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#0B1E36]/95 to-[#071527]/95 p-4 md:p-5 backdrop-blur-xl shadow-lg group hover:border-brand-cyan/30 transition-all duration-300">
                            <div class="absolute -right-6 -top-6 w-24 h-24 bg-brand-cyan/10 rounded-full blur-2xl group-hover:bg-brand-cyan/20 transition-all duration-500"></div>
                            <div class="flex items-center gap-2.5 mb-1.5 relative z-10">
                                <div class="p-2 rounded-xl bg-brand-cyan/10 text-brand-cyan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    {{ selectedPeriod === 'today' ? 'আজকের আদায়' : (selectedPeriod === 'week' ? 'সাপ্তাহিক আদায়' : (selectedPeriod === 'month' ? 'মাসিক আদায়' : 'বার্ষিক আদায়')) }}
                                </div>
                            </div>
                            <div class="text-2xl md:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-brand-cyan to-brand-sky mt-1 font-mono drop-shadow-md relative z-10">
                                ৳{{ currentMetrics.collection.toLocaleString('en-US') }}
                            </div>
                            <div class="text-[11px] font-medium text-slate-400 mt-1 flex items-center gap-1.5 relative z-10">
                                <span class="inline-flex h-1.5 w-1.5 rounded-full bg-brand-cyan"></span>
                                মোট {{ currentMetrics.count }} টি সফল কালেকশন
                            </div>
                        </div>

                        <!-- Renewals & Cash Breakdown KPI -->
                        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#0B1E36]/95 to-[#071527]/95 p-4 md:p-5 backdrop-blur-xl shadow-lg group hover:border-brand-orange/30 transition-all duration-300">
                            <div class="absolute -right-6 -top-6 w-24 h-24 bg-brand-orange/10 rounded-full blur-2xl group-hover:bg-brand-orange/20 transition-all duration-500"></div>
                            <div class="flex items-center gap-2.5 mb-1.5 relative z-10">
                                <div class="p-2 rounded-xl bg-brand-orange/10 text-brand-orange">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </div>
                                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">রিনিউ ও ক্যাশ ইন হ্যান্ড</div>
                            </div>
                            <div class="text-2xl md:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-brand-orange to-brand-amber mt-1 font-mono drop-shadow-md relative z-10">
                                ৳{{ currentMetrics.cash.toLocaleString('en-US') }}
                            </div>
                            <div class="text-[11px] font-medium text-slate-400 mt-1 flex items-center gap-1.5 relative z-10">
                                <span class="inline-flex h-1.5 w-1.5 rounded-full bg-brand-orange"></span>
                                ক্যাশ আদায় • রিনিউ: {{ currentMetrics.renewals }} টি
                            </div>
                        </div>
                    </div>

                    <!-- Method Distribution Pill (Cash vs Digital) -->
                    <div class="p-3 rounded-2xl bg-[#0B1E36]/60 border border-white/5 flex items-center justify-between text-xs text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <span>নগদ ক্যাশ (Cash): <strong class="font-mono text-emerald-400">৳{{ currentMetrics.cash.toLocaleString('en-US') }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-brand-sky"></span>
                            <span>ডিজিটাল (MFS/Bank): <strong class="font-mono text-brand-sky">৳{{ currentMetrics.digital.toLocaleString('en-US') }}</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Search Hero -->
                <div class="relative group z-20">
                    <div class="absolute -inset-1 bg-gradient-to-r from-brand-sky to-brand-cyan rounded-3xl blur opacity-25 group-hover:opacity-40 transition duration-500"></div>
                    <div class="relative flex items-center">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search Code, Name, Phone..."
                            class="w-full rounded-2xl border-white/10 bg-[#0B1E36]/80 py-4 pl-12 pr-12 text-sm text-white placeholder-slate-400 shadow-2xl focus:border-brand-sky focus:ring-2 focus:ring-brand-sky/50 backdrop-blur-xl transition-all"
                        />
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-brand-sky">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <div v-if="isSearching" class="absolute inset-y-0 right-0 flex items-center pr-4">
                            <svg class="h-5 w-5 animate-spin text-brand-cyan" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                            </svg>
                        </div>
                    </div>
                    <!-- Instant Search Dropdown Results -->
                    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                        <div v-if="searchResults.length > 0" class="absolute top-full left-0 right-0 mt-3 rounded-2xl border border-white/10 bg-[#071527]/95 backdrop-blur-xl p-2 shadow-2xl space-y-1 z-30">
                            <Link
                                v-for="item in searchResults"
                                :key="item.id"
                                :href="route('staff.customer-details', item.id)"
                                class="group flex items-center justify-between rounded-xl p-3 hover:bg-white/5 border border-transparent hover:border-white/10 transition-all duration-300"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-brand-sky/10 flex items-center justify-center text-brand-sky font-bold shadow-[0_0_15px_rgba(0,141,210,0.2)] group-hover:bg-brand-sky group-hover:text-white transition-colors">
                                        {{ item.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-white group-hover:text-brand-sky transition-colors">{{ item.name }}</div>
                                        <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                                            <span class="font-mono text-brand-cyan/80">{{ item.customer_code }}</span>
                                            <span class="w-1 h-1 rounded-full bg-slate-600"></span>
                                            <span>{{ item.primary_contact?.phone || 'No phone' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </Transition>
                </div>

                <!-- 72-Hour Expiry Critical Alert Banner -->
                <div 
                    v-if="filterCounts.expiring_72h > 0"
                    @click="setFilter('expiring_72h')"
                    class="relative overflow-hidden rounded-3xl border border-amber-500/40 bg-gradient-to-r from-amber-500/15 via-rose-500/10 to-[#071527] p-4 shadow-xl backdrop-blur-xl cursor-pointer hover:border-amber-400 transition-all group"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-xl shadow-[0_0_15px_rgba(245,158,11,0.3)] animate-pulse">
                                ⚠️
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                                    <span>জরুরি সতর্কতা (Critical Notice)</span>
                                    <span class="px-2 py-0.5 rounded-full bg-amber-500/20 border border-amber-500/30 text-[10px] font-black text-amber-300">
                                        {{ filterCounts.expiring_72h }} জন গ্রাহক
                                    </span>
                                </div>
                                <p class="text-xs text-slate-200 mt-0.5 leading-snug">
                                    আগামী <strong>৭২ ঘণ্টার মধ্যে</strong> সংযোগের মেয়াদ শেষ হবে। সংযোগ বিচ্ছিন্ন হওয়া রোধে দ্রুত বিল আদায় বা যোগাযোগ করুন।
                                </p>
                            </div>
                        </div>

                        <button 
                            type="button"
                            class="shrink-0 px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-rose-500 text-white font-bold text-xs shadow-lg shadow-amber-500/20 group-hover:scale-105 transition"
                        >
                            লিস্ট দেখুন →
                        </button>
                    </div>
                </div>

                <!-- Professional Operational Filter Tabs Bar -->
                <div class="rounded-3xl border border-white/10 bg-[#071527]/70 backdrop-blur-xl p-4 shadow-xl space-y-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            ফিল্টার ক্যাটাগরি (Operations Filter)
                        </span>

                        <!-- Area Selector Dropdown -->
                        <div class="w-44">
                            <select v-model="selectedArea" class="w-full rounded-xl border border-white/10 bg-[#0B1E36] text-white text-xs py-1.5 px-2.5 focus:border-brand-sky focus:ring-brand-sky shadow-inner">
                                <option value="">সব এলাকা (All)</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filter Badges / Pills -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <!-- 1. Due / Unpaid -->
                        <button
                            type="button"
                            @click="setFilter('due')"
                            :class="[
                                'p-2.5 rounded-2xl border text-left transition-all relative overflow-hidden',
                                activeFilter === 'due'
                                    ? 'bg-rose-500/20 border-rose-500 text-white shadow-md shadow-rose-500/20'
                                    : 'bg-[#0B1E36]/60 border-white/5 text-slate-400 hover:text-slate-200 hover:bg-[#0B1E36]'
                            ]"
                        >
                            <div class="text-[10px] font-bold uppercase tracking-wider text-rose-400 flex items-center justify-between">
                                <span>আদায় বাকি</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            </div>
                            <div class="text-lg font-black font-mono text-rose-400 mt-0.5">{{ filterCounts.due }}</div>
                            <div class="text-[10px] text-rose-300/80 mt-0.5">বকেয়া গ্রাহক</div>
                        </button>

                        <!-- 3. Paid / Collected -->
                        <button
                            type="button"
                            @click="setFilter('paid')"
                            :class="[
                                'p-2.5 rounded-2xl border text-left transition-all relative overflow-hidden',
                                activeFilter === 'paid'
                                    ? 'bg-emerald-500/20 border-emerald-500 text-white shadow-md shadow-emerald-500/20'
                                    : 'bg-[#0B1E36]/60 border-white/5 text-slate-400 hover:text-slate-200 hover:bg-[#0B1E36]'
                            ]"
                        >
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 flex items-center justify-between">
                                <span>বিল জমা</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            </div>
                            <div class="text-lg font-black font-mono text-emerald-400 mt-0.5">{{ filterCounts.paid }}</div>
                            <div class="text-[10px] text-emerald-300/80 mt-0.5">চলতি মাসে আদায়</div>
                        </button>

                        <!-- 4. Expiring in 72h -->
                        <button
                            type="button"
                            @click="setFilter('expiring_72h')"
                            :class="[
                                'p-2.5 rounded-2xl border text-left transition-all relative overflow-hidden',
                                activeFilter === 'expiring_72h'
                                    ? 'bg-amber-500/25 border-amber-400 text-white shadow-md shadow-amber-500/25'
                                    : 'bg-[#0B1E36]/60 border-white/5 text-slate-400 hover:text-slate-200 hover:bg-[#0B1E36]'
                            ]"
                        >
                            <div class="text-[10px] font-bold uppercase tracking-wider text-amber-400 flex items-center justify-between">
                                <span>৭২ঘন্টায় শেষ</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                            </div>
                            <div class="text-lg font-black font-mono text-amber-400 mt-0.5">{{ filterCounts.expiring_72h }}</div>
                            <div class="text-[10px] text-amber-300/80 mt-0.5">আসন্ন মেয়াদ শেষ</div>
                        </button>

                        <!-- 5. Renewed This Month -->
                        <button
                            type="button"
                            @click="setFilter('renewed')"
                            :class="[
                                'p-2.5 rounded-2xl border text-left transition-all relative overflow-hidden',
                                activeFilter === 'renewed'
                                    ? 'bg-brand-cyan/20 border-brand-cyan text-white shadow-md shadow-brand-cyan/20'
                                    : 'bg-[#0B1E36]/60 border-white/5 text-slate-400 hover:text-slate-200 hover:bg-[#0B1E36]'
                            ]"
                        >
                            <div class="text-[10px] font-bold uppercase tracking-wider text-brand-cyan flex items-center justify-between">
                                <span>রিনিউ হয়েছে</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-cyan"></span>
                            </div>
                            <div class="text-lg font-black font-mono text-brand-cyan mt-0.5">{{ filterCounts.renewed }}</div>
                            <div class="text-[10px] text-brand-cyan/80 mt-0.5">নবায়নকৃত</div>
                        </button>
                    </div>
                </div>

                <!-- Filtered Customer List Section -->
                <div class="rounded-3xl border border-white/10 bg-[#071527]/60 backdrop-blur-xl p-4 md:p-5 shadow-lg min-h-[260px]">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <span v-if="activeFilter === 'due'" class="text-rose-400">🔴 আদায় বাকি গ্রাহক তালিকা</span>
                                <span v-else-if="activeFilter === 'paid'" class="text-emerald-400">🟢 বিল পরিশোধিত গ্রাহক তালিকা</span>
                                <span v-else-if="activeFilter === 'expiring_72h'" class="text-amber-400">⚠️ ৭২ ঘণ্টায় মেয়াদ শেষ তালিকা</span>
                                <span v-else-if="activeFilter === 'renewed'" class="text-brand-cyan">🔵 চলতি মাসে রিনিউ তালিকা</span>
                                <span v-else>গ্রাহক তালিকা</span>
                                <span class="bg-brand-cyan/20 text-brand-cyan border border-brand-cyan/30 text-[10px] px-2 py-0.5 rounded-full font-mono">
                                    {{ filteredCustomersList.length }}
                                </span>
                            </h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">গ্রাহকের প্রোফাইলে ক্লিক করে সরাসরি বিল আদায় বা রিনিউ করুন</p>
                        </div>

                        <div v-if="isFiltering" class="flex items-center text-brand-sky text-xs gap-1.5 font-semibold">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            আপডেট হচ্ছে...
                        </div>
                    </div>

                    <div v-if="filteredCustomersList.length > 0" class="space-y-3">
                        <Link
                            v-for="customer in filteredCustomersList"
                            :key="customer.id"
                            :href="route('staff.customer-details', customer.id)"
                            class="block p-4 rounded-2xl bg-[#0B1E36]/60 border border-white/5 hover:bg-[#0B1E36] hover:border-brand-sky/40 transition-all duration-300 group shadow-sm"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-bold text-white group-hover:text-brand-sky transition-colors">
                                            {{ customer.name }}
                                        </div>
                                        <span class="text-xs font-mono text-brand-sky/80 bg-brand-sky/10 border border-brand-sky/20 px-1.5 py-0.5 rounded">
                                            {{ customer.customer_code }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                                        <span v-if="customer.primary_contact?.phone" class="font-mono">
                                            📞 {{ customer.primary_contact.phone }}
                                        </span>
                                        <span class="text-slate-600">•</span>
                                        <span class="text-slate-300 font-semibold">
                                            {{ customer.connections?.[0]?.current_package?.name || 'Standard' }}
                                        </span>
                                        <span class="text-slate-600">•</span>
                                        <span class="font-mono text-white">
                                            ৳{{ customer.connections?.[0]?.current_package?.current_price?.price || 500 }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-right sm:self-center">
                                    <!-- 72h Warning or Expiry Badge -->
                                    <div v-if="getExpiryRemainingText(customer.connections?.[0]?.expiry_date)" class="text-right">
                                        <span 
                                            :class="[
                                                getExpiryRemainingText(customer.connections?.[0]?.expiry_date).type === 'critical' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 animate-pulse' : '',
                                                getExpiryRemainingText(customer.connections?.[0]?.expiry_date).type === 'warning' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : '',
                                                getExpiryRemainingText(customer.connections?.[0]?.expiry_date).type === 'expired' ? 'bg-slate-800 text-slate-400 border-slate-700' : '',
                                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-bold border'
                                            ]"
                                        >
                                            <span>⏱️</span>
                                            <span>{{ getExpiryRemainingText(customer.connections?.[0]?.expiry_date).text }}</span>
                                        </span>
                                    </div>

                                    <!-- Balance / Payment Status -->
                                    <span v-if="customer.balance < 0" class="inline-block px-2.5 py-1 rounded-xl bg-rose-500/20 text-rose-400 text-[11px] font-bold border border-rose-500/30 font-mono shadow-sm">
                                        বকেয়া: ৳{{ Math.abs(customer.balance) }}
                                    </span>
                                    <span v-else class="inline-block px-2.5 py-1 rounded-xl bg-emerald-500/20 text-emerald-400 text-[11px] font-bold border border-emerald-500/30 font-mono shadow-sm">
                                        পরিশোধিত ✓
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>
                    <div v-else-if="!isFiltering" class="flex flex-col items-center justify-center py-12 opacity-70">
                        <div class="w-14 h-14 rounded-full bg-white/5 flex items-center justify-center text-slate-500 mb-3 border border-white/10">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <p class="text-sm text-slate-400 font-medium">এই ক্যাটাগরিতে কোনো গ্রাহক পাওয়া যায়নি</p>
                    </div>
                </div>

                <!-- Recent Collections widget -->
                <div v-if="recent_collections?.length > 0" class="rounded-3xl border border-white/10 bg-[#071527]/60 backdrop-blur-xl p-5 shadow-lg">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">আজকের কালেকশন লগ (Recent Log)</h2>
                    <div class="space-y-3">
                        <div v-for="pay in recent_collections" :key="pay.id" class="flex justify-between items-center text-sm border-b border-white/5 pb-3 last:border-0 last:pb-0">
                            <div>
                                <div class="text-slate-200 font-bold">{{ pay.customer?.name }}</div>
                                <div class="flex items-center gap-1.5 mt-1 text-[10px] text-slate-400 font-mono uppercase tracking-wider">
                                    <span class="bg-white/10 px-1.5 py-0.5 rounded border border-white/5 text-slate-300">{{ pay.payment_method }}</span>
                                    <span>•</span>
                                    <span>{{ formatDateTime(pay.paid_at) }}</span>
                                </div>
                            </div>
                            <span class="font-mono text-brand-cyan font-black text-lg drop-shadow-sm">৳{{ pay.amount }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Complaints Tab -->
            <div v-show="activeTab === 'complaints'" class="space-y-6">
                <div class="rounded-3xl border border-white/10 bg-[#071527]/60 backdrop-blur-xl p-4 shadow-lg min-h-[400px]">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">এসাইনড কমপ্লেইন</h2>
                        <span class="bg-rose-500/20 text-rose-400 border border-rose-500/30 text-[10px] px-2.5 py-1 rounded-full font-bold">
                            {{ open_complaints?.length || 0 }} Active
                        </span>
                    </div>

                    <div v-if="open_complaints?.length > 0" class="space-y-4">
                        <div 
                            v-for="complaint in open_complaints" 
                            :key="complaint.id"
                            class="p-4 rounded-2xl bg-[#0B1E36]/60 border border-white/5 hover:border-rose-500/40 hover:bg-[#0B1E36] transition-all group"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="text-[10px] text-slate-400 font-mono mb-1 flex items-center gap-2">
                                        <span>{{ complaint.complaint_number }}</span>
                                        <span class="text-slate-600">•</span>
                                        <span>{{ formatDateTime(complaint.created_at) }}</span>
                                    </div>
                                    <Link 
                                        :href="route('staff.complaints.show', complaint.id)"
                                        class="text-sm font-bold text-white group-hover:text-rose-400 transition-colors block"
                                    >
                                        {{ complaint.subject }}
                                    </Link>
                                </div>
                                <span class="bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[10px] px-2 py-0.5 rounded-md font-bold uppercase shadow-sm">
                                    {{ complaint.status }}
                                </span>
                            </div>

                            <!-- Customer Info Row -->
                            <div class="mt-3 p-2.5 rounded-xl bg-[#071322]/80 border border-white/5 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <div>
                                    <div class="font-bold text-white flex items-center gap-1.5">
                                        <span class="text-brand-sky">{{ complaint.customer?.name }}</span>
                                        <span v-if="complaint.customer?.customer_code" class="text-[10px] font-mono text-slate-400 bg-white/5 px-1 py-0.5 rounded">
                                            {{ complaint.customer.customer_code }}
                                        </span>
                                    </div>
                                    <div v-if="complaint.customer?.installation_address?.address" class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs">
                                        📍 {{ complaint.customer.installation_address.address }}
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2">
                                    <a
                                        v-if="complaint.customer?.primary_contact?.phone"
                                        :href="`tel:${complaint.customer.primary_contact.phone}`"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500 text-emerald-300 hover:text-white border border-emerald-500/40 text-xs font-bold transition shadow-sm"
                                        title="Call Customer Directly"
                                    >
                                        <span>📞</span>
                                        <span>কল দিন</span>
                                    </a>

                                    <Link
                                        :href="route('staff.complaints.show', complaint.id)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-brand-sky/20 hover:bg-brand-sky text-brand-sky hover:text-white border border-brand-sky/40 text-xs font-bold transition shadow-sm"
                                    >
                                        <span>সমাধান করুন →</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-16 opacity-70">
                        <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center text-emerald-500 mb-3 border border-white/10">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="text-sm text-slate-300 font-medium">কোনো কমপ্লেইন নেই!</p>
                        <p class="text-xs text-slate-500 mt-1">সব ঠিক আছে।</p>
                    </div>
                </div>
            </div>
        </div>

    </StaffLayout>
</template>

<style>
@keyframes fade-in-up {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fade-in-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
