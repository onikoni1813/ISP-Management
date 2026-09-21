<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    invoice: Object,
});

// Resolve PPPoE username
const pppoeUsername = computed(() => {
    const conn = props.invoice?.customer?.connections?.find(c => c.pppoe_credential?.username);
    if (conn?.pppoe_credential?.username) {
        return conn.pppoe_credential.username;
    }
    return props.invoice?.customer?.customer_code || 'subscriber';
});

// Format document title & PDF download name: PPPoE ID + Date (e.g. "monirul-2026-09-16" or "Invoice_monirul_2026-09-16")
const invoiceDateStr = computed(() => {
    return props.invoice?.created_at ? props.invoice.created_at.substring(0, 10) : 'date';
});

const pageTitle = computed(() => {
    return `${pppoeUsername.value}_${invoiceDateStr.value}`;
});

const printInvoice = () => {
    window.print();
};

const backRoute = computed(() => {
    if (typeof window !== 'undefined' && window.location.pathname.startsWith('/account')) {
        return route('account.invoices');
    }
    return route('admin.billing.invoices');
});

const handleBack = () => {
    if (typeof window !== 'undefined' && window.opener && !window.opener.closed) {
        window.close();
        setTimeout(() => {
            router.visit(backRoute.value);
        }, 150);
        return;
    }

    if (typeof window !== 'undefined' && window.history.length > 1 && (window.history.state?.back || (document.referrer && !document.referrer.includes('/invoices/')))) {
        window.history.back();
        return;
    }

    router.visit(backRoute.value);
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="min-h-screen bg-slate-950 text-slate-100 p-3 sm:p-6 md:p-8 flex flex-col justify-start items-center print:block print:bg-white print:text-black print:p-0 print:m-0">
        <!-- Main Standard A4 Proportion Printable Container -->
        <div class="invoice-container w-full max-w-3xl rounded-2xl border border-slate-800 bg-slate-900/95 p-6 sm:p-8 shadow-2xl backdrop-blur-md print:border-none print:shadow-none print:bg-transparent print:p-0 print:m-0 print:w-full print:max-w-none">
            
            <!-- Header & Company Branding -->
            <div class="flex items-start justify-between gap-4 pb-4 border-b-2 border-slate-800 print:border-slate-300">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-white p-2 shadow-md border border-slate-700/50 print:border print:border-slate-300 print:shadow-none">
                        <img src="/logo.png" alt="Pirgacha Internet" class="h-11 w-11 object-contain" />
                    </div>
                    <div>
                        <div class="text-lg sm:text-xl font-black text-white print:text-black tracking-tight leading-tight">PIRGACHA INTERNET</div>
                        <div class="text-[11px] font-semibold text-slate-400 print:text-slate-600">High-Speed Optical Fiber Broadband</div>
                        <div class="text-[10px] text-slate-500 print:text-slate-600 mt-0.5">
                            {{ $page.props.company?.address || 'Pirgacha Sadar, Rangpur' }} | Hotline: {{ $page.props.company?.hotline || '01711-000000' }}
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <div class="inline-block rounded-md px-2.5 py-0.5 text-[11px] font-black tracking-wider uppercase border"
                        :class="[
                            invoice.status === 'paid' 
                                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 print:text-emerald-700 print:border-emerald-600' 
                                : (invoice.status === 'partial' 
                                    ? 'bg-amber-500/10 text-amber-400 border-amber-500/30 print:text-amber-700 print:border-amber-600' 
                                    : 'bg-rose-500/10 text-rose-400 border-rose-500/30 print:text-rose-700 print:border-rose-600')
                        ]"
                    >
                        INVOICE: {{ invoice.status }}
                    </div>
                    <div class="font-mono text-xs font-bold text-slate-200 print:text-black mt-1">
                        {{ invoice.invoice_number }}
                    </div>
                    <div class="text-[10px] text-slate-400 print:text-slate-600 mt-0.5">
                        Date: {{ formatDate(invoice.created_at) }}
                    </div>
                </div>
            </div>

            <!-- Customer & Invoice Meta Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-3.5 border-b border-slate-800 print:border-slate-300 text-xs">
                <div>
                    <span class="text-slate-400 print:text-slate-600 block text-[10px] uppercase font-bold tracking-wider">Subscriber Info</span>
                    <strong class="text-white print:text-black font-bold block text-sm leading-tight mt-0.5">{{ invoice.customer?.name }}</strong>
                    <div class="font-mono text-[11px] text-brand-sky print:text-black font-semibold mt-0.5">
                        PPPoE: {{ pppoeUsername }}
                    </div>
                    <span class="text-slate-400 print:text-slate-600 font-mono text-[10px] block">{{ invoice.customer?.customer_code }}</span>
                </div>
                <div>
                    <span class="text-slate-400 print:text-slate-600 block text-[10px] uppercase font-bold tracking-wider">Contact & Address</span>
                    <span class="text-slate-200 print:text-black font-mono font-medium block mt-0.5">{{ invoice.customer?.primary_contact?.phone || 'N/A' }}</span>
                    <span class="text-slate-400 print:text-slate-600 text-[10px] block leading-tight mt-0.5">
                        {{ invoice.customer?.installation_address?.full_address || 'Pirgacha, Rangpur' }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 print:text-slate-600 block text-[10px] uppercase font-bold tracking-wider">Billing Period</span>
                    <span class="text-slate-200 print:text-black font-mono font-medium block mt-0.5">
                        {{ formatDate(invoice.period_start) }}
                    </span>
                    <span class="text-slate-400 print:text-slate-600 text-[11px] block">to {{ formatDate(invoice.period_end) }}</span>
                </div>
                <div class="text-right">
                    <span class="text-slate-400 print:text-slate-600 block text-[10px] uppercase font-bold tracking-wider">Payment Due Date</span>
                    <span class="font-mono font-bold text-sm block mt-0.5" :class="invoice.status === 'unpaid' ? 'text-rose-400 print:text-rose-700' : 'text-slate-200 print:text-black'">
                        {{ formatDate(invoice.due_date) }}
                    </span>
                </div>
            </div>

            <!-- Itemized Charges Table -->
            <div class="py-3 border-b border-slate-800 print:border-slate-300">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 print:border-slate-300 text-[10px] uppercase tracking-wider text-slate-400 print:text-slate-700 font-bold">
                            <th class="py-2">Item Description</th>
                            <th class="py-2 text-center">Qty</th>
                            <th class="py-2 text-right">Rate</th>
                            <th class="py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 print:divide-slate-200">
                        <tr v-for="item in invoice.items" :key="item.id">
                            <td class="py-2 text-slate-200 print:text-black font-medium">
                                <div>{{ item.description }}</div>
                                <div v-if="item.item_type" class="text-[10px] text-slate-500 print:text-slate-600 font-mono">{{ item.item_type }}</div>
                            </td>
                            <td class="py-2 text-center font-mono text-slate-300 print:text-black">{{ item.quantity || 1 }}</td>
                            <td class="py-2 text-right font-mono text-slate-300 print:text-black">৳{{ Number(item.unit_price).toFixed(2) }}</td>
                            <td class="py-2 text-right font-mono font-bold text-white print:text-black">৳{{ Number(item.total).toFixed(2) }}</td>
                        </tr>
                        <tr v-if="!invoice.items || invoice.items.length === 0">
                            <td class="py-2 text-slate-200 print:text-black font-medium">Monthly Broadband Internet Subscription</td>
                            <td class="py-2 text-center font-mono text-slate-300 print:text-black">1</td>
                            <td class="py-2 text-right font-mono text-slate-300 print:text-black">৳{{ Number(invoice.total).toFixed(2) }}</td>
                            <td class="py-2 text-right font-mono font-bold text-white print:text-black">৳{{ Number(invoice.total).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Financial Calculations Breakdown -->
            <div class="py-3 border-b border-slate-800 print:border-slate-300 flex flex-col sm:flex-row justify-between items-start gap-4">
                <div class="text-xs text-slate-400 print:text-slate-600 max-w-sm space-y-1">
                    <div v-if="invoice.notes"><strong class="text-slate-300 print:text-black">Notes:</strong> {{ invoice.notes }}</div>
                    <div class="text-[10px] text-slate-500 print:text-slate-600">
                        Thank you for using Pirgacha Internet. Keep this invoice for your billing records.
                    </div>
                </div>

                <div class="w-full sm:w-60 space-y-1 text-xs">
                    <div class="flex justify-between text-slate-400 print:text-slate-600">
                        <span>Subtotal:</span>
                        <span class="font-mono text-slate-200 print:text-black">৳{{ Number(invoice.subtotal || invoice.total).toFixed(2) }}</span>
                    </div>
                    <div v-if="Number(invoice.discount) > 0" class="flex justify-between text-amber-400 print:text-amber-700">
                        <span>Discount:</span>
                        <span class="font-mono">- ৳{{ Number(invoice.discount).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-xs text-white print:text-black pt-1 border-t border-slate-800 print:border-slate-300">
                        <span>Total Invoiced:</span>
                        <span class="font-mono text-brand-sky print:text-black">৳{{ Number(invoice.total).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-400 print:text-emerald-700 font-semibold">
                        <span>Amount Paid:</span>
                        <span class="font-mono">৳{{ Number(invoice.paid_amount).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between font-black text-sm pt-1 border-t border-slate-800 print:border-slate-300"
                        :class="Number(invoice.due_amount) > 0 ? 'text-rose-400 print:text-rose-700' : 'text-slate-400 print:text-slate-600'"
                    >
                        <span>Due Balance:</span>
                        <span class="font-mono">৳{{ Number(invoice.due_amount).toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Linked Payment Allocations if any -->
            <div v-if="invoice.allocations && invoice.allocations.length > 0" class="py-2.5 border-b border-slate-800 print:border-slate-300 text-xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 print:text-slate-700 mb-1">
                    Applied Payment Receipts:
                </div>
                <div class="space-y-0.5">
                    <div v-for="alloc in invoice.allocations" :key="alloc.id" class="flex justify-between items-center text-slate-300 print:text-black text-[11px]">
                        <span class="font-mono">
                            Receipt #{{ alloc.payment?.payment_number }} ({{ alloc.payment?.payment_method }}) - {{ formatDate(alloc.payment?.paid_at) }}
                        </span>
                        <span class="font-mono font-bold text-emerald-400 print:text-black">৳{{ Number(alloc.allocated_amount).toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Authorization Signatures for Professional Look -->
            <div class="pt-8 pb-1 grid grid-cols-2 gap-8 text-xs text-center">
                <div>
                    <div class="border-t border-dashed border-slate-700 print:border-slate-400 pt-1.5 w-36 mx-auto text-slate-400 print:text-slate-700 text-[10px]">
                        Customer Signature
                    </div>
                </div>
                <div>
                    <div class="border-t border-dashed border-slate-700 print:border-slate-400 pt-1.5 w-36 mx-auto text-slate-400 print:text-slate-700 text-[10px]">
                        Authorized Signature
                    </div>
                </div>
            </div>

            <!-- Action Buttons (Print / Back) -->
            <div class="pt-5 flex items-center justify-between print:hidden">
                <button
                    @click="handleBack"
                    type="button"
                    class="rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white transition flex items-center gap-1.5 cursor-pointer shadow-sm"
                >
                    <span>←</span>
                    <span>Back</span>
                </button>
                <div class="flex items-center gap-2.5">
                    <button
                        @click="printInvoice"
                        type="button"
                        class="rounded-xl bg-brand-blue hover:bg-brand-sky/90 px-5 py-2 text-xs font-bold text-white shadow-lg shadow-brand-blue/30 flex items-center gap-1.5 cursor-pointer transition active:scale-95"
                    >
                        <span>🖨️</span>
                        <span>Print</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page {
        margin: 0.6cm;
        size: A4 portrait;
    }
    html, body {
        background: white !important;
        color: black !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: auto !important;
    }
    .invoice-container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }
}
</style>
