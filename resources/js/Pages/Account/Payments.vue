<script setup>
import { Head } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

defineProps({
    customer: Object,
    payments: Object,
});
</script>

<template>
    <Head title="Payment History - Pirgacha Internet" />

    <CustomerLayout>
        <div class="mb-6">
            <h1 class="text-xl font-black text-white">Payment Receipts</h1>
            <p class="text-xs text-slate-400 mt-0.5">Historical record of all payments and money receipts</p>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-900/90 text-[11px] font-extrabold uppercase text-slate-400">
                        <tr>
                            <th class="p-4">Receipt #</th>
                            <th class="p-4">Date & Time</th>
                            <th class="p-4">Method</th>
                            <th class="p-4">Reference</th>
                            <th class="p-4 text-right">Amount Paid</th>
                            <th class="p-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="pay in payments.data" :key="pay.id" class="hover:bg-slate-800/40 transition">
                            <td class="p-4 font-mono font-bold text-cyan-400">{{ pay.payment_number }}</td>
                            <td class="p-4 text-slate-400">{{ pay.paid_at }}</td>
                            <td class="p-4 font-semibold uppercase text-white">{{ pay.payment_method }}</td>
                            <td class="p-4 font-mono text-slate-400">{{ pay.reference || 'None' }}</td>
                            <td class="p-4 text-right font-mono font-black text-emerald-400 text-sm">৳{{ pay.amount }}</td>
                            <td class="p-4 text-center">
                                <span class="rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-0.5 text-[10px] font-extrabold uppercase text-emerald-400">
                                    {{ pay.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!payments.data?.length" class="p-8 text-center text-xs text-slate-500">
                No payment history found.
            </div>
        </div>
    </CustomerLayout>
</template>
