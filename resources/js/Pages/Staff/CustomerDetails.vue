<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';

const props = defineProps({
    customer: Object,
    canViewPppoePassword: Boolean,
});

const primaryConnection = props.customer.connections?.[0] || null;
const pppoe = primaryConnection?.pppoe_credential || null;

// Reveal PPPoE Password Modal
const revealedPassword = ref(null);
const isRevealing = ref(false);

const revealPassword = async () => {
    if (!pppoe?.id) return;
    isRevealing.value = true;
    try {
        const res = await axios.post(route('admin.pppoe.reveal-password', pppoe.id));
        revealedPassword.value = res.data.password;
    } catch (e) {
        alert('Unauthorized to view password.');
    } finally {
        isRevealing.value = false;
    }
};

import { syncService } from '@/Services/syncService';

// Quick Pay Modal/Form
const showPayModal = ref(false);
const paySuccessNotice = ref('');
const payForm = useForm({
    amount: primaryConnection?.current_package?.current_price?.price || 500,
    payment_method: 'cash',
    notes: 'Field collection',
});

const submitPayment = async () => {
    if (!navigator.onLine) {
        // Enqueue offline payment mutation
        await syncService.collectPaymentOffline(props.customer, {
            amount: payForm.amount,
            payment_method: payForm.payment_method,
            notes: payForm.notes,
        });
        showPayModal.value = false;
        paySuccessNotice.value = `Payment of ৳${payForm.amount} queued offline. Will sync when online.`;
        setTimeout(() => { paySuccessNotice.value = ''; }, 4000);
        return;
    }

    payForm.post(route('customers.pay', props.customer.id), {
        onSuccess: () => {
            showPayModal.value = false;
        }
    });
};

// Quick Renew Modal/Form
const showRenewModal = ref(false);
const renewSuccessNotice = ref('');
const renewForm = useForm({
    validity_days: 30,
    is_zero_charge: false,
    mode: 'standard',
    collect_payment: true,
    payment_method: 'cash',
});

const submitRenewal = async () => {
    if (!primaryConnection) return;

    if (!navigator.onLine) {
        // Enqueue offline renewal mutation
        await syncService.renewConnectionOffline(props.customer, primaryConnection.id, {
            validity_days: renewForm.validity_days,
            is_zero_charge: renewForm.is_zero_charge,
            mode: renewForm.mode,
            collect_payment: renewForm.collect_payment,
            payment_method: renewForm.payment_method,
        });
        showRenewModal.value = false;
        renewSuccessNotice.value = `Renewal for ${renewForm.validity_days} days queued offline. Will sync when online.`;
        setTimeout(() => { renewSuccessNotice.value = ''; }, 4000);
        return;
    }

    renewForm.post(route('customers.renew', {
        customer: props.customer.id,
        connection: primaryConnection.id,
    }), {
        onSuccess: () => {
            showRenewModal.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`${customer.name} - Field Operations`" />

    <StaffLayout>
        <div class="mb-4">
            <Link :href="route('staff.dashboard')" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1">
                ← Back to Dashboard
            </Link>
        </div>

        <!-- Offline Queue Notification Banners -->
        <div v-if="paySuccessNotice || renewSuccessNotice" class="mb-4 rounded-2xl border border-brand-orange/40 bg-brand-orange/10 p-3.5 text-xs font-bold text-brand-amber flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-brand-orange animate-pulse"></span>
            <span>{{ paySuccessNotice || renewSuccessNotice }}</span>
        </div>

        <!-- Customer Identity Card -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-md mb-4 shadow-xl">
            <div class="flex items-start justify-between">
                <div>
                    <span 
                        :class="[
                            customer.status === 'active' ? 'bg-brand-cyan/15 text-brand-cyan border-brand-cyan/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                            'inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-extrabold uppercase'
                        ]"
                    >
                        {{ customer.status }}
                    </span>
                    <h1 class="text-xl font-black text-white mt-1.5">{{ customer.name }}</h1>
                    <div class="text-xs font-mono text-brand-sky mt-0.5">{{ customer.customer_code }}</div>
                </div>

                <div class="text-right">
                    <a :href="`tel:${customer.primary_contact?.phone}`" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue px-3.5 py-2 text-xs font-bold text-white shadow-lg shadow-brand-navy/50">
                        📞 Call Customer
                    </a>
                </div>
            </div>

            <!-- Quick Action Buttons on Field -->
            <div class="grid grid-cols-2 gap-3 mt-5 pt-4 border-t border-brand-navy">
                <button
                    @click="showPayModal = true"
                    class="rounded-2xl bg-gradient-to-r from-brand-orange to-brand-amber hover:opacity-95 p-3 text-center text-xs font-bold text-white shadow-lg shadow-brand-orange/25 transition"
                >
                    💰 Collect Payment
                </button>
                <button
                    @click="showRenewModal = true"
                    class="rounded-2xl bg-gradient-to-r from-brand-sky to-brand-blue hover:opacity-95 p-3 text-center text-xs font-bold text-white shadow-lg shadow-brand-navy/50 transition"
                >
                    ⚡ Renew Connection
                </button>
            </div>
        </div>

        <!-- Connection & Package Details -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm space-y-4 mb-4 shadow-lg">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Connection Details</h2>
            
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-400">Package:</span>
                    <div class="text-sm font-bold text-white mt-0.5">{{ primaryConnection?.current_package?.name }}</div>
                </div>
                <div>
                    <span class="text-slate-400">Bandwidth:</span>
                    <div class="text-sm font-bold text-brand-cyan mt-0.5">{{ primaryConnection?.current_package?.speed_mbps }} Mbps</div>
                </div>
                <div>
                    <span class="text-slate-400">Monthly Rate:</span>
                    <div class="text-sm font-bold text-white mt-0.5">৳{{ primaryConnection?.current_package?.current_price?.price }}</div>
                </div>
                <div>
                    <span class="text-slate-400">Expiry Date:</span>
                    <div class="text-sm font-mono font-bold text-brand-orange mt-0.5">{{ primaryConnection?.expiry_date || 'N/A' }}</div>
                </div>
            </div>

            <!-- PPPoE Credential for Technician -->
            <div class="mt-4 pt-4 border-t border-brand-navy">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-400">PPPoE Username:</span>
                    <span class="text-xs font-mono font-bold text-white">{{ pppoe?.username || 'None' }}</span>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-slate-400">PPPoE Password:</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-bold text-brand-sky">{{ revealedPassword || '••••••••' }}</span>
                        <button
                            v-if="canViewPppoePassword && !revealedPassword"
                            @click="revealPassword"
                            class="text-[10px] font-bold text-brand-orange underline"
                        >
                            Reveal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Info -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm text-xs space-y-2 shadow-lg">
            <h2 class="font-bold uppercase tracking-wider text-slate-400">Location & Area</h2>
            <div>
                <span class="text-slate-400">Area / Zone:</span>
                <span class="text-slate-200 font-semibold ml-1">{{ customer.area?.name }}</span>
            </div>
            <div>
                <span class="text-slate-400">Address:</span>
                <span class="text-slate-300 ml-1">{{ customer.installation_address?.full_address }}</span>
            </div>
        </div>

        <!-- Pay Modal -->
        <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-3xl border border-brand-navy bg-[#071527] p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-white">Record Payment Collection</h3>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Amount (BDT)</label>
                    <input v-model="payForm.amount" type="number" class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-3 text-white font-mono focus:border-brand-orange focus:ring-brand-orange" />
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Payment Method</label>
                    <select v-model="payForm.payment_method" class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-3 text-white focus:border-brand-sky focus:ring-brand-sky">
                        <option value="cash">Cash in Hand</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                    </select>
                </div>
                <div class="flex gap-2 pt-2">
                    <button @click="showPayModal = false" class="flex-1 rounded-xl bg-[#0B1E36] border border-brand-navy p-2.5 text-xs font-semibold text-slate-300 hover:text-white">Cancel</button>
                    <button @click="submitPayment" :disabled="payForm.processing" class="flex-1 rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber p-2.5 text-xs font-bold text-white shadow-lg shadow-brand-orange/20">Confirm</button>
                </div>
            </div>
        </div>

        <!-- Renew Modal -->
        <div v-if="showRenewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-3xl border border-brand-navy bg-[#071527] p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-white">Renew Connection</h3>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Renewal Days</label>
                    <input v-model="renewForm.validity_days" type="number" class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-3 text-white font-mono focus:border-brand-sky focus:ring-brand-sky" />
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="renewForm.is_zero_charge" id="zeroChargeStaff" type="checkbox" class="rounded bg-[#0B1E36] border-brand-navy text-brand-sky" />
                    <label for="zeroChargeStaff" class="text-xs text-slate-300">Zero Charge Validity Adjustment</label>
                </div>
                <div class="flex gap-2 pt-2">
                    <button @click="showRenewModal = false" class="flex-1 rounded-xl bg-[#0B1E36] border border-brand-navy p-2.5 text-xs font-semibold text-slate-300 hover:text-white">Cancel</button>
                    <button @click="submitRenewal" :disabled="renewForm.processing" class="flex-1 rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue p-2.5 text-xs font-bold text-white shadow-lg shadow-brand-navy/40">Submit Renewal</button>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
