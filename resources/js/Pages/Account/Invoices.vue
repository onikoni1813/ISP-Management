<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { formatDate } from '@/Utils/date';

defineProps({
    customer: Object,
    invoices: Object,
});
</script>

<template>
    <Head title="My Invoices - Pirgacha Internet" />

    <CustomerLayout>
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-black text-white">My Billing Invoices</h1>
                <p class="text-xs text-slate-400 mt-0.5">Monthly internet broadband service bills & official invoices</p>
            </div>

            <Link
                :href="route('account.renewal')"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-brand-orange hover:bg-brand-orange/90 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-brand-orange/20 transition active:scale-95"
            >
                <span>⚡</span>
                <span>Pay / Renew Connection</span>
            </Link>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-900/90 text-[11px] font-extrabold uppercase text-slate-400 whitespace-nowrap">
                        <tr>
                            <th class="p-4">Invoice #</th>
                            <th class="p-4">Billing Period</th>
                            <th class="p-4">Due Date</th>
                            <th class="p-4 text-right">Total Amount</th>
                            <th class="p-4 text-right">Paid</th>
                            <th class="p-4 text-right">Due</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-slate-800/40 transition whitespace-nowrap">
                            <td class="p-4 font-mono font-bold text-brand-sky">
                                <Link :href="route('account.invoices.show', inv.id)" class="hover:underline">
                                    {{ inv.invoice_number }}
                                </Link>
                            </td>
                            <td class="p-4 text-slate-400">
                                {{ formatDate(inv.period_start) }} - {{ formatDate(inv.period_end) }}
                            </td>
                            <td class="p-4 font-mono" :class="inv.status === 'unpaid' ? 'text-rose-400 font-bold' : 'text-slate-400'">
                                {{ formatDate(inv.due_date) }}
                            </td>
                            <td class="p-4 text-right font-mono font-bold text-white">৳{{ Number(inv.total) }}</td>
                            <td class="p-4 text-right font-mono font-semibold text-emerald-400">৳{{ Number(inv.paid_amount) }}</td>
                            <td class="p-4 text-right font-mono font-bold" :class="Number(inv.due_amount) > 0 ? 'text-rose-400' : 'text-slate-400'">
                                ৳{{ Number(inv.due_amount) }}
                            </td>
                            <td class="p-4 text-center">
                                <span
                                    :class="[
                                        inv.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : '',
                                        inv.status === 'partial' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : '',
                                        inv.status === 'unpaid' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : '',
                                        'rounded-full border px-2.5 py-0.5 text-[10px] font-extrabold uppercase'
                                    ]"
                                >
                                    {{ inv.status }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <Link
                                    :href="route('account.invoices.show', inv.id)"
                                    class="inline-flex items-center gap-1 rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-[11px] font-bold text-brand-sky hover:bg-slate-700 hover:text-white transition"
                                >
                                    <span>🖨️</span>
                                    <span>Print</span>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!invoices.data?.length" class="p-8 text-center text-xs text-slate-500">
                No billing invoices found.
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="invoices.links && invoices.links.length > 3" class="mt-4 flex items-center justify-between border border-slate-800 rounded-2xl px-4 py-3 text-xs text-slate-400 bg-slate-900/40">
            <div>Showing {{ invoices.from || 0 }} to {{ invoices.to || 0 }} of {{ invoices.total || 0 }} invoices</div>
            <div class="flex items-center gap-1">
                <Link
                    v-for="(link, i) in invoices.links"
                    :key="i"
                    :href="link.url || '#'"
                    v-html="link.label"
                    :class="[
                        link.active ? 'bg-brand-blue text-white font-bold' : 'text-slate-400 hover:bg-slate-800 hover:text-white',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                        'rounded-lg px-2.5 py-1 transition text-xs'
                    ]"
                />
            </div>
        </div>
    </CustomerLayout>
</template>
