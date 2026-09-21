<script setup>
import { ref } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    metrics: Object,
    pendingPayments: Array,
    recentPayments: Array,
    recentComplaints: Array,
});

const page = usePage();
const user = page.props.auth.user;

const showApproveConfirm = ref(false);
const paymentToApprove = ref(null);

const approveForm = useForm({});
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

const formatTaka = (amount) => {
    return '৳' + Number(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};
</script>

<template>
    <Head title="Admin Dashboard - Pirgacha Internet" />

    <AdminLayout>
        <!-- Welcome Hero -->
        <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3.5">
            <div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight">
                    Operations Center
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5 sm:mt-1">
                    Welcome back, {{ user?.name }}. Real-time ISP network & financial overview.
                </p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-auto">
                <Link
                    :href="route('admin.customers.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-orange via-brand-amber to-brand-gold hover:opacity-95 px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-black text-white shadow-lg shadow-brand-orange/30 transition active:scale-95"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Customer
                </Link>
            </div>
        </div>

        <!-- Pending Staff Collections Alert Banner (Milestone: Instant Operational Approval) -->
        <div v-if="metrics?.pending_approvals_count > 0" class="mb-6 sm:mb-8 rounded-2xl border border-amber-500/40 bg-gradient-to-r from-amber-500/15 via-[#0B1E36] to-[#071322] p-4 sm:p-5 backdrop-blur-xl shadow-xl shadow-amber-500/5 relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-36 h-36 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 relative z-10">
                <div class="flex items-start gap-3 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/30 shrink-0 mt-0.5 sm:mt-0">
                        <svg class="h-5 w-5 sm:h-6 sm:w-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex h-2 w-2 rounded-full bg-amber-400 animate-ping"></span>
                            <h2 class="text-sm sm:text-base font-bold text-amber-300">স্টাফ পেমেন্ট অনুমোদনের অপেক্ষা</h2>
                            <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] sm:text-xs font-black text-amber-300 border border-amber-500/40 font-mono">
                                {{ metrics.pending_approvals_count }} টি বিল
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                            ফিল্ড স্টাফদের সংগৃহীত সর্বমোট <strong class="text-emerald-400 font-mono text-sm">৳{{ Number(metrics.pending_approvals_amount || 0).toLocaleString('en-IN') }}</strong> এখনো অনুমোদনের অপেক্ষায় আছে।
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-stretch sm:self-auto justify-end shrink-0">
                    <Link
                        :href="route('admin.billing.payments', { status: 'pending' })"
                        class="w-full sm:w-auto text-center inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 px-3.5 py-2 text-xs font-black text-slate-950 shadow-md transition active:scale-95"
                    >
                        <span>অনুমোদন করুন ({{ metrics.pending_approvals_count }})</span>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </Link>
                </div>
            </div>

            <!-- Quick Approvals Preview Drawer if items present -->
            <div v-if="pendingPayments && pendingPayments.length > 0" class="mt-4 pt-3.5 border-t border-amber-500/20 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-3">
                <div v-for="pay in pendingPayments.slice(0, 3)" :key="pay.id" class="p-2.5 sm:p-3 rounded-xl bg-[#061220]/80 border border-white/5 flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-white truncate">{{ pay.customer?.name }}</div>
                        <div class="text-[10px] text-slate-400 font-mono truncate">স্টাফ: {{ pay.collector?.name }} • ৳{{ pay.amount }}</div>
                    </div>
                    <button
                        @click="handleApprove(pay)"
                        class="shrink-0 px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 hover:bg-emerald-500 hover:text-white transition"
                    >
                        Approve ✓
                    </button>
                </div>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
            <Link :href="route('admin.customers.index')" class="rounded-2xl border border-brand-navy bg-[#091A2E]/80 p-3.5 sm:p-5 backdrop-blur-sm hover:border-brand-sky/50 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-slate-400">Customers</span>
                    <span class="rounded-lg bg-brand-sky/10 p-1.5 sm:p-2 text-brand-sky border border-brand-sky/20">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2.5 sm:mt-4 flex items-baseline gap-1.5 sm:gap-2">
                    <span class="text-xl sm:text-3xl font-extrabold text-white tracking-tight">{{ metrics?.total_customers ?? 0 }}</span>
                    <span class="text-[10px] sm:text-xs font-medium text-emerald-400">Live</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5 sm:mt-1 truncate">{{ metrics?.active_customers ?? 0 }} Active | {{ metrics?.inactive_customers ?? 0 }} Exp</p>
            </Link>

            <Link :href="route('admin.billing.payments')" class="rounded-2xl border border-brand-navy bg-[#091A2E]/80 p-3.5 sm:p-5 backdrop-blur-sm hover:border-emerald-500/50 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-slate-400">Today Collection</span>
                    <span class="rounded-lg bg-emerald-500/10 p-1.5 sm:p-2 text-emerald-400 border border-emerald-500/20">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2.5 sm:mt-4 flex items-baseline gap-1.5 sm:gap-2">
                    <span class="text-xl sm:text-3xl font-extrabold text-emerald-400 tracking-tight font-mono">{{ formatTaka(metrics?.today_collection) }}</span>
                    <span class="text-[10px] sm:text-xs font-medium text-emerald-400 font-mono">{{ metrics?.today_collection_count ?? 0 }} Txn</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5 sm:mt-1 truncate">Real-time collections</p>
            </Link>

            <Link :href="route('admin.reports.dues')" class="rounded-2xl border border-brand-navy bg-[#091A2E]/80 p-3.5 sm:p-5 backdrop-blur-sm hover:border-rose-500/50 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-slate-400">Total Due</span>
                    <span class="rounded-lg bg-rose-500/10 p-1.5 sm:p-2 text-rose-400 border border-rose-500/20">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2.5 sm:mt-4 flex items-baseline gap-1.5 sm:gap-2">
                    <span class="text-xl sm:text-3xl font-extrabold text-rose-400 tracking-tight font-mono">{{ formatTaka(metrics?.total_due) }}</span>
                    <span class="text-[10px] sm:text-xs font-medium text-rose-400 font-mono">{{ metrics?.due_customers_count ?? 0 }} Cust</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5 sm:mt-1 truncate">Pending recovery</p>
            </Link>

            <Link :href="route('admin.complaints.index')" class="rounded-2xl border border-brand-navy bg-[#091A2E]/80 p-3.5 sm:p-5 backdrop-blur-sm hover:border-brand-orange/50 transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-slate-400">Support Desk</span>
                    <span class="rounded-lg bg-brand-orange/10 p-1.5 sm:p-2 text-brand-orange border border-brand-orange/30">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2.5 sm:mt-4 flex items-baseline gap-1.5 sm:gap-2">
                    <span class="text-xl sm:text-3xl font-extrabold text-white tracking-tight">{{ metrics?.open_tickets ?? 0 }}</span>
                    <span class="text-[10px] sm:text-xs font-medium text-brand-orange font-mono">{{ metrics?.urgent_tickets ?? 0 }} Urgent</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5 sm:mt-1 truncate">Active tickets</p>
            </Link>
        </div>

        <!-- Recent Operations Grid: Collections & Complaints -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <!-- Recent Payments -->
            <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/80 p-4 sm:p-5 backdrop-blur-sm">
                <div class="flex items-center justify-between mb-3.5">
                    <h2 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        Recent Payments & Collections
                    </h2>
                    <Link :href="route('admin.billing.payments')" class="text-xs font-semibold text-brand-sky hover:underline">
                        View All →
                    </Link>
                </div>
                <div class="divide-y divide-brand-navy/60">
                    <div v-for="p in recentPayments" :key="p.id" class="py-2.5 sm:py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold text-white truncate">{{ p.customer?.name }}</div>
                            <div class="text-[10px] sm:text-[11px] text-slate-400 font-mono truncate">{{ p.payment_number }} • {{ p.payment_method }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-mono font-bold text-emerald-400">৳{{ p.amount }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ formatDateTime(p.paid_at) }}</div>
                        </div>
                    </div>
                    <div v-if="!recentPayments || recentPayments.length === 0" class="py-6 text-center text-xs text-slate-400">
                        No payments recorded yet.
                    </div>
                </div>
            </div>

            <!-- Recent Complaints -->
            <div class="rounded-2xl border border-brand-navy bg-[#091A2E]/80 p-4 sm:p-5 backdrop-blur-sm">
                <div class="flex items-center justify-between mb-3.5">
                    <h2 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-brand-orange"></span>
                        Active Support Tickets
                    </h2>
                    <Link :href="route('admin.complaints.index')" class="text-xs font-semibold text-brand-sky hover:underline">
                        View All →
                    </Link>
                </div>
                <div class="divide-y divide-brand-navy/60">
                    <div v-for="c in recentComplaints" :key="c.id" class="py-2.5 sm:py-3 flex items-center justify-between gap-2.5">
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold text-white truncate">{{ c.subject }}</div>
                            <div class="text-[10px] sm:text-[11px] text-slate-400 truncate">{{ c.customer?.name }} • Assigned: {{ c.assignee?.name || 'Unassigned' }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <span
                                class="inline-block px-2 py-0.5 rounded text-[9px] sm:text-[10px] font-bold uppercase font-mono"
                                :class="c.priority === 'urgent' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-brand-sky/10 text-brand-sky border border-brand-sky/20'"
                            >
                                {{ c.priority }}
                            </span>
                            <div class="text-[10px] text-slate-400 mt-0.5 capitalize font-mono">{{ c.status }}</div>
                        </div>
                    </div>
                    <div v-if="!recentComplaints || recentComplaints.length === 0" class="py-6 text-center text-xs text-slate-400">
                        No open complaints.
                    </div>
                </div>
            </div>
        </div>

        <!-- Professional Approve Payment Modal -->
        <ConfirmModal
            :show="showApproveConfirm"
            :title="'অনুমোদন নিশ্চিতকরণ (Approve Collection)'"
            :message="`গ্রাহক &quot;${paymentToApprove?.customer?.name}&quot;-এর ৳${paymentToApprove?.amount} টাকার পেমেন্টটি অনুমোদন করতে চান? অনুমোদন সম্পন্ন হলে স্বয়ংক্রিয়ভাবে ইনভয়েস ইস্যু এবং গ্রাহকের একাউন্টে ক্রেডিট যোগ হবে।`"
            confirm-text="কালেকশন অনুমোদন করুন"
            cancel-text="বাতিল"
            type="success"
            :processing="approveForm.processing"
            @confirm="confirmApprovePayment"
            @cancel="showApproveConfirm = false; paymentToApprove = null;"
        />
    </AdminLayout>
</template>
