<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    payments: Object,
    filters: Object,
});

const reverseForm = useForm({
    reason: '',
});

const handleReverse = (paymentId) => {
    const reason = prompt('Please enter reversal reason (Required):');
    if (!reason) return;

    reverseForm.reason = reason;
    reverseForm.post(route('admin.billing.payments.reverse', paymentId));
};
</script>

<template>
    <Head title="Payment Collections - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Payment Collections</h1>
                <p class="text-sm text-slate-400 mt-1">Audit customer payments, receipts, and multi-invoice allocations.</p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.billing.invoices')"
                    class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition"
                >
                    View Invoices
                </Link>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-950/50 text-xs uppercase font-semibold text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Receipt #</th>
                            <th class="px-5 py-4">Customer</th>
                            <th class="px-5 py-4">Amount</th>
                            <th class="px-5 py-4">Method & Account</th>
                            <th class="px-5 py-4">Paid At</th>
                            <th class="px-5 py-4">Collector</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <tr v-for="pay in payments.data" :key="pay.id" class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4 font-mono font-bold text-emerald-400">
                                {{ pay.payment_number }}
                            </td>
                            <td class="px-5 py-4">
                                <Link :href="route('admin.customers.show', pay.customer_id)" class="font-bold text-white hover:text-indigo-300">
                                    {{ pay.customer?.name }}
                                </Link>
                                <div class="text-xs text-slate-500 font-mono">{{ pay.customer?.customer_code }}</div>
                            </td>
                            <td class="px-5 py-4 font-mono font-extrabold text-white text-base">
                                ৳{{ pay.amount }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="capitalize font-semibold text-slate-200">{{ pay.payment_method }}</span>
                                <div class="text-xs text-slate-500">{{ pay.account?.name || 'Cash in Hand' }}</div>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-300 font-mono">
                                {{ pay.paid_at }}
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400">
                                {{ pay.collector?.name || 'System' }}
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    :class="[
                                        pay.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                        'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-bold uppercase'
                                    ]"
                                >
                                    {{ pay.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-2">
                                <Link
                                    :href="route('admin.billing.receipt', pay.id)"
                                    class="inline-flex items-center gap-1 rounded-lg bg-slate-800 hover:bg-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200 transition"
                                >
                                    Receipt
                                </Link>
                                <button
                                    v-if="pay.status === 'completed'"
                                    @click="handleReverse(pay.id)"
                                    class="inline-flex items-center gap-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 px-3 py-1.5 text-xs font-semibold text-rose-300 transition"
                                >
                                    Reverse
                                </button>
                            </td>
                        </tr>
                        <tr v-if="payments.data.length === 0">
                            <td colspan="8" class="px-5 py-8 text-center text-slate-500">
                                No payment records logged yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
