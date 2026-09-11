<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    payment: Object,
});

const printReceipt = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Receipt #${payment.payment_number}`" />

    <div class="min-h-screen bg-slate-950 text-slate-100 p-4 md:p-8 flex justify-center items-center print:bg-white print:text-black print:p-0">
        <div class="w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900/90 p-6 md:p-8 shadow-2xl backdrop-blur-md print:border-none print:shadow-none print:bg-transparent">
            <!-- Receipt Header -->
            <div class="text-center pb-6 border-b border-slate-800 print:border-slate-300">
                <div class="text-xl font-black text-white print:text-black tracking-tight">PIRGACHA INTERNET</div>
                <div class="text-xs text-slate-400 print:text-slate-600 mt-1">High-Speed Optical Fiber Broadband</div>
                <div class="text-[11px] text-slate-500 print:text-slate-600">Pirgacha Sadar, Rangpur | Support: 01711-000000</div>
                <div class="mt-4 inline-block rounded-full bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-400 print:text-black">
                    MONEY RECEIPT
                </div>
            </div>

            <!-- Receipt Meta -->
            <div class="py-4 border-b border-slate-800 print:border-slate-300 grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-500 print:text-slate-600">Receipt No:</span>
                    <div class="font-mono font-bold text-white print:text-black">{{ payment.payment_number }}</div>
                </div>
                <div class="text-right">
                    <span class="text-slate-500 print:text-slate-600">Payment Date:</span>
                    <div class="font-mono text-slate-300 print:text-black">{{ payment.paid_at }}</div>
                </div>
                <div>
                    <span class="text-slate-500 print:text-slate-600">Customer Name:</span>
                    <div class="font-bold text-white print:text-black">{{ payment.customer?.name }}</div>
                </div>
                <div class="text-right">
                    <span class="text-slate-500 print:text-slate-600">Customer Code:</span>
                    <div class="font-mono font-bold text-indigo-400 print:text-black">{{ payment.customer?.customer_code }}</div>
                </div>
                <div>
                    <span class="text-slate-500 print:text-slate-600">Mobile:</span>
                    <div class="font-mono text-slate-300 print:text-black">{{ payment.customer?.primary_contact?.phone }}</div>
                </div>
                <div class="text-right">
                    <span class="text-slate-500 print:text-slate-600">Payment Method:</span>
                    <div class="capitalize font-semibold text-slate-200 print:text-black">{{ payment.payment_method }} ({{ payment.account?.name }})</div>
                </div>
            </div>

            <!-- Allocation Summary -->
            <div class="py-4 border-b border-slate-800 print:border-slate-300">
                <div class="text-xs font-bold uppercase text-slate-400 print:text-black mb-2">Invoice Allocations</div>
                <div v-for="alloc in payment.allocations" :key="alloc.id" class="flex items-center justify-between text-xs py-1">
                    <span class="font-mono text-slate-300 print:text-black">{{ alloc.invoice?.invoice_number }}</span>
                    <span class="font-mono font-bold text-emerald-400 print:text-black">৳{{ alloc.allocated_amount }}</span>
                </div>
            </div>

            <!-- Total Amount Paid -->
            <div class="py-4 flex items-center justify-between">
                <span class="text-sm font-bold uppercase text-slate-300 print:text-black">Total Paid Amount:</span>
                <span class="text-2xl font-black font-mono text-emerald-400 print:text-black">৳{{ payment.amount }}</span>
            </div>

            <!-- Receipt Actions -->
            <div class="pt-6 flex items-center justify-between print:hidden">
                <button
                    @click="window?.history?.back ? window.history.back() : null"
                    type="button"
                    class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700"
                >
                    ← Back
                </button>
                <button
                    @click="printReceipt"
                    class="rounded-xl bg-indigo-600 hover:bg-indigo-500 px-5 py-2 text-xs font-bold text-white shadow-lg shadow-indigo-600/30"
                >
                    🖨️ Print Receipt
                </button>
            </div>
        </div>
    </div>
</template>
