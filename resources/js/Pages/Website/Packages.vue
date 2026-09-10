<script setup>
import { Head, Link } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

defineProps({
    packages: Array,
});
</script>

<template>
    <Head title="Broadband Packages - Pirgacha Internet" />

    <WebsiteLayout>
        <div class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest">Our Optical Fiber Plans</span>
                <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">High Speed Broadband Packages</h1>
                <p class="text-xs text-slate-400 mt-2">Zero throttling, true optical FTTH connectivity with dedicated bandwidth for home and office.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="pkg in packages"
                    :key="pkg.id"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 flex flex-col justify-between hover:border-cyan-500/50 hover:bg-slate-900 transition-all duration-300 shadow-xl"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-cyan-400">{{ pkg.code }}</span>
                            <span class="rounded-full bg-cyan-500/10 border border-cyan-500/20 px-2.5 py-0.5 text-[10px] font-bold text-cyan-300">Fiber FTTH</span>
                        </div>

                        <h2 class="text-xl font-black text-white mt-3">{{ pkg.name }}</h2>
                        <div class="text-4xl font-black text-white font-mono mt-4">
                            {{ pkg.speed_mbps }} <span class="text-sm font-normal text-slate-400">Mbps</span>
                        </div>

                        <div class="mt-6 pt-6 border-t border-slate-800 space-y-2.5 text-xs text-slate-300">
                            <div class="flex items-center gap-2">✓ Optical Fiber ONU Setup</div>
                            <div class="flex items-center gap-2">✓ Unlimited Data, No FUP</div>
                            <div class="flex items-center gap-2">✓ BDIX & Youtube Cache 100Mbps</div>
                            <div class="flex items-center gap-2">✓ Low Latency Gaming Peering</div>
                            <div class="flex items-center gap-2">✓ 24/7 Field Support</div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-500 block">Monthly Price</span>
                            <div class="text-2xl font-black text-cyan-400 font-mono">
                                ৳{{ pkg.current_price?.price || '500' }}
                            </div>
                        </div>

                        <Link
                            :href="route('contact')"
                            class="rounded-xl bg-cyan-600 hover:bg-cyan-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-cyan-600/20"
                        >
                            Get Started
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </WebsiteLayout>
</template>
