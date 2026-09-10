<script setup>
import { Head, Link } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';
</script>

<template>
    <Head title="About Us - Pirgacha Internet" />

    <WebsiteLayout>
        <div class="py-16 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest">Our Mission</span>
                <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">Connecting Pirgacha with High-Speed Fiber</h1>
            </div>

            <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-8 backdrop-blur-sm space-y-6 text-sm text-slate-300 leading-relaxed">
                <p>
                    <strong class="text-white">Pirgacha Internet</strong> is a licensed Internet Service Provider (ISP) dedicated to providing high-performance, affordable, and dependable broadband services to homes, educational institutions, healthcare centers, and businesses throughout the Pirgacha region.
                </p>

                <p>
                    Founded with a vision to eliminate the digital divide in rural and semi-urban communities, we deployed an advanced Optical Fiber to the Home (FTTH) network infrastructure engineered for extreme stability, low latency, and zero data throttling.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-800 text-xs">
                    <div class="space-y-1">
                        <span class="font-bold text-white block">Dedicated Optical Backbone</span>
                        <p class="text-slate-400">Multiple upstream gateways with automatic failover protection ensuring high service uptime.</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-bold text-white block">Local Field Support NOC</span>
                        <p class="text-slate-400">Our technicians reside within Pirgacha, enabling rapid resolution of physical and technical fiber faults.</p>
                    </div>
                </div>
            </div>
        </div>
    </WebsiteLayout>
</template>
