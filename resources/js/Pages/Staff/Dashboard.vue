<script setup>
import { ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';

const page = usePage();
const user = page.props.auth.user;

const searchQuery = ref('');

const sampleTasks = [
    { id: 'T-104', type: 'collection', customer: 'Hasan Ali (CUST-0021)', address: 'Purbo Para, Pirgacha', amount: '৳800', status: 'pending' },
    { id: 'T-105', type: 'complaint', customer: 'Md. Monir (CUST-0044)', address: 'College Road', issue: 'Red LOS blinking on ONU', status: 'urgent' },
    { id: 'T-106', type: 'renewal', customer: 'Rakib Hossain (CUST-0019)', address: 'Bazar Moor', package: '10 Mbps Standard', status: 'pending' },
];
</script>

<template>
    <Head title="Staff Field Console - Pirgacha Internet" />

    <StaffLayout>
        <!-- Quick Search Bar (Prominent for Mobile) -->
        <div class="mb-6">
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search Customer code, name, phone, PPPoE..."
                    class="w-full rounded-2xl border-slate-800 bg-slate-900/90 py-3.5 pl-11 pr-4 text-sm text-white placeholder-slate-500 shadow-xl focus:border-emerald-500 focus:ring-emerald-500 backdrop-blur-md"
                />
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today Summary Counter -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Collected Today</div>
                <div class="text-2xl font-black text-emerald-400 mt-1">৳14,200</div>
                <div class="text-[11px] text-slate-500 mt-0.5">18 Collections</div>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Assigned Tickets</div>
                <div class="text-2xl font-black text-amber-400 mt-1">3 Tasks</div>
                <div class="text-[11px] text-slate-500 mt-0.5">1 Urgent Repair</div>
            </div>
        </div>

        <!-- Field Actions List -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider">Field Tasks & Follow-ups</h2>
                <span class="text-xs text-slate-500">Live Sync Queue: 0 Pending</span>
            </div>

            <div class="space-y-2.5">
                <div 
                    v-for="task in sampleTasks" 
                    :key="task.id"
                    class="rounded-2xl border border-slate-800/80 bg-slate-900/70 p-4 hover:border-slate-700 transition"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span 
                                    :class="[
                                        task.status === 'urgent' ? 'bg-rose-500/20 text-rose-400 border-rose-500/30' : 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                                        'rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider'
                                    ]"
                                >
                                    {{ task.type }}
                                </span>
                                <span class="text-xs font-semibold text-slate-300">{{ task.id }}</span>
                            </div>
                            <h3 class="text-sm font-bold text-white mt-1">{{ task.customer }}</h3>
                            <p class="text-xs text-slate-400 mt-0.5">{{ task.address }}</p>
                            <p v-if="task.issue" class="text-xs text-rose-300 font-medium mt-1">Fault: {{ task.issue }}</p>
                        </div>

                        <div class="text-right">
                            <div v-if="task.amount" class="text-sm font-extrabold text-emerald-400">{{ task.amount }}</div>
                            <button 
                                type="button"
                                class="mt-2 inline-flex items-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition"
                            >
                                Action
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
