<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { formatDate } from '@/Utils/date';

defineProps({
    customer: Object,
    user: Object,
});
</script>

<template>
    <Head title="My Profile - Pirgacha Internet" />

    <CustomerLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-white">Subscriber Profile</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Contact details, billing info, and registered address</p>
                </div>
            </div>

            <!-- Profile Summary Card -->
            <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Account Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-500 block text-[11px]">Subscriber Code</span>
                        <span class="font-mono font-bold text-cyan-400 text-sm">{{ customer.customer_code }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Full Name</span>
                        <span class="font-bold text-white text-sm">{{ customer.name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Contact Mobile</span>
                        <span class="font-mono text-white">{{ customer.primary_contact?.phone || 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Email Address</span>
                        <span class="text-white">{{ user.email }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Area / Zone</span>
                        <span class="text-white">{{ customer.area?.name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Joined Since</span>
                        <span class="font-mono text-white">{{ formatDate(customer.join_date) }}</span>
                    </div>
                </div>
            </div>

            <!-- Installation Location -->
            <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-2 text-xs">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Installation Address</h2>
                <p class="text-slate-300 font-medium leading-relaxed">
                    {{ customer.installation_address?.full_address || 'Pirgacha, Rangpur' }}
                </p>
            </div>

            <!-- Quick Account Actions -->
            <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Account Actions</h2>
                <div class="flex flex-col gap-3">
                    <Link
                        :href="route('account.invoices')"
                        class="flex items-center justify-between p-4 rounded-2xl border border-slate-700 bg-[#0B1E36] hover:bg-[#0E2442] hover:border-slate-500 transition group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-brand-sky/10 flex items-center justify-center text-brand-sky text-lg">
                                🧾
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white group-hover:text-brand-sky transition">Billing Invoices</h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">View & print monthly broadband invoices</p>
                            </div>
                        </div>
                        <span class="text-slate-500 group-hover:text-brand-sky transition">→</span>
                    </Link>

                    <Link
                        :href="route('account.payments')"
                        class="flex items-center justify-between p-4 rounded-2xl border border-slate-700 bg-[#0B1E36] hover:bg-[#0E2442] hover:border-slate-500 transition group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 font-black">
                                ৳
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white group-hover:text-cyan-400 transition">Payment Receipts</h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">View your past receipts and bills</p>
                            </div>
                        </div>
                        <span class="text-slate-500 group-hover:text-cyan-400 transition">→</span>
                    </Link>

                    <Link
                        :href="route('account.complaints')"
                        class="flex items-center justify-between p-4 rounded-2xl border border-slate-700 bg-[#0B1E36] hover:bg-[#0E2442] hover:border-slate-500 transition group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-400 text-lg">
                                💬
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white group-hover:text-orange-400 transition">Support Tickets</h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">Report internet issues & get help</p>
                            </div>
                        </div>
                        <span class="text-slate-500 group-hover:text-orange-400 transition">→</span>
                    </Link>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
