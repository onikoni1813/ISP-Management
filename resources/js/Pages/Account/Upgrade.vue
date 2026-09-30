<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    customer: Object,
    primaryConnection: Object,
    packages: Array,
});

const currentPackageId = props.primaryConnection?.current_package_id;
const currentPackage = props.primaryConnection?.current_package;
const currentPackagePrice = parseFloat(props.primaryConnection?.current_package?.current_price?.price || 0);

const form = useForm({
    package_id: '',
});

const selectedPackage = ref(null);
const confirmModal = ref(false);

const isUpgrade = computed(() => {
    if (!selectedPackage.value) return false;
    const newPrice = parseFloat(selectedPackage.value.current_price?.price || 0);
    return newPrice > currentPackagePrice;
});

const isDowngrade = computed(() => {
    if (!selectedPackage.value) return false;
    const newPrice = parseFloat(selectedPackage.value.current_price?.price || 0);
    return newPrice < currentPackagePrice;
});

const openConfirm = (pkg) => {
    if (pkg.id === currentPackageId) return;
    selectedPackage.value = pkg;
    form.package_id = pkg.id;
    confirmModal.value = true;
};

const closeConfirm = () => {
    confirmModal.value = false;
    selectedPackage.value = null;
    form.reset();
};

const submitUpgrade = () => {
    form.post(route('account.upgrade.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeConfirm();
        }
    });
};
</script>

<template>
    <Head title="Change Package - Pirgacha Internet" />

    <CustomerLayout>
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-white">Choose Your Plan</h1>
                    <p class="text-sm text-slate-400 mt-1">Upgrade your speed or downgrade to a budget plan based on your daily needs</p>
                </div>
            </div>

            <!-- Validation Errors -->
            <div v-if="form.errors.error" class="bg-red-500/20 border border-red-500/50 p-4 rounded-2xl flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="text-sm font-bold text-red-400">Plan Change Failed</h3>
                    <p class="text-xs text-red-300 mt-1">{{ form.errors.error }}</p>
                </div>
            </div>

            <!-- Packages Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="pkg in packages" :key="pkg.id" 
                    class="relative rounded-3xl border p-6 flex flex-col h-full transition-all duration-300"
                    :class="[
                        pkg.id === currentPackageId 
                            ? 'border-emerald-500/60 bg-emerald-950/20 shadow-lg shadow-emerald-950/30' 
                            : 'border-slate-800 bg-slate-900/60 hover:border-slate-700 hover:bg-slate-800/80 backdrop-blur-sm'
                    ]"
                >
                    <!-- Badges -->
                    <!-- 1. Current Plan -->
                    <div v-if="pkg.id === currentPackageId" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-emerald-500 text-slate-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg shadow-emerald-500/30 whitespace-nowrap">
                            Current Plan
                        </span>
                    </div>

                    <!-- 2. Upgrade Badge -->
                    <div v-else-if="parseFloat(pkg.current_price?.price || 0) > currentPackagePrice" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-cyan-500 text-slate-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg shadow-cyan-500/25 whitespace-nowrap flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                            </svg>
                            Upgrade
                        </span>
                    </div>

                    <!-- 3. Downgrade Badge -->
                    <div v-else-if="parseFloat(pkg.current_price?.price || 0) < currentPackagePrice" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-amber-500 text-slate-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg shadow-amber-500/25 whitespace-nowrap flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                            Downgrade
                        </span>
                    </div>

                    <div class="mb-6 text-center mt-2">
                        <h3 class="text-lg font-bold text-white mb-2">{{ pkg.name }}</h3>
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="text-3xl font-black text-cyan-400">৳{{ pkg.current_price?.price || 500 }}</span>
                            <span class="text-slate-500 text-sm">/mo</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2 line-clamp-2 min-h-[32px]">{{ pkg.description || `${pkg.speed_mbps} Mbps reliable fiber optic internet` }}</p>
                    </div>

                    <!-- Speed & Features -->
                    <div class="flex-1 space-y-4 mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-cyan-500/20 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dedicated Speed</span>
                                <span class="text-white font-bold">{{ pkg.speed_mbps }} Mbps</span>
                            </div>
                        </div>

                        <div class="w-full h-px bg-slate-800 my-4"></div>

                        <ul class="space-y-3 text-sm">
                            <li v-for="(feat, i) in (pkg.features || [])" :key="i" class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                                <div>
                                    <span class="text-slate-300 font-medium">{{ feat.name }}</span>
                                    <span v-if="feat.value" class="text-slate-500 block text-xs">{{ feat.value }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Action Button -->
                    <button 
                        @click="openConfirm(pkg)"
                        :disabled="pkg.id === currentPackageId"
                        class="w-full rounded-2xl py-3.5 px-4 text-sm font-bold transition-all mt-auto"
                        :class="[
                            pkg.id === currentPackageId
                                ? 'bg-emerald-500/10 text-emerald-400 cursor-default border border-emerald-500/30'
                                : parseFloat(pkg.current_price?.price || 0) < currentPackagePrice
                                    ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-lg shadow-amber-500/25 active:scale-95'
                                    : 'bg-cyan-500 hover:bg-cyan-400 text-slate-950 shadow-lg shadow-cyan-500/25 active:scale-95'
                        ]"
                    >
                        <template v-if="pkg.id === currentPackageId">Active Plan</template>
                        <template v-else-if="parseFloat(pkg.current_price?.price || 0) < currentPackagePrice">
                            Downgrade Plan
                        </template>
                        <template v-else-if="parseFloat(pkg.current_price?.price || 0) > currentPackagePrice">
                            Upgrade Plan
                        </template>
                        <template v-else>
                            Switch Plan
                        </template>
                    </button>
                </div>
            </div>

            <!-- Confirmation Modal -->
            <div v-if="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="closeConfirm"></div>
                <div class="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl overflow-hidden">
                    <!-- Decor Blur -->
                    <div 
                        class="absolute -top-24 -right-24 w-48 h-48 rounded-full blur-3xl pointer-events-none"
                        :class="isDowngrade ? 'bg-amber-500/20' : 'bg-cyan-500/20'"
                    ></div>
                    
                    <div class="relative z-10">
                        <!-- Icon -->
                        <div 
                            class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                            :class="isDowngrade ? 'bg-amber-500/20 text-amber-400' : 'bg-cyan-500/20 text-cyan-400'"
                        >
                            <svg v-if="isDowngrade" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                        </div>
                        
                        <h3 class="text-xl font-bold text-white mb-2">
                            <template v-if="isDowngrade">Confirm Package Downgrade</template>
                            <template v-else-if="isUpgrade">Confirm Package Upgrade</template>
                            <template v-else>Confirm Package Switch</template>
                        </h3>

                        <p class="text-slate-400 text-sm mb-5 leading-relaxed">
                            You are changing your plan to <strong class="text-white">{{ selectedPackage?.name }}</strong> 
                            ({{ selectedPackage?.speed_mbps }} Mbps).
                        </p>

                        <!-- Plan comparison card in modal -->
                        <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 mb-6 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Current Plan:</span>
                                <span class="text-slate-200 font-semibold">{{ currentPackage?.name || 'Standard Plan' }} (৳{{ currentPackagePrice }}/mo)</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">New Plan:</span>
                                <span :class="isDowngrade ? 'text-amber-400' : 'text-cyan-400'" class="font-bold">
                                    {{ selectedPackage?.name }} (৳{{ selectedPackage?.current_price?.price }}/mo)
                                </span>
                            </div>
                            <div class="pt-2 border-t border-slate-800/60 text-[11px] text-slate-500">
                                The new billing rate will take effect starting from your next monthly renewal cycle.
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button 
                                @click="closeConfirm" 
                                class="flex-1 px-4 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-2xl font-bold text-sm transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                @click="submitUpgrade" 
                                :disabled="form.processing"
                                class="flex-1 px-4 py-3 rounded-2xl font-bold text-sm shadow-lg transition-all flex items-center justify-center"
                                :class="[
                                    isDowngrade 
                                        ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-amber-500/25 active:scale-95' 
                                        : 'bg-cyan-500 hover:bg-cyan-400 text-slate-950 shadow-cyan-500/25 active:scale-95'
                                ]"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-950" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <template v-if="isDowngrade">Confirm Downgrade</template>
                                <template v-else-if="isUpgrade">Confirm Upgrade</template>
                                <template v-else>Confirm Switch</template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
