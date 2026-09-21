<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    payments: Object,
    filters: Object,
});

const reverseForm = useForm({
    reason: '',
});

const approveForm = useForm({});
const showApproveConfirm = ref(false);
const paymentToApprove = ref(null);

const handleReverse = (paymentId) => {
    const reason = prompt('Please enter reversal reason (Required):');
    if (!reason) return;

    reverseForm.reason = reason;
    reverseForm.post(route('admin.billing.payments.reverse', paymentId));
};

const handleApprove = (pay) => {
    paymentToApprove.value = pay;
    showApproveConfirm.value = true;
};

const confirmApprovePayment = () => {
    if (!paymentToApprove.value) return;
    approveForm.post(route('admin.billing.payments.approve', paymentToApprove.value.id), {
        preserveScroll: true,
        onFinish: () => {
            showApproveConfirm.value = false;
            paymentToApprove.value = null;
        }
    });
};

const filterStatus = (status) => {
    router.get(route('admin.billing.payments'), {
        ...props.filters,
        status: status || undefined,
    }, { preserveState: true });
};
</script>

<template>
    <Head title="Payment Collections - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Payment Collections & Approvals</h1>
                <p class="text-sm text-slate-400 mt-1">Audit customer payments, approve field staff collections, and generate invoices.</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Status Filter Pills -->
                <div class="flex p-1 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold">
                    <button
                        @click="filterStatus('')"
                        :class="[!filters.status ? 'bg-slate-800 text-white shadow' : 'text-slate-400 hover:text-white', 'px-3 py-1.5 rounded-lg transition']"
                    >
                        All
                    </button>
                    <button
                        @click="filterStatus('pending')"
                        :class="[filters.status === 'pending' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'text-slate-400 hover:text-white', 'px-3 py-1.5 rounded-lg transition flex items-center gap-1.5']"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        Pending Approval
                    </button>
                    <button
                        @click="filterStatus('completed')"
                        :class="[filters.status === 'completed' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'text-slate-400 hover:text-white', 'px-3 py-1.5 rounded-lg transition']"
                    >
                        Completed
                    </button>
                </div>

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
                            <th class="px-5 py-4">Amount & Discount</th>
                            <th class="px-5 py-4">Method & Account</th>
                            <th class="px-5 py-4">Paid At</th>
                            <th class="px-5 py-4">Collector</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <tr v-for="pay in payments.data" :key="pay.id" class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4">
                                <div class="font-mono font-bold text-emerald-400">{{ pay.payment_number }}</div>
                                <div v-if="pay.generated_invoice" class="text-[10px] font-mono text-indigo-400 mt-0.5">
                                    Inv: {{ pay.generated_invoice.invoice_number }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <Link :href="route('admin.customers.show', pay.customer_id)" class="font-bold text-white hover:text-indigo-300">
                                    {{ pay.customer?.name }}
                                </Link>
                                <div class="text-xs text-slate-500 font-mono">{{ pay.customer?.customer_code }}</div>
                            </td>
                            <td class="px-5 py-4 font-mono">
                                <div class="font-extrabold text-white text-base">৳{{ pay.amount }}</div>
                                <div v-if="pay.discount > 0" class="text-[11px] text-brand-amber font-semibold">
                                    ছাড়: ৳{{ pay.discount }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="capitalize font-semibold text-slate-200">{{ pay.payment_method }}</span>
                                <div class="text-xs text-slate-500">{{ pay.account?.name || 'Cash in Hand' }}</div>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-300 font-mono">
                                {{ formatDateTime(pay.paid_at) }}
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400">
                                <div class="font-medium text-slate-300">{{ pay.collector?.name || 'System' }}</div>
                                <div v-if="pay.approver" class="text-[10px] text-emerald-400">Approved by {{ pay.approver?.name }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    :class="[
                                        pay.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : '',
                                        pay.status === 'pending' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30 animate-pulse' : '',
                                        pay.status === 'reversed' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : '',
                                        'inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-bold uppercase'
                                    ]"
                                >
                                    {{ pay.status === 'pending' ? 'অপেক্ষমাণ (Pending)' : pay.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-2 whitespace-nowrap">
                                <!-- Approve Button for Pending Payments -->
                                <button
                                    v-if="pay.status === 'pending'"
                                    @click="handleApprove(pay)"
                                    :disabled="approveForm.processing"
                                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white shadow-lg shadow-emerald-600/30 transition cursor-pointer"
                                >
                                    <span>✓</span>
                                    <span>এপ্রুভ করুন</span>
                                </button>

                                <Link
                                    v-if="pay.status === 'completed'"
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

        <!-- Professional Approve Payment Modal -->
        <ConfirmModal
            :show="showApproveConfirm"
            :title="'বিল কালেকশন অনুমোদন (Approve Collection)'"
            :message="`আপনি কি গ্রাহক &quot;${paymentToApprove?.customer?.name}&quot;-এর ৳${paymentToApprove?.amount} টাকার বিল পেমেন্টটি নিশ্চিতভাবে অনুমোদন করতে চান? অনুমোদনের সাথে সাথে ইনভয়েস জেনারেট হবে এবং গ্রাহকের ব্যালেন্স আপডেট হবে।`"
            confirm-text="কালেকশন অনুমোদন করুন"
            cancel-text="বাতিল"
            type="success"
            :processing="approveForm.processing"
            @confirm="confirmApprovePayment"
            @cancel="showApproveConfirm = false; paymentToApprove = null;"
        />
    </AdminLayout>
</template>
