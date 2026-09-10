<script setup>
import { Head, Link } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

defineProps({
    coverageAreas: Array,
});
</script>

<template>
    <Head title="Coverage Areas - Pirgacha Internet" />

    <WebsiteLayout>
        <div class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest">Network Map</span>
                <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">Coverage Areas & Zones</h1>
                <p class="text-xs text-slate-400 mt-2">We have expanded our optical distribution network across Pirgacha Sadar and adjacent communities.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="area in coverageAreas"
                    :key="area.id"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-white">{{ area.name }}</h2>
                        <span class="rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-0.5 text-[10px] font-bold text-emerald-400">Active Optical Zone</span>
                    </div>

                    <p class="text-xs text-slate-400">{{ area.description || 'Full optical fiber FTTH coverage.' }}</p>

                    <!-- Sub-zones / Wards -->
                    <div v-if="area.children?.length > 0" class="pt-4 border-t border-slate-800 space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Connected Sub-Zones</span>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="sub in area.children"
                                :key="sub.id"
                                class="rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-1 text-xs font-mono text-cyan-300"
                            >
                                {{ sub.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 rounded-3xl border border-slate-800 bg-slate-900/40 p-8 text-center max-w-xl mx-auto space-y-3">
                <h3 class="text-base font-bold text-white">Don't see your area listed?</h3>
                <p class="text-xs text-slate-400">We are expanding our fiber backbones continuously. Reach out to check whether our optical distribution box is near your building.</p>
                <div class="pt-2">
                    <Link :href="route('contact')" class="rounded-xl bg-cyan-600 px-4 py-2 text-xs font-bold text-white">
                        Check Availability With NOC
                    </Link>
                </div>
            </div>
        </div>
    </WebsiteLayout>
</template>
