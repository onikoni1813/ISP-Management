<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDate, formatDateTime } from '@/Utils/date';

const props = defineProps({
    customer: Object,
    packages: Array,
    canViewPppoePassword: Boolean,
});

const primaryConnection = props.customer.connections?.[0] || null;
const pppoe = primaryConnection?.pppoe_credential || null;

// Reveal PPPoE Password Modal/State
const revealedPassword = ref(null);
const isRevealing = ref(false);

const revealPassword = async () => {
    if (!pppoe?.id) return;
    isRevealing.value = true;
    try {
        const res = await axios.post(route('admin.pppoe.reveal-password', pppoe.id));
        revealedPassword.value = res.data.password;
    } catch (err) {
        alert('Unauthorized or error revealing password.');
    } finally {
        isRevealing.value = false;
    }
};

// Send PPPoE Credentials via SMS
const isSendingSms = ref(false);
const sendCredentialsSms = () => {
    const phone = props.customer.contacts?.[0]?.phone || props.customer.contacts?.[0]?.phone_number;
    if (!confirm(`Are you sure you want to send PPPoE credentials and login link via SMS to ${props.customer.name} (${phone || 'Primary Contact'})?`)) {
        return;
    }
    isSendingSms.value = true;
    useForm({}).post(route('admin.customers.send-credentials-sms', props.customer.id), {
        preserveScroll: true,
        onFinish: () => {
            isSendingSms.value = false;
        },
    });
};

// Package Change Form
const packageForm = useForm({
    package_id: primaryConnection?.current_package_id || '',
});

const isChangingPackage = ref(false);

const submitPackageChange = () => {
    if (!primaryConnection) return;
    packageForm.post(route('admin.customers.change-package', {
        customer: props.customer.id,
        connection: primaryConnection.id,
    }), {
        onSuccess: () => {
            isChangingPackage.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`${customer.name} (${customer.customer_code}) - Central Profile`" />

    <AdminLayout>
        <!-- Top Profile Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-2xl font-black text-white shadow-xl shadow-indigo-600/30">
                    {{ customer.name.charAt(0) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-black text-white tracking-tight">{{ customer.name }}</h1>
                        <span 
                            :class="[
                                customer.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                'inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-bold uppercase'
                            ]"
                        >
                            {{ customer.status }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-400 mt-1">
                        <span class="font-mono text-indigo-400">{{ customer.customer_code }}</span>
                        <span>•</span>
                        <span>Joined: {{ formatDate(customer.join_date) }}</span>
                        <span>•</span>
                        <span>Billing Day: {{ customer.billing_day }}th</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.customers.edit', customer.id)"
                    class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-700 transition"
                >
                    Edit Profile
                </Link>
                <button
                    type="button"
                    class="rounded-xl bg-emerald-600 hover:bg-emerald-500 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-emerald-600/25 transition"
                >
                    Collect Payment
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Details & Connection -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Current Connection & Package Card -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Active Connection & Internet Package
                        </h2>
                        <button
                            v-if="!isChangingPackage"
                            @click="isChangingPackage = true"
                            class="text-xs font-semibold text-indigo-400 hover:text-indigo-300"
                        >
                            Change Package
                        </button>
                    </div>

                    <!-- Change Package Drawer -->
                    <div v-if="isChangingPackage" class="my-4 p-4 rounded-xl bg-slate-950/80 border border-indigo-500/30">
                        <div class="text-xs font-bold text-indigo-300 mb-2 uppercase">Select New Package</div>
                        <form @submit.prevent="submitPackageChange" class="flex items-center gap-3">
                            <select
                                v-model="packageForm.package_id"
                                class="rounded-xl border-slate-800 bg-slate-900 text-sm text-white flex-1 p-2.5"
                            >
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }} ({{ pkg.speed_mbps }} Mbps) - ৳{{ pkg.current_price?.price || 0 }}/mo
                                </option>
                            </select>
                            <button
                                type="submit"
                                :disabled="packageForm.processing"
                                class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-500 transition"
                            >
                                Apply
                            </button>
                            <button
                                type="button"
                                @click="isChangingPackage = false"
                                class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-2.5 text-xs font-semibold text-slate-300"
                            >
                                Cancel
                            </button>
                        </form>
                    </div>

                    <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <div class="text-[11px] font-semibold text-slate-400 uppercase">Package</div>
                            <div class="text-sm font-bold text-white mt-1">{{ primaryConnection?.current_package?.name || 'None' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold text-slate-400 uppercase">Speed</div>
                            <div class="text-sm font-bold text-emerald-400 mt-1">{{ primaryConnection?.current_package?.speed_mbps || 0 }} Mbps</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold text-slate-400 uppercase">Monthly Price</div>
                            <div class="text-sm font-bold text-white mt-1">৳{{ primaryConnection?.current_package?.current_price?.price || 0 }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold text-slate-400 uppercase">Expiry Date</div>
                            <div class="text-sm font-bold text-amber-400 mt-1 font-mono">{{ formatDate(primaryConnection?.expiry_date) }}</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-800/80 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-slate-500">Connection Code:</span>
                            <span class="font-mono text-slate-300 ml-1">{{ primaryConnection?.connection_code }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">IP Address:</span>
                            <span class="font-mono text-slate-300 ml-1">{{ primaryConnection?.ip_address || 'Dynamic' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">MAC:</span>
                            <span class="font-mono text-slate-300 ml-1">{{ primaryConnection?.mac_address || 'Unset' }}</span>
                        </div>
                    </div>
                </div>

                <!-- PPPoE Security Card -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                            PPPoE Security Credentials
                        </h2>
                        <span class="text-[10px] uppercase font-bold text-violet-400 bg-violet-500/10 border border-violet-500/20 px-2 py-0.5 rounded">
                            AES-256 Encrypted
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80">
                            <div class="text-xs text-slate-400">PPPoE Username</div>
                            <div class="text-sm font-mono font-bold text-white mt-1">
                                {{ pppoe?.username || 'No PPPoE Assigned' }}
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-400">Encrypted Password</span>
                                <button
                                    v-if="canViewPppoePassword && !revealedPassword"
                                    @click="revealPassword"
                                    :disabled="isRevealing"
                                    class="text-[11px] font-bold text-indigo-400 hover:text-indigo-300 underline"
                                >
                                    {{ isRevealing ? 'Decrypting...' : 'Reveal (Audited)' }}
                                </button>
                            </div>
                            <div class="text-sm font-mono font-bold text-emerald-400 mt-1">
                                {{ revealedPassword || '••••••••••••' }}
                            </div>
                        </div>
                    </div>

                    <div v-if="pppoe" class="mt-4 pt-3.5 border-t border-slate-800/80 flex items-center justify-between flex-wrap gap-2">
                        <span class="text-xs text-slate-400 flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-brand-sky" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            গ্রাহককে PPPoE ও লগইন লিংক SMS করুন
                        </span>
                        <button
                            type="button"
                            @click="sendCredentialsSms"
                            :disabled="isSendingSms"
                            class="inline-flex items-center gap-2 rounded-xl bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white font-bold text-xs px-3.5 py-2 shadow-lg shadow-violet-600/20 transition cursor-pointer"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            {{ isSendingSms ? 'পাঠানো হচ্ছে...' : 'Send Credentials SMS' }}
                        </button>
                    </div>
                </div>

                <!-- Package Assignment History Card -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm">
                    <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                        Package History (Historical Pricing Preserved)
                    </h2>
                    <div class="divide-y divide-slate-800">
                        <div v-for="item in customer.package_histories" :key="item.id" class="py-3 flex items-center justify-between text-xs">
                            <div>
                                <div class="font-bold text-white">{{ item.package?.name }}</div>
                                <div class="text-slate-500 mt-0.5">{{ item.start_date }} to {{ item.end_date || 'Present' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-mono font-bold text-emerald-400">৳{{ item.actual_price }}</div>
                                <div class="text-[10px] text-slate-500 uppercase">{{ item.status }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoices & Generated Bills Card (PPPoE Linked) -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            ইনভয়েস ও পরিশোধিত বিল (PPPoE Profile Invoices)
                        </h2>
                        <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full">
                            {{ customer.invoices?.length || 0 }} Invoices
                        </span>
                    </div>

                    <div v-if="customer.invoices?.length > 0" class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300">
                            <thead class="border-b border-slate-800 bg-slate-950/40 text-[10px] uppercase font-bold text-slate-400">
                                <tr>
                                    <th class="px-3 py-2.5">Invoice #</th>
                                    <th class="px-3 py-2.5">Period</th>
                                    <th class="px-3 py-2.5">Total & Discount</th>
                                    <th class="px-3 py-2.5">Status</th>
                                    <th class="px-3 py-2.5">PPPoE / Service</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="inv in customer.invoices" :key="inv.id" class="hover:bg-slate-800/30 transition">
                                    <td class="px-3 py-3 font-mono font-bold text-indigo-400">
                                        {{ inv.invoice_number }}
                                    </td>
                                    <td class="px-3 py-3 text-slate-400">
                                        {{ formatDate(inv.period_start) }} - {{ formatDate(inv.period_end) }}
                                    </td>
                                    <td class="px-3 py-3 font-mono font-bold text-white">
                                        ৳{{ inv.total }}
                                        <span v-if="inv.discount > 0" class="text-[10px] text-brand-amber font-normal block">
                                            (ছাড়: ৳{{ inv.discount }})
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span :class="[
                                            inv.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                            'inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase'
                                        ]">
                                            {{ inv.status === 'paid' ? 'পরিশোধিত (Paid)' : inv.status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-slate-400 text-[11px]">
                                        <div class="font-mono text-brand-sky font-semibold">{{ pppoe?.username || 'PPPoE' }}</div>
                                        <div class="text-[10px] text-slate-500 truncate max-w-[180px]">{{ inv.notes || 'Package billing' }}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-xs text-slate-500 text-center py-6 bg-slate-950/40 rounded-xl mt-4">
                        কোনো ইনভয়েস রেকর্ড তৈরি হয়নি।
                    </div>
                </div>
            </div>

            <!-- Right Column: Personal Info & Address & Staff Notes -->
            <div class="space-y-6">
                <!-- Staff Field Notes & Promise to Pay Card -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                            Staff Notes & Promise To Pay
                        </h2>
                        <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">
                            {{ customer.customer_notes?.length || 0 }} Notes
                        </span>
                    </div>

                    <div v-if="customer.customer_notes?.length > 0" class="divide-y divide-slate-800/80">
                        <div v-for="nt in customer.customer_notes" :key="nt.id" class="py-3 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                                        nt.note_type === 'promise_to_pay' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30'
                                    ]">
                                        {{ nt.note_type === 'promise_to_pay' ? 'Promise to Pay' : 'Note' }}
                                    </span>
                                    <span class="font-bold text-white">{{ nt.author?.name }}</span>
                                </div>
                                <span class="text-[10px] font-mono text-slate-500">{{ formatDateTime(nt.created_at) }}</span>
                            </div>

                            <p class="text-slate-300 leading-relaxed">{{ nt.note }}</p>

                            <div v-if="nt.promise_date" class="flex items-center justify-between pt-1 text-[11px]">
                                <span class="text-amber-400 font-medium">
                                    📅 Promised: <strong class="font-mono">{{ formatDate(nt.promise_date) }}</strong>
                                    <span v-if="nt.promise_amount" class="ml-1 font-mono text-white">(৳{{ nt.promise_amount }})</span>
                                </span>
                                <span :class="[
                                    'text-[10px] font-bold uppercase px-1.5 py-0.5 rounded',
                                    nt.status === 'resolved' ? 'text-emerald-400 bg-emerald-500/10' : 'text-amber-400 bg-amber-500/10'
                                ]">
                                    {{ nt.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-slate-500 text-center py-4 bg-slate-950/40 rounded-xl">
                        No field notes recorded yet.
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-cyan-500"></span>
                        Contact & Location
                    </h2>

                    <div>
                        <div class="text-xs text-slate-500 uppercase">Primary Phone</div>
                        <div class="text-sm font-bold font-mono text-white mt-0.5">
                            {{ customer.contacts?.[0]?.phone || 'None' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500 uppercase">Email Address</div>
                        <div class="text-sm text-slate-300 mt-0.5">
                            {{ customer.contacts?.[0]?.email || 'None' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500 uppercase">Operational Area</div>
                        <div class="text-sm font-semibold text-indigo-400 mt-0.5">
                            {{ customer.area?.name }} ({{ customer.area?.code }})
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500 uppercase">Installation Address</div>
                        <div class="text-sm text-slate-300 mt-0.5">
                            {{ customer.addresses?.[0]?.full_address || 'None' }}
                        </div>
                    </div>

                    <div v-if="customer.notes" class="pt-3 border-t border-slate-800">
                        <div class="text-xs text-slate-500 uppercase">Internal Notes</div>
                        <div class="text-xs text-slate-400 mt-1 italic">{{ customer.notes }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
