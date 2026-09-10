<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

defineProps({
    customer: Object,
    invoices: Object,
});
</script>

<template>
    <Head title="My Invoices - Pirgacha Internet" />

    <CustomerLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-black text-white">Billing Invoices</h1>
                <p class="text-xs text-slate-400 mt-0.5">View all itemized monthly bills and charges</p>
            </div>
            <Link
                :href="route('account.renewal')"
                class="rounded-2xl bg-cyan-600 hover:bg-cyan-500 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-cyan-600/20"
            >
                Pay & Renew
            </Link>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-900/90 text-[11px] font-extrabold uppercase text-slate-400">
                        <tr>
                            <th class="p-4">Invoice #</th>
                            <th class="p-4">Period</th>
                            <th class="p-4">Due Date</th>
                            <th class="p-4">Items</th>
                            <th class="p-4 text-right">Total Amount</th>
                            <th class="p-4 text-right">Paid</th>
                            <th class="p-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-slate-800/40 transition">
                            <td class="p-4 font-mono font-bold text-white">{{ inv.invoice_number }}</td>
                            <td class="p-4 text-slate-400">{{ inv.period_start }} to {{ inv.period_end }}</td>
                            <td class="p-4 font-mono text-amber-400">{{ inv.due_date }}</td>
                            <td class="p-4">
                                <div v-for="item in inv.items" :key="item.id" class="text-[11px] text-slate-400">
                                    • {{ item.description }}
                                </div>
                            </td>
                            <td class="p-4 text-right font-mono font-bold text-white">৳{{ inv.total }}</td>
                            <td class="p-4 text-right font-mono text-emerald-400">৳{{ inv.paid_amount }}</td>
                            <td class="p-4 text-center">
                                <span 
                                    :class="[
                                        inv.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'rounded-full border px-2.5 py-0.5 text-[10px] font-extrabold uppercase'
                                    ]"
                                >
                                    {{ inv.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!invoices.data?.length" class="p-8 text-center text-xs text-slate-500">
                No invoices found on record.
            </div>
        </div>
    </CustomerLayout>
</template>
