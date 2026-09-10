<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    invoices: Object,
    filters: Object,
});
</script>

<template>
    <Head title="Invoice Ledger - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Invoice Ledger</h1>
                <p class="text-sm text-slate-400 mt-1">Audit customer invoices, itemizations, and outstanding dues.</p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.billing.payments')"
                    class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition"
                >
                    View All Payments
                </Link>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-950/50 text-xs uppercase font-semibold text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Invoice #</th>
                            <th class="px-5 py-4">Customer</th>
                            <th class="px-5 py-4">Billing Period</th>
                            <th class="px-5 py-4">Due Date</th>
                            <th class="px-5 py-4">Total</th>
                            <th class="px-5 py-4">Paid</th>
                            <th class="px-5 py-4">Due</th>
                            <th class="px-5 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4 font-mono font-bold text-indigo-400">
                                {{ inv.invoice_number }}
                            </td>
                            <td class="px-5 py-4">
                                <Link :href="route('admin.customers.show', inv.customer_id)" class="font-bold text-white hover:text-indigo-300">
                                    {{ inv.customer?.name }}
                                </Link>
                                <div class="text-xs text-slate-500 font-mono">{{ inv.customer?.customer_code }}</div>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400">
                                {{ inv.period_start }} to {{ inv.period_end }}
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-slate-300">
                                {{ inv.due_date }}
                            </td>
                            <td class="px-5 py-4 font-mono font-bold text-white">
                                ৳{{ inv.total }}
                            </td>
                            <td class="px-5 py-4 font-mono font-semibold text-emerald-400">
                                ৳{{ inv.paid_amount }}
                            </td>
                            <td class="px-5 py-4 font-mono font-semibold text-rose-400">
                                ৳{{ inv.due_amount }}
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    :class="[
                                        inv.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : '',
                                        inv.status === 'partial' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                                        inv.status === 'unpaid' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : '',
                                        'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-bold uppercase'
                                    ]"
                                >
                                    {{ inv.status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="8" class="px-5 py-8 text-center text-slate-500">
                                No invoice records generated yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
