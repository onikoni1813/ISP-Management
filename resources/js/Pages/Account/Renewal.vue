<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    customer: Object,
    primaryConnection: Object,
});

const currentPackage = props.primaryConnection?.current_package;
const monthlyPrice = parseFloat(currentPackage?.current_price?.price || 500);

const form = useForm({
    validity_days: 30,
    payment_method: 'bkash',
    reference: '',
});

const calculateTotal = () => {
    const mult = form.validity_days / 30;
    return (monthlyPrice * mult).toFixed(2);
};

const submitRenewal = () => {
    form.post(route('account.renewal.store'));
};
</script>

<template>
    <Head title="Renew Connection - Pirgacha Internet" />

    <CustomerLayout>
        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-black text-white">Renew Subscription</h1>
                <p class="text-xs text-slate-400 mt-1">Instant package renewal and connection validity extension</p>
            </div>

            <!-- Current Package Summary -->
            <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Current Plan</span>
                        <h2 class="text-xl font-black text-white mt-1">{{ currentPackage?.name }}</h2>
                        <div class="text-xs text-slate-400 font-mono mt-0.5">{{ currentPackage?.speed_mbps }} Mbps Optical Fiber</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-slate-400">Current Expiry</div>
                        <div class="text-sm font-mono font-bold text-amber-400 mt-0.5">
                            {{ primaryConnection?.expiry_date || 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Renewal Order Form -->
            <form @submit.prevent="submitRenewal" class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-5 shadow-2xl">
                <!-- Select Validity Period -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Extension Period</label>
                    <div class="grid grid-cols-3 gap-3">
                        <button
                            type="button"
                            @click="form.validity_days = 30"
                            :class="[
                                form.validity_days === 30 ? 'border-cyan-500 bg-cyan-500/10 text-white font-bold' : 'border-slate-800 bg-slate-950 text-slate-400',
                                'rounded-2xl border p-4 text-center transition'
                            ]"
                        >
                            <div class="text-sm">30 Days</div>
                            <div class="text-xs text-cyan-400 font-mono mt-1">৳{{ (monthlyPrice * 1).toFixed(0) }}</div>
                        </button>

                        <button
                            type="button"
                            @click="form.validity_days = 60"
                            :class="[
                                form.validity_days === 60 ? 'border-cyan-500 bg-cyan-500/10 text-white font-bold' : 'border-slate-800 bg-slate-950 text-slate-400',
                                'rounded-2xl border p-4 text-center transition'
                            ]"
                        >
                            <div class="text-sm">60 Days</div>
                            <div class="text-xs text-cyan-400 font-mono mt-1">৳{{ (monthlyPrice * 2).toFixed(0) }}</div>
                        </button>

                        <button
                            type="button"
                            @click="form.validity_days = 90"
                            :class="[
                                form.validity_days === 90 ? 'border-cyan-500 bg-cyan-500/10 text-white font-bold' : 'border-slate-800 bg-slate-950 text-slate-400',
                                'rounded-2xl border p-4 text-center transition'
                            ]"
                        >
                            <div class="text-sm">90 Days</div>
                            <div class="text-xs text-cyan-400 font-mono mt-1">৳{{ (monthlyPrice * 3).toFixed(0) }}</div>
                        </button>
                    </div>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Payment Channel</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label 
                            :class="[
                                form.payment_method === 'bkash' ? 'border-pink-500/50 bg-pink-500/10 text-white' : 'border-slate-800 bg-slate-950 text-slate-400',
                                'flex items-center justify-center rounded-2xl border p-3.5 cursor-pointer font-bold text-xs transition'
                            ]"
                        >
                            <input type="radio" v-model="form.payment_method" value="bkash" class="sr-only" />
                            <span>bKash Online</span>
                        </label>

                        <label 
                            :class="[
                                form.payment_method === 'nagad' ? 'border-orange-500/50 bg-orange-500/10 text-white' : 'border-slate-800 bg-slate-950 text-slate-400',
                                'flex items-center justify-center rounded-2xl border p-3.5 cursor-pointer font-bold text-xs transition'
                            ]"
                        >
                            <input type="radio" v-model="form.payment_method" value="nagad" class="sr-only" />
                            <span>Nagad Pay</span>
                        </label>

                        <label 
                            :class="[
                                form.payment_method === 'cash' ? 'border-emerald-500/50 bg-emerald-500/10 text-white' : 'border-slate-800 bg-slate-950 text-slate-400',
                                'flex items-center justify-center rounded-2xl border p-3.5 cursor-pointer font-bold text-xs transition'
                            ]"
                        >
                            <input type="radio" v-model="form.payment_method" value="cash" class="sr-only" />
                            <span>Field Cash</span>
                        </label>
                    </div>
                </div>

                <!-- Transaction Reference -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                        Transaction ID / TrxID (For Mobile Wallet)
                    </label>
                    <input
                        v-model="form.reference"
                        type="text"
                        placeholder="e.g. 9JA882LK1"
                        class="w-full rounded-2xl border-slate-800 bg-slate-950 p-3.5 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-cyan-500 font-mono"
                    />
                </div>

                <!-- Total Summary & Submit -->
                <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 block">Total Payable</span>
                        <div class="text-2xl font-black text-cyan-400 font-mono">৳{{ calculateTotal() }}</div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-2xl bg-gradient-to-r from-cyan-600 to-teal-500 hover:from-cyan-500 hover:to-teal-400 px-6 py-3.5 text-xs font-extrabold text-white shadow-xl shadow-cyan-600/30 transition active:scale-95 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Processing...' : 'Confirm & Renew Now' }}
                    </button>
                </div>
            </form>
        </div>
    </CustomerLayout>
</template>
