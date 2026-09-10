<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

const props = defineProps({
    packages: Array,
    coverageAreas: Array,
});

const applyForm = useForm({
    name: '',
    phone: '',
    email: '',
    area_id: props.coverageAreas?.[0]?.children?.[0]?.id || props.coverageAreas?.[0]?.id || '',
    address: '',
    package_id: props.packages?.[0]?.id || '',
    notes: '',
});

const submitApplication = () => {
    applyForm.post(route('apply'), {
        onSuccess: () => {
            applyForm.reset('name', 'phone', 'email', 'address', 'notes');
        }
    });
};
</script>

<template>
    <Head title="Pirgacha Internet - High Speed Optical Fiber Broadband" />

    <WebsiteLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden py-20 lg:py-32 border-b border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-bold text-cyan-300 mb-6 shadow-lg shadow-cyan-500/10">
                        <span class="h-2 w-2 rounded-full bg-cyan-400 animate-pulse"></span>
                        <span>Gigabit Ready Optical Fiber Network</span>
                    </div>

                    <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-tight">
                        Experience Seamless Internet with <span class="bg-gradient-to-r from-cyan-400 via-teal-300 to-emerald-400 bg-clip-text text-transparent">Pirgacha Internet</span>.
                    </h1>

                    <p class="mt-6 text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl">
                        Uninterrupted buffer-free 4K streaming, zero-latency gaming, and rock-solid optical fiber connectivity for homes, businesses, and institutions in Pirgacha.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a
                            href="#packages"
                            class="rounded-2xl bg-gradient-to-r from-cyan-600 to-teal-500 hover:from-cyan-500 hover:to-teal-400 px-6 py-3.5 text-xs font-extrabold text-white shadow-xl shadow-cyan-600/30 transition active:scale-95"
                        >
                            Explore Packages →
                        </a>
                        <a
                            href="#apply"
                            class="rounded-2xl border border-slate-700 bg-slate-900/80 hover:bg-slate-800 px-6 py-3.5 text-xs font-bold text-slate-200 transition"
                        >
                            Get New Connection
                        </a>
                    </div>
                </div>
            </div>

            <!-- Background decorative elements -->
            <div class="absolute -top-24 right-0 h-96 w-96 rounded-full bg-cyan-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 right-1/4 h-80 w-80 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
        </section>

        <!-- Feature Highlights -->
        <section class="py-16 bg-slate-900/40 border-b border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="rounded-3xl border border-slate-800 bg-slate-950 p-6 space-y-3">
                    <div class="h-10 w-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 font-bold">
                        ⚡
                    </div>
                    <h3 class="text-base font-bold text-white">Ultra-Low Ping & Buffer Free</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Direct upstream peering with national Internet Exchanges (BDIX) ensures lightning-fast downloads and seamless video calling.</p>
                </div>

                <div class="rounded-3xl border border-slate-800 bg-slate-950 p-6 space-y-3">
                    <div class="h-10 w-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold">
                        🛡️
                    </div>
                    <h3 class="text-base font-bold text-white">99.9% Optical Fiber Uptime</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Redundant optical backbones protect against physical wire damages with automatic rerouting.</p>
                </div>

                <div class="rounded-3xl border border-slate-800 bg-slate-950 p-6 space-y-3">
                    <div class="h-10 w-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold">
                        👨‍🔧
                    </div>
                    <h3 class="text-base font-bold text-white">Dedicated On-Site Technicians</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Our local Pirgacha NOC team responds to physical connection queries and resolves tickets within hours.</p>
                </div>
            </div>
        </section>

        <!-- Database-Driven Packages Section -->
        <section id="packages" class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest">Affordable Pricing</span>
                <h2 class="text-3xl font-black text-white mt-2">Choose Your Optical Speed Plan</h2>
                <p class="text-xs text-slate-400 mt-2">All plans include unlimited broadband data, zero throttling, and BDIX peering.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="pkg in packages"
                    :key="pkg.id"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 flex flex-col justify-between hover:border-cyan-500/50 hover:bg-slate-900 transition-all duration-300 shadow-xl"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono text-cyan-400">{{ pkg.code }}</span>
                            <span class="rounded-full bg-cyan-500/10 border border-cyan-500/20 px-2 py-0.5 text-[10px] font-bold text-cyan-300">Optical FTTH</span>
                        </div>

                        <h3 class="text-lg font-black text-white mt-3">{{ pkg.name }}</h3>
                        <div class="text-3xl font-black text-white font-mono mt-4">
                            {{ pkg.speed_mbps }} <span class="text-sm font-normal text-slate-400">Mbps</span>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-800/80 space-y-2 text-xs text-slate-300">
                            <div class="flex items-center gap-2">✓ Unlimited data usage</div>
                            <div class="flex items-center gap-2">✓ 100 Mbps BDIX cache speed</div>
                            <div class="flex items-center gap-2">✓ Optical ONU terminal support</div>
                            <div class="flex items-center gap-2">✓ 24/7 dedicated local support</div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-500 block">Monthly Rate</span>
                            <div class="text-xl font-black text-cyan-400 font-mono">
                                ৳{{ pkg.current_price?.price || '500' }}
                            </div>
                        </div>

                        <a
                            href="#apply"
                            @click="applyForm.package_id = pkg.id"
                            class="rounded-xl bg-cyan-600 hover:bg-cyan-500 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-cyan-600/20 transition active:scale-95"
                        >
                            Select Plan
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Online Connection Application Form Section -->
        <section id="apply" class="py-20 bg-slate-900/30 border-t border-slate-800">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-8 shadow-2xl backdrop-blur-md">
                    <div class="mb-8">
                        <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest">Instant Setup</span>
                        <h2 class="text-2xl font-black text-white mt-1">Apply for a New Optical Connection</h2>
                        <p class="text-xs text-slate-400 mt-1">Fill out the quick form below and our team will survey your location and install fiber within 24 hours.</p>
                    </div>

                    <form @submit.prevent="submitApplication" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1">Your Full Name *</label>
                                <input v-model="applyForm.name" type="text" placeholder="e.g. Rafiqul Islam" required class="w-full rounded-2xl border-slate-800 bg-slate-950 p-3.5 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-cyan-500" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1">Mobile Phone Number *</label>
                                <input v-model="applyForm.phone" type="tel" placeholder="017XXXXXXXX" required class="w-full rounded-2xl border-slate-800 bg-slate-950 p-3.5 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-cyan-500 font-mono" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1">Email Address</label>
                                <input v-model="applyForm.email" type="email" placeholder="Optional" class="w-full rounded-2xl border-slate-800 bg-slate-950 p-3.5 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-cyan-500" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1">Select Area / Zone *</label>
                                <select v-model="applyForm.area_id" required class="w-full rounded-2xl border-slate-800 bg-slate-950 p-3.5 text-xs text-white focus:border-cyan-500 focus:ring-cyan-500">
                                    <optgroup v-for="area in coverageAreas" :key="area.id" :label="area.name">
                                        <option v-for="sub in area.children" :key="sub.id" :value="sub.id">
                                            {{ sub.name }} ({{ sub.code }})
                                        </option>
                                        <option v-if="!area.children?.length" :value="area.id">
                                            {{ area.name }}
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1">Target Package Plan *</label>
                            <select v-model="applyForm.package_id" required class="w-full rounded-2xl border-slate-800 bg-slate-950 p-3.5 text-xs text-white focus:border-cyan-500 focus:ring-cyan-500">
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }} ({{ pkg.speed_mbps }} Mbps) — ৳{{ pkg.current_price?.price || 500 }}/month
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1">Complete Installation Address *</label>
                            <textarea v-model="applyForm.address" rows="2" placeholder="House no, road, village or landmark..." required class="w-full rounded-2xl border-slate-800 bg-slate-950 p-3.5 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-cyan-500"></textarea>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button
                                type="submit"
                                :disabled="applyForm.processing"
                                class="rounded-2xl bg-gradient-to-r from-cyan-600 to-teal-500 hover:from-cyan-500 hover:to-teal-400 px-8 py-4 text-xs font-extrabold text-white shadow-xl shadow-cyan-600/30 transition active:scale-95 disabled:opacity-50"
                            >
                                {{ applyForm.processing ? 'Submitting Application...' : 'Submit Connection Request →' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </WebsiteLayout>
</template>
