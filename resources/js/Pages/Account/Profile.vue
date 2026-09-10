<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

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
                <Link
                    :href="route('profile.edit')"
                    class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-300 hover:text-white"
                >
                    Edit Credentials
                </Link>
            </div>

            <!-- Profile Summary Card -->
            <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Account Details</h2>
                <div class="grid grid-cols-2 gap-4 text-xs">
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
                        <span class="font-mono text-white">{{ customer.join_date }}</span>
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
        </div>
    </CustomerLayout>
</template>
