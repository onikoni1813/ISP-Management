<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

const props = defineProps({
    packages: Array,
    coverageAreas: Array,
    banners: {
        type: Array,
        default: () => [],
    },
    latestNotices: {
        type: Array,
        default: () => [],
    },
});

const activeBanner = computed(() => {
    return props.banners && props.banners.length > 0 ? props.banners[0] : null;
});

// Sorted packages directly from DB
const sortedPackages = computed(() => {
    if (!props.packages || props.packages.length === 0) return [];
    return [...props.packages].sort((a, b) => Number(a.speed_mbps) - Number(b.speed_mbps));
});

// Selected index of the package array
const selectedPackageIndex = ref(0);

// Initialize with a middle or first package if available
if (props.packages && props.packages.length > 0) {
    selectedPackageIndex.value = Math.min(1, props.packages.length - 1);
}

const activePackage = computed(() => {
    if (sortedPackages.value.length === 0) {
        return {
            id: null,
            name: 'Standard Fiber Plan',
            speed_mbps: 15,
            current_price: { price: '700' },
            description: 'Buffer-free optical fiber internet'
        };
    }
    return sortedPackages.value[selectedPackageIndex.value] || sortedPackages.value[0];
});

// Get recommended devices directly from DB (configured by Admin) with fallback
const recommendedDevices = computed(() => {
    if (activePackage.value?.recommended_devices) {
        return activePackage.value.recommended_devices;
    }
    const speed = Number(activePackage.value?.speed_mbps) || 10;
    if (speed <= 10) return '1 - 3 Devices (Basic)';
    if (speed <= 15) return '3 - 5 Devices (Family)';
    if (speed <= 25) return '5 - 8 Devices (Heavy/Gaming)';
    return '8+ Devices (Ultra 4K)';
});

const selectRecommendedPackage = () => {
    if (activePackage.value?.id) {
        applyForm.package_id = activePackage.value.id;
    }
};

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
        <section class="relative overflow-hidden py-16 lg:py-28 border-b border-brand-navy/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <!-- Left Column: Copy & Actions -->
                    <div class="lg:col-span-7">
                        <div class="inline-flex items-center gap-2 rounded-full border border-brand-orange/40 bg-brand-orange/10 px-3.5 py-1.5 text-xs font-bold text-brand-orange mb-6 shadow-lg shadow-brand-orange/15">
                            <span class="h-2 w-2 rounded-full bg-brand-orange animate-pulse"></span>
                            <span>{{ activeBanner?.badge_text || 'Gigabit Ready Optical Fiber Network' }}</span>
                        </div>

                        <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-tight">
                            <template v-if="activeBanner">
                                {{ activeBanner.title }}
                            </template>
                            <template v-else>
                                Connect to the World with <span class="bg-gradient-to-r from-brand-sky via-brand-cyan to-brand-orange bg-clip-text text-transparent">Pirgacha Internet</span>.
                            </template>
                        </h1>

                        <p class="mt-6 text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl">
                            {{ activeBanner?.subtitle || 'Uninterrupted buffer-free 4K streaming, zero-latency gaming, and rock-solid optical fiber connectivity for homes, businesses, and institutions in Pirgacha.' }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <a
                                :href="activeBanner?.button_url || '#packages'"
                                class="rounded-2xl bg-gradient-to-r from-brand-orange via-brand-amber to-brand-gold hover:opacity-95 px-7 py-3.5 text-xs font-black text-white shadow-xl shadow-brand-orange/30 transition active:scale-95 flex items-center gap-2"
                            >
                                <span>{{ activeBanner?.button_text || 'Explore Packages' }}</span>
                                <span>→</span>
                            </a>
                            <a
                                href="#apply"
                                class="rounded-2xl border border-brand-navy bg-[#0B1E36] hover:bg-[#102B4D] hover:border-brand-sky/50 px-6 py-3.5 text-xs font-bold text-slate-200 transition flex items-center gap-2"
                            >
                                <svg class="h-4 w-4 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span>Get New Connection</span>
                            </a>
                        </div>

                        <!-- Live Trust Metrics -->
                        <div class="mt-10 pt-8 border-t border-brand-navy/60 grid grid-cols-3 gap-6">
                            <div>
                                <div class="text-2xl font-black text-white font-mono">99.9%</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">Network Uptime</div>
                            </div>
                            <div>
                                <div class="text-2xl font-black text-brand-sky font-mono">&lt; 5 ms</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">BDIX Ultra Ping</div>
                            </div>
                            <div>
                                <div class="text-2xl font-black text-emerald-400 font-mono">1 Gbps</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">Backbone Core</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Interactive Bandwidth Calculator & Smart Recommendation -->
                    <div class="lg:col-span-5 relative">
                        <!-- Outer Ambient Glows -->
                        <div class="absolute -inset-2 bg-gradient-to-r from-brand-sky/30 via-brand-blue/20 to-brand-orange/30 rounded-3xl blur-2xl opacity-75"></div>

                        <!-- Interactive Package Recommender Card -->
                        <div class="relative rounded-3xl border border-brand-sky/40 bg-[#091A2E]/95 p-6 sm:p-7 backdrop-blur-xl shadow-2xl shadow-black/70 overflow-hidden">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between pb-4 border-b border-brand-navy/80">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-xl bg-brand-orange/15 border border-brand-orange/30 flex items-center justify-center text-brand-orange font-bold text-base">
                                        🚀
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-black text-white uppercase tracking-wider">Plan Calculator</h3>
                                        <p class="text-[10px] text-slate-400">Find the perfect internet speed</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 px-2.5 py-1 text-[10px] font-bold text-emerald-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Instant Estimate
                                </span>
                            </div>

                            <!-- Interactive Dynamic Packages Slider / Selector -->
                            <div class="my-5 p-4 rounded-2xl bg-[#061322] border border-brand-navy relative">
                                <div class="flex items-center justify-between text-xs font-bold text-slate-300 mb-2">
                                    <span>Select Internet Speed</span>
                                    <span class="text-brand-orange font-mono font-black text-sm">
                                        {{ activePackage.speed_mbps }} Mbps
                                    </span>
                                </div>

                                <input
                                    v-if="sortedPackages.length > 1"
                                    type="range"
                                    min="0"
                                    :max="sortedPackages.length - 1"
                                    step="1"
                                    v-model.number="selectedPackageIndex"
                                    class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-brand-orange"
                                />

                                <!-- Quick pill buttons for each DB package -->
                                <div class="mt-3 flex items-center justify-between gap-1.5">
                                    <button
                                        v-for="(pkg, idx) in sortedPackages"
                                        :key="pkg.id"
                                        type="button"
                                        @click="selectedPackageIndex = idx"
                                        :class="[
                                            'flex-1 py-1.5 px-1 rounded-xl text-center font-mono font-bold transition text-[11px]',
                                            selectedPackageIndex === idx
                                                ? 'bg-gradient-to-r from-brand-orange to-brand-amber text-white shadow-md shadow-brand-orange/20 border border-brand-orange'
                                                : 'bg-[#0A1D33] text-slate-400 hover:text-white border border-brand-navy hover:border-brand-sky/40'
                                        ]"
                                    >
                                        {{ pkg.speed_mbps }}M
                                    </button>
                                </div>

                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-400 border-t border-brand-navy/60 pt-2">
                                    <span class="text-slate-500">Best for:</span>
                                    <span class="text-brand-sky font-semibold">{{ recommendedDevices }}</span>
                                </div>
                            </div>

                            <!-- Recommended Speed & Plan Display Box -->
                            <div class="p-5 rounded-2xl bg-gradient-to-br from-[#0B2038] to-[#081729] border border-brand-sky/30 relative overflow-hidden">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-brand-sky">Selected Package</span>
                                    <span class="text-[10px] font-bold bg-brand-sky/10 border border-brand-sky/30 px-2 py-0.5 rounded text-brand-sky">
                                        {{ activePackage.code || 'Optical Fiber FTTH' }}
                                    </span>
                                </div>

                                <div class="flex items-end justify-between">
                                    <div>
                                        <div class="text-3xl sm:text-4xl font-black text-white font-mono flex items-baseline gap-1">
                                            {{ activePackage.speed_mbps }} <span class="text-lg text-brand-sky font-sans">Mbps</span>
                                        </div>
                                        <div class="text-xs text-slate-300 font-semibold mt-1">
                                            {{ activePackage.name }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Monthly Rate</div>
                                        <div class="text-2xl sm:text-3xl font-black text-brand-orange font-mono">
                                            ৳{{ activePackage.current_price?.price || '500' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Dynamic Social Media & Bandwidth Perks based on selected DB package -->
                                <div v-if="activePackage.features && activePackage.features.length > 0" class="mt-4 pt-3 border-t border-brand-navy/60">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center justify-between">
                                        <span>Included Speed & Perks</span>
                                        <span class="text-brand-orange font-normal">Buffer Free</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div
                                            v-for="(feat, fIdx) in activePackage.features.filter(item => item.enabled).slice(0, 4)"
                                            :key="fIdx"
                                            class="flex items-center gap-1.5 p-1.5 rounded-lg bg-[#061220]/80 border border-brand-navy text-[11px]"
                                        >
                                            <span class="text-brand-orange font-bold text-xs">✓</span>
                                            <span class="text-slate-300 font-medium truncate">{{ feat.name }}:</span>
                                            <span class="text-brand-sky font-bold font-mono text-[10px] ml-auto whitespace-nowrap">{{ feat.value }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="mt-4 pt-3 border-t border-brand-navy/60 grid grid-cols-2 gap-2 text-[11px] text-slate-300">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>YouTube 100 Mbps</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>Facebook Unlimited</span>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Buttons -->
                            <div class="mt-5 flex items-center gap-3">
                                <a
                                    href="#apply"
                                    @click="selectRecommendedPackage"
                                    class="flex-1 rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber hover:opacity-95 text-center py-3 text-xs font-black text-white shadow-lg shadow-brand-orange/25 transition active:scale-95"
                                >
                                    Get This Connection →
                                </a>
                                <a
                                    href="#packages"
                                    class="rounded-xl border border-brand-navy bg-[#0B1E36] hover:bg-[#102B4D] px-4 py-3 text-xs font-bold text-slate-300 transition text-center"
                                >
                                    View All Plans
                                </a>
                            </div>

                            <!-- Mini Network Badge Footer -->
                            <div class="mt-4 pt-3 border-t border-brand-navy/60 flex items-center justify-between text-[10px] text-slate-400">
                                <span class="flex items-center gap-1">
                                    <span class="text-brand-sky">⚡</span> 1:8 GPON Dedicated Split
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="text-emerald-400">●</span> 24/7 Local NOC Support
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Background decorative elements matching Logo -->
            <div class="absolute -top-24 right-0 h-96 w-96 rounded-full bg-brand-sky/15 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 right-1/4 h-80 w-80 rounded-full bg-brand-orange/10 blur-3xl pointer-events-none"></div>
        </section>

        <!-- Feature Highlights -->
        <section class="py-16 bg-[#071527] border-b border-brand-navy/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="rounded-3xl border border-brand-navy bg-[#091A2E] p-6 space-y-3 hover:border-brand-sky/40 transition">
                    <div class="h-10 w-10 rounded-2xl bg-brand-sky/10 border border-brand-sky/30 flex items-center justify-center text-brand-sky font-bold text-lg">
                        ⚡
                    </div>
                    <h3 class="text-base font-bold text-white">Ultra-Low Ping & Buffer Free</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Direct upstream peering with national Internet Exchanges (BDIX) ensures lightning-fast downloads and seamless video calling.</p>
                </div>

                <div class="rounded-3xl border border-brand-navy bg-[#091A2E] p-6 space-y-3 hover:border-brand-orange/40 transition">
                    <div class="h-10 w-10 rounded-2xl bg-brand-orange/10 border border-brand-orange/30 flex items-center justify-center text-brand-orange font-bold text-lg">
                        🛡️
                    </div>
                    <h3 class="text-base font-bold text-white">99.9% Optical Fiber Uptime</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Redundant optical backbones protect against physical wire damages with automatic rerouting.</p>
                </div>

                <div class="rounded-3xl border border-brand-navy bg-[#091A2E] p-6 space-y-3 hover:border-brand-sky/40 transition">
                    <div class="h-10 w-10 rounded-2xl bg-brand-blue/20 border border-brand-sky/30 flex items-center justify-center text-brand-sky font-bold text-lg">
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
                <span class="text-xs font-bold text-brand-orange uppercase tracking-widest">Affordable Pricing</span>
                <h2 class="text-3xl font-black text-white mt-2">Choose Your Optical Speed Plan</h2>
                <p class="text-xs text-slate-400 mt-2">All plans include unlimited broadband data, zero throttling, and BDIX peering.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="pkg in packages"
                    :key="pkg.id"
                    class="rounded-3xl border border-brand-navy bg-[#091A2E]/80 p-6 flex flex-col justify-between hover:border-brand-orange/60 hover:bg-[#0B2038] transition-all duration-300 shadow-xl"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold font-mono text-brand-sky">{{ pkg.code }}</span>
                            <span class="rounded-full bg-brand-orange/10 border border-brand-orange/30 px-2.5 py-0.5 text-[10px] font-bold text-brand-orange">Fiber FTTH</span>
                        </div>

                        <h3 class="text-lg font-black text-white mt-3">{{ pkg.name }}</h3>
                        <div class="text-3xl font-black text-white font-mono mt-4">
                            {{ pkg.speed_mbps }} <span class="text-sm font-normal text-slate-400">Mbps</span>
                        </div>

                        <div v-if="pkg.recommended_devices" class="mt-2.5 inline-flex items-center gap-1.5 rounded-lg bg-brand-sky/10 border border-brand-sky/20 px-2 py-1 text-[11px] text-brand-sky font-semibold">
                            <span>📱</span>
                            <span>{{ pkg.recommended_devices }}</span>
                        </div>

                        <!-- Perks & Social Media Speeds -->
                        <div class="mt-4 pt-4 border-t border-brand-navy space-y-2 text-xs text-slate-300">
                            <template v-if="pkg.features && pkg.features.length > 0">
                                <div
                                    v-for="(feat, fIndex) in pkg.features.filter(f => f.enabled)"
                                    :key="fIndex"
                                    class="flex items-center justify-between"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <span class="text-brand-orange font-bold">✓</span>
                                        <span>{{ feat.name }}</span>
                                    </span>
                                    <span class="font-mono font-bold text-brand-sky text-[11px]">{{ feat.value }}</span>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex items-center gap-2"><span class="text-brand-orange font-bold">✓</span> Unlimited data usage</div>
                                <div class="flex items-center gap-2"><span class="text-brand-orange font-bold">✓</span> 100 Mbps YouTube & BDIX</div>
                                <div class="flex items-center gap-2"><span class="text-brand-orange font-bold">✓</span> Facebook & IMO Unlimited</div>
                                <div class="flex items-center gap-2"><span class="text-brand-orange font-bold">✓</span> 24/7 dedicated local support</div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-brand-navy flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 block">Monthly Rate</span>
                            <div class="text-xl font-black text-brand-orange font-mono">
                                ৳{{ pkg.current_price?.price || '500' }}
                            </div>
                        </div>

                        <a
                            href="#apply"
                            @click="applyForm.package_id = pkg.id"
                            class="rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue hover:from-brand-cyan hover:to-brand-sky px-4 py-2 text-xs font-bold text-white shadow-lg shadow-brand-sky/20 transition active:scale-95"
                        >
                            Select Plan
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Online Connection Application Form Section -->
        <section id="apply" class="py-20 bg-[#071527] border-t border-brand-navy/60">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl border border-brand-navy bg-[#091A2E]/90 p-8 shadow-2xl backdrop-blur-md">
                    <div class="mb-8">
                        <span class="text-xs font-bold text-brand-orange uppercase tracking-widest">Instant Setup</span>
                        <h2 class="text-2xl font-black text-white mt-1">Apply for a New Optical Connection</h2>
                        <p class="text-xs text-slate-400 mt-1">Fill out the quick form below and our team will survey your location and install fiber within 24 hours.</p>
                    </div>

                    <form @submit.prevent="submitApplication" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Your Full Name *</label>
                                <input v-model="applyForm.name" type="text" placeholder="e.g. Rafiqul Islam" required class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3.5 text-xs text-white placeholder-slate-600 focus:border-brand-sky focus:ring-brand-sky" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Mobile Phone Number *</label>
                                <input v-model="applyForm.phone" type="tel" placeholder="017XXXXXXXX" required class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3.5 text-xs text-white placeholder-slate-600 focus:border-brand-sky focus:ring-brand-sky font-mono" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Email Address</label>
                                <input v-model="applyForm.email" type="email" placeholder="Optional" class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3.5 text-xs text-white placeholder-slate-600 focus:border-brand-sky focus:ring-brand-sky" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Select Area / Zone *</label>
                                <select v-model="applyForm.area_id" required class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3.5 text-xs text-white focus:border-brand-sky focus:ring-brand-sky">
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
                            <label class="block text-xs font-bold text-slate-300 mb-1">Target Package Plan *</label>
                            <select v-model="applyForm.package_id" required class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3.5 text-xs text-white focus:border-brand-sky focus:ring-brand-sky">
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }} ({{ pkg.speed_mbps }} Mbps) — ৳{{ pkg.current_price?.price || 500 }}/month
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Complete Installation Address *</label>
                            <textarea v-model="applyForm.address" rows="2" placeholder="House no, road, village or landmark..." required class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3.5 text-xs text-white placeholder-slate-600 focus:border-brand-sky focus:ring-brand-sky"></textarea>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button
                                type="submit"
                                :disabled="applyForm.processing"
                                class="rounded-2xl bg-gradient-to-r from-brand-orange via-brand-amber to-brand-gold hover:opacity-95 px-8 py-4 text-xs font-black text-white shadow-xl shadow-brand-orange/30 transition active:scale-95 disabled:opacity-50"
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
