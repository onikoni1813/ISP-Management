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
                <span class="text-xs font-bold text-brand-orange uppercase tracking-widest">Our Optical Fiber Plans</span>
                <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">High Speed Broadband Packages</h1>
                <p class="text-xs text-slate-400 mt-2">Zero throttling, true optical FTTH connectivity with dedicated bandwidth for home and office.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="pkg in packages"
                    :key="pkg.id"
                    class="rounded-3xl border border-brand-navy bg-[#091A2E]/80 p-6 flex flex-col justify-between hover:border-brand-orange/60 hover:bg-[#0B2038] transition-all duration-300 shadow-xl"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-brand-sky">{{ pkg.code }}</span>
                            <span class="rounded-full bg-brand-orange/10 border border-brand-orange/30 px-2.5 py-0.5 text-[10px] font-bold text-brand-orange">Fiber FTTH</span>
                        </div>

                        <h2 class="text-xl font-black text-white mt-3">{{ pkg.name }}</h2>
                        <div class="text-4xl font-black text-white font-mono mt-4">
                            {{ pkg.speed_mbps }} <span class="text-sm font-normal text-slate-400">Mbps</span>
                        </div>

                        <div v-if="pkg.recommended_devices" class="mt-2.5 inline-flex items-center gap-1.5 rounded-lg bg-brand-sky/10 border border-brand-sky/20 px-2 py-1 text-[11px] text-brand-sky font-semibold">
                            <span>📱</span>
                            <span>{{ pkg.recommended_devices }}</span>
                        </div>

                        <div class="mt-6 pt-6 border-t border-brand-navy space-y-2.5 text-xs text-slate-300">
                            <template v-if="pkg.features && pkg.features.length > 0">
                                <div
                                    v-for="(feat, fIndex) in pkg.features.filter(f => f.enabled)"
                                    :key="fIndex"
                                    class="flex items-center justify-between"
                                >
                                    <span class="flex items-center gap-2">
                                        <span class="text-brand-orange font-bold">✓</span>
                                        <span>{{ feat.name }}</span>
                                    </span>
                                    <span class="font-mono font-bold text-brand-sky text-xs">{{ feat.value }}</span>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex items-center gap-2"><span class="text-brand-orange font-bold">✓</span> Optical Fiber ONU Setup</div>
                                <div class="flex items-center gap-2"><span class="text-brand-orange font-bold">✓</span> Unlimited Data, No FUP</div>
                                <div class="flex items-center gap-2"><span class="text-brand-orange font-bold">✓</span> YouTube & BDIX 100 Mbps</div>
                                <div class="flex items-center gap-2"><span class="text-brand-orange font-bold">✓</span> Facebook & IMO Unlimited</div>
                                <div class="flex items-center gap-2"><span class="text-brand-orange font-bold">✓</span> 24/7 Field Support</div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-brand-navy flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 block">Monthly Price</span>
                            <div class="text-2xl font-black text-brand-orange font-mono">
                                ৳{{ pkg.current_price?.price || '500' }}
                            </div>
                        </div>

                        <Link
                            :href="route('contact')"
                            class="rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue hover:from-brand-cyan hover:to-brand-sky px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-brand-sky/20 transition active:scale-95"
                        >
                            Get Started
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </WebsiteLayout>
</template>
