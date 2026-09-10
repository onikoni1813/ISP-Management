<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    renewals: Object,
    filters: Object,
});
</script>

<template>
    <Head title="Renewal Transactions - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Renewal Ledger</h1>
                <p class="text-sm text-slate-400 mt-1">Audit customer package renewals, expiry date extensions, and zero-charge validity adjustments.</p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.billing.invoices')"
                    class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition"
                >
                    Invoices
                </Link>
                <Link
                    :href="route('admin.billing.payments')"
                    class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition"
                >
                    Payments
                </Link>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-950/50 text-xs uppercase font-semibold text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Renewal #</th>
                            <th class="px-5 py-4">Customer</th>
                            <th class="px-5 py-4">Package</th>
                            <th class="px-5 py-4">Validity Shift</th>
                            <th class="px-5 py-4">Amount</th>
                            <th class="px-5 py-4">Type</th>
                            <th class="px-5 py-4">Renewed By</th>
                            <th class="px-5 py-4">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <tr v-for="ren in renewals.data" :key="ren.id" class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4 font-mono font-bold text-indigo-400">
                                {{ ren.renewal_number }}
                            </td>
                            <td class="px-5 py-4">
                                <Link :href="route('admin.customers.show', ren.customer_id)" class="font-bold text-white hover:text-indigo-300">
                                    {{ ren.customer?.name }}
                                </Link>
                                <div class="text-xs text-slate-500 font-mono">{{ ren.customer?.customer_code }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-semibold text-emerald-400">{{ ren.package?.name }}</span>
                                <div class="text-xs text-slate-500">{{ ren.validity_days }} Days</div>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs">
                                <div class="text-slate-400">Prev: {{ ren.previous_expiry || 'N/A' }}</div>
                                <div class="text-emerald-400 font-bold">New: {{ ren.new_expiry }}</div>
                            </td>
                            <td class="px-5 py-4 font-mono font-extrabold text-white">
                                <span v-if="ren.is_zero_charge" class="rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 text-xs">
                                    ৳0 (Zero Charge)
                                </span>
                                <span v-else>
                                    ৳{{ ren.amount }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-bold uppercase text-slate-300">
                                    {{ ren.renewal_type }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400">
                                {{ ren.renewer?.name || 'System' }}
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-slate-400">
                                {{ ren.renewed_at }}
                            </td>
                        </tr>
                        <tr v-if="renewals.data.length === 0">
                            <td colspan="8" class="px-5 py-8 text-center text-slate-500">
                                No renewal transactions found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
