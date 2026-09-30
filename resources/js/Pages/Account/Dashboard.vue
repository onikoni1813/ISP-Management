<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { formatDate, formatDateTime } from '@/Utils/date';

const props = defineProps({
    customer: Object,
    primaryConnection: Object,
    payments: Array,
    invoices: Array,
    complaints: Array,
});

const showPassword = ref(false);
const currentPackage = props.primaryConnection?.current_package;
const currentPrice = currentPackage?.current_price;
const pppoe = props.primaryConnection?.pppoe_credential;
const balance = parseFloat(props.customer?.balance || 0);
const dueAmount = Math.max(0, -balance);
</script>

<template>
    <Head title="My Subscriber Account - Pirgacha Internet" />

    <CustomerLayout>
        <!-- Welcome Hero & Connection Health Status -->
        <div class="relative overflow-hidden rounded-3xl border border-brand-navy bg-[#071527] p-6 md:p-8 shadow-2xl mb-6">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span 
                            :class="[
                                customer.status === 'active' ? 'bg-brand-cyan/15 text-brand-cyan border-brand-cyan/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                                'inline-flex items-center rounded-full border px-3 py-1 text-xs font-black uppercase tracking-wider'
                            ]"
                        >
                            <span :class="[customer.status === 'active' ? 'bg-brand-cyan' : 'bg-rose-400', 'h-2 w-2 rounded-full mr-1.5']"></span>
                            {{ customer.status }}
                        </span>
                        <span class="text-xs font-mono text-brand-sky bg-brand-sky/10 border border-brand-sky/20 px-2.5 py-1 rounded-full">
                            {{ customer.customer_code }}
                        </span>
                    </div>

                    <h1 class="text-2xl md:text-3xl font-black text-white mt-3">{{ customer.name }}</h1>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ customer.installation_address?.full_address || customer.area?.name }}
                    </p>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('account.renewal')"
                        class="rounded-2xl bg-gradient-to-r from-brand-orange to-brand-amber hover:opacity-95 px-5 py-3 text-xs font-extrabold text-white shadow-xl shadow-brand-orange/25 transition active:scale-95"
                    >
                        ⚡ Renew Connection
                    </Link>
                    <Link
                        :href="route('account.complaints')"
                        class="rounded-2xl border border-brand-navy bg-[#0B1E36] hover:bg-brand-navy/60 px-5 py-3 text-xs font-extrabold text-slate-200 transition"
                    >
                        💬 Support Ticket
                    </Link>
                </div>
            </div>

            <!-- Background decorative glow -->
            <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-brand-sky/10 blur-3xl pointer-events-none"></div>
        </div>

        <!-- 4 Key Subscriber Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Active Package -->
            <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg relative">
                <div class="flex justify-between items-start">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Subscribed Package</div>
                    <Link :href="route('account.upgrade')" class="text-[10px] font-bold uppercase tracking-wider bg-brand-navy hover:bg-brand-sky/20 border border-brand-navy hover:border-brand-sky/40 text-brand-sky px-2 py-1 rounded-lg transition shrink-0">
                        Change Plan
                    </Link>
                </div>
                <div class="text-xl font-black text-white mt-2">{{ currentPackage?.name || 'Standard Plan' }}</div>
                <div class="text-xs font-bold text-brand-cyan mt-1 font-mono">{{ currentPackage?.speed_mbps || 10 }} Mbps Optical Fiber</div>
            </div>

            <!-- Expiry Date -->
            <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Connection Expiry</div>
                <div class="text-xl font-black text-brand-orange font-mono mt-2">
                    {{ formatDate(primaryConnection?.expiry_date) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">Daily Automated Expiry Check</div>
            </div>

            <!-- Monthly Bill -->
            <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Monthly Rate</div>
                <div class="text-xl font-black text-white font-mono mt-2">
                    ৳{{ currentPrice?.price || '0.00' }}
                </div>
                <div class="text-xs text-slate-400 mt-1">Every {{ currentPrice?.validity_days || 30 }} Days Cycle</div>
            </div>

            <!-- Outstanding Due -->
            <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Current Outstanding</div>
                <div :class="[dueAmount > 0 ? 'text-brand-orange' : 'text-brand-cyan', 'text-xl font-black font-mono mt-2']">
                    ৳{{ dueAmount.toFixed(2) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    {{ dueAmount > 0 ? 'Payment Required' : 'All Dues Clear' }}
                </div>
            </div>
        </div>

        <!-- PPPoE Only Technical Information Card -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 mb-6 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Connection Technical Overview</h2>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    {{ pppoe?.status || 'Active' }}
                </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
                <div class="rounded-2xl bg-[#040D18] border border-brand-navy/60 p-3.5">
                    <span class="text-slate-400 block text-[11px] font-sans">PPPoE Username / ID</span>
                    <span class="text-white font-bold text-sm select-all mt-0.5 block">{{ pppoe?.username || 'None configured' }}</span>
                </div>
                <div class="rounded-2xl bg-[#040D18] border border-brand-navy/60 p-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 block text-[11px] font-sans">PPPoE Password</span>
                        <button
                            v-if="pppoe?.password"
                            type="button"
                            @click="showPassword = !showPassword"
                            class="text-[10px] text-brand-sky hover:text-brand-cyan transition font-sans font-semibold cursor-pointer"
                        >
                            {{ showPassword ? 'Hide' : 'Show' }}
                        </button>
                    </div>
                    <span class="text-brand-orange font-bold text-sm select-all mt-0.5 block">
                        {{ showPassword ? pppoe?.password : '••••••••••••' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Recent Invoices Section -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm">🧾</span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">My Billing Invoices</h2>
                </div>
                <Link :href="route('account.invoices')" class="text-xs font-bold text-brand-sky hover:text-brand-cyan transition">
                    View All Invoices →
                </Link>
            </div>

            <div v-if="invoices?.length > 0" class="divide-y divide-brand-navy/80">
                <div v-for="inv in invoices" :key="inv.id" class="py-3 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-white font-mono">{{ inv.invoice_number }}</span>
                            <span
                                :class="[
                                    inv.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : '',
                                    inv.status === 'partial' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                                    inv.status === 'unpaid' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : '',
                                    'rounded-full border px-2 py-0.5 text-[9px] font-extrabold uppercase'
                                ]"
                            >
                                {{ inv.status }}
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Due: {{ formatDate(inv.due_date) }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <div class="text-sm font-mono font-bold text-white">৳{{ Number(inv.total) }}</div>
                            <div v-if="Number(inv.due_amount) > 0" class="text-[10px] text-rose-400 font-mono font-semibold">
                                Due: ৳{{ Number(inv.due_amount) }}
                            </div>
                        </div>
                        <Link
                            :href="route('account.invoices.show', inv.id)"
                            class="px-2.5 py-1 rounded-lg border border-slate-700 bg-slate-800 text-[11px] font-bold text-brand-sky hover:bg-slate-700 hover:text-white transition flex items-center gap-1"
                        >
                            <span>🖨️</span>
                            <span>Print</span>
                        </Link>
                    </div>
                </div>
            </div>
            <div v-else class="text-xs text-slate-400 py-4 text-center">No invoices issued yet.</div>
        </div>

        <!-- Recent Payments / Payment History -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Payment History</h2>
                <Link :href="route('account.payments')" class="text-xs font-bold text-brand-sky hover:text-brand-cyan transition">
                    View All →
                </Link>
            </div>

            <div v-if="payments?.length > 0" class="divide-y divide-brand-navy/80">
                <div v-for="pay in payments" :key="pay.id" class="py-3 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-bold text-white font-mono">{{ pay.payment_number }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">{{ formatDateTime(pay.paid_at) }} • {{ pay.payment_method }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-mono font-black text-brand-orange">৳{{ pay.amount }}</div>
                        <span class="text-[10px] font-bold text-brand-sky uppercase">{{ pay.status }}</span>
                    </div>
                </div>
            </div>
            <div v-else class="text-xs text-slate-400 py-4 text-center">No payments recorded yet.</div>
        </div>
    </CustomerLayout>
</template>
