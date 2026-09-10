<script setup>
import { ref, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';

const props = defineProps({
    metrics: Object,
    recent_collections: Array,
});

const page = usePage();
const user = page.props.auth.user;

const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

let debounceTimeout = null;

watch(searchQuery, (newVal) => {
    clearTimeout(debounceTimeout);
    if (!newVal || newVal.trim().length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    debounceTimeout = setTimeout(async () => {
        try {
            const res = await axios.get(route('staff.api.search'), { params: { q: newVal } });
            searchResults.value = res.data;
        } catch (e) {
            console.error('Search error:', e);
        } finally {
            isSearching.value = false;
        }
    }, 300);
});
</script>

<template>
    <Head title="Staff Field Console - Pirgacha Internet" />

    <StaffLayout>
        <!-- Fast Mobile Customer Search (Sticky Hero) -->
        <div class="mb-6">
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search Code, Name, Phone, PPPoE..."
                    class="w-full rounded-2xl border-slate-800 bg-slate-900/95 py-3.5 pl-11 pr-10 text-sm text-white placeholder-slate-500 shadow-xl focus:border-emerald-500 focus:ring-emerald-500 backdrop-blur-md"
                />
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div v-if="isSearching" class="absolute inset-y-0 right-0 flex items-center pr-4">
                    <svg class="h-4 w-4 animate-spin text-emerald-400" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                    </svg>
                </div>
            </div>

            <!-- Instant Search Dropdown Results -->
            <div v-if="searchResults.length > 0" class="mt-2 rounded-2xl border border-slate-800 bg-slate-900 p-2 shadow-2xl space-y-1">
                <Link
                    v-for="item in searchResults"
                    :key="item.id"
                    :href="route('staff.customer-details', item.id)"
                    class="flex items-center justify-between rounded-xl p-3 hover:bg-slate-800/80 transition"
                >
                    <div>
                        <div class="text-sm font-bold text-white">{{ item.name }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            <span class="font-mono text-emerald-400">{{ item.customer_code }}</span>
                            <span> • </span>
                            <span>{{ item.primary_contact?.phone || 'No phone' }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-xs text-slate-300">
                            {{ item.connections?.[0]?.current_package?.name || 'Package' }}
                        </span>
                        <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                            {{ item.connections?.[0]?.pppoe_credential?.username || '' }}
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Today Operational Metrics -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/70 p-4 backdrop-blur-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Today's Collection</div>
                <div class="text-2xl font-black text-emerald-400 mt-1 font-mono">
                    ৳{{ metrics?.today_collection || 0 }}
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">{{ metrics?.today_collection_count || 0 }} Collections</div>
            </div>

            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/70 p-4 backdrop-blur-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Today's Renewals</div>
                <div class="text-2xl font-black text-indigo-400 mt-1 font-mono">
                    {{ metrics?.today_renewals_count || 0 }}
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">Active Connections Extended</div>
            </div>
        </div>

        <!-- Recent Collections by Staff -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 backdrop-blur-sm space-y-3 mb-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">My Recent Collections</h2>
                <span class="text-[11px] text-emerald-400 font-semibold">Live Audited</span>
            </div>

            <div v-if="recent_collections?.length > 0" class="divide-y divide-slate-800/60">
                <div v-for="pay in recent_collections" :key="pay.id" class="py-2.5 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-bold text-white">{{ pay.customer?.name }}</div>
                        <div class="text-xs text-slate-400 font-mono">{{ pay.payment_number }} • {{ pay.payment_method }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-mono font-extrabold text-emerald-400 text-sm">৳{{ pay.amount }}</div>
                        <div class="text-[10px] text-slate-500">{{ pay.paid_at }}</div>
                    </div>
                </div>
            </div>
            <div v-else class="text-xs text-slate-500 py-3 text-center">
                No collections logged yet today.
            </div>
        </div>
    </StaffLayout>
</template>
