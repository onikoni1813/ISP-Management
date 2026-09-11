<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    customer: Object,
    primaryConnection: Object,
    invoices: Array,
    payments: Array,
    complaints: Array,
});

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
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Active Package -->
            <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Subscribed Package</div>
                <div class="text-xl font-black text-white mt-2">{{ currentPackage?.name || 'Standard Plan' }}</div>
                <div class="text-xs font-bold text-brand-cyan mt-1 font-mono">{{ currentPackage?.speed_mbps || 10 }} Mbps Optical Fiber</div>
            </div>

            <!-- Expiry Date -->
            <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Connection Expiry</div>
                <div class="text-xl font-black text-brand-orange font-mono mt-2">
                    {{ primaryConnection?.expiry_date || 'N/A' }}
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

        <!-- PPPoE & Technical Information Card -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 mb-6 shadow-lg">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Connection Technical Overview</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-mono">
                <div>
                    <span class="text-slate-400 block text-[11px]">PPPoE Username</span>
                    <span class="text-white font-bold">{{ pppoe?.username || 'None configured' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">IP Address</span>
                    <span class="text-brand-sky">{{ primaryConnection?.ip_address || 'Dynamic' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Protocol</span>
                    <span class="text-slate-200 uppercase">{{ primaryConnection?.protocol || 'PPPoE' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Connected Router</span>
                    <span class="text-slate-200">{{ primaryConnection?.router_model || 'Standard ONT' }}</span>
                </div>
            </div>
        </div>

        <!-- Recent Invoices & Recent Payments Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Recent Invoices -->
            <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Recent Invoices</h2>
                    <Link :href="route('account.invoices')" class="text-xs font-bold text-brand-sky hover:text-brand-cyan">
                        View All →
                    </Link>
                </div>

                <div v-if="invoices?.length > 0" class="divide-y divide-brand-navy/80">
                    <div v-for="inv in invoices" :key="inv.id" class="py-3 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-bold text-white font-mono">{{ inv.invoice_number }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Due: {{ inv.due_date }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-mono font-black text-white">৳{{ inv.total }}</div>
                            <span 
                                :class="[
                                    inv.status === 'paid' ? 'text-brand-cyan' : 'text-brand-orange',
                                    'text-[10px] font-bold uppercase'
                                ]"
                            >
                                {{ inv.status }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-400 py-4 text-center">No invoices generated yet.</div>
            </div>

            <!-- Recent Payments -->
            <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Payment History</h2>
                    <Link :href="route('account.payments')" class="text-xs font-bold text-brand-sky hover:text-brand-cyan">
                        View All →
                    </Link>
                </div>

                <div v-if="payments?.length > 0" class="divide-y divide-brand-navy/80">
                    <div v-for="pay in payments" :key="pay.id" class="py-3 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-bold text-white font-mono">{{ pay.payment_number }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ pay.paid_at }} • {{ pay.payment_method }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-mono font-black text-brand-orange">৳{{ pay.amount }}</div>
                            <span class="text-[10px] font-bold text-brand-sky uppercase">{{ pay.status }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-400 py-4 text-center">No payments recorded yet.</div>
            </div>
        </div>
    </CustomerLayout>
</template>
