<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CustomerRenewalModal from '@/Components/CustomerRenewalModal.vue';
import CustomerMoveModal from '@/Components/CustomerMoveModal.vue';
import { formatDate, formatDateTime } from '@/Utils/date';

const showRenewalModal = ref(false);
const showMoveModal = ref(false);

const props = defineProps({
    customer: Object,
    packages: Array,
    canViewPppoePassword: Boolean,
});

const primaryConnection = computed(() => props.customer.connections?.[0] || null);
const pppoe = computed(() => primaryConnection.value?.pppoe_credential || null);

// Reveal PPPoE Password Modal/State
const revealedPassword = ref(null);
const isRevealing = ref(false);

const revealPassword = async () => {
    if (!pppoe.value?.id) return;
    isRevealing.value = true;
    try {
        const res = await axios.post(route('admin.pppoe.reveal-password', pppoe.value.id));
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
    package_id: primaryConnection.value?.current_package_id || '',
});

const isChangingPackage = ref(false);

const submitPackageChange = () => {
    if (!primaryConnection.value) return;
    packageForm.post(route('admin.customers.change-package', {
        customer: props.customer.id,
        connection: primaryConnection.value.id,
    }), {
        onSuccess: () => {
            isChangingPackage.value = false;
        }
    });
};

// Direct Expiry Date Edit Form & Modal
const showExpiryModal = ref(false);

const formatForDateInput = (d) => {
    if (!d) return '';
    if (typeof d === 'string') return d.substring(0, 10);
    return '';
};

const expiryForm = useForm({
    name: props.customer.name,
    phone: props.customer.contacts?.[0]?.phone || '',
    area_id: props.customer.area_id,
    status: props.customer.status,
    billing_day: props.customer.billing_day,
    expiry_date: formatForDateInput(primaryConnection.value?.expiry_date),
    notes: props.customer.notes || '',
});

const openExpiryModal = () => {
    expiryForm.name = props.customer.name;
    expiryForm.phone = props.customer.contacts?.[0]?.phone || '';
    expiryForm.area_id = props.customer.area_id;
    expiryForm.status = props.customer.status;
    expiryForm.billing_day = props.customer.billing_day;
    expiryForm.expiry_date = formatForDateInput(primaryConnection.value?.expiry_date);
    expiryForm.notes = props.customer.notes || '';
    showExpiryModal.value = true;
};

const submitExpiryUpdate = () => {
    expiryForm.put(route('admin.customers.update', props.customer.id), {
        preserveScroll: true,
        onSuccess: () => {
            showExpiryModal.value = false;
            router.reload({ only: ['customer'] });
        },
        onError: (errors) => {
            const err = Object.values(errors).flat().join('\n');
            alert(err || 'Failed to update expiry date.');
        }
    });
};

// Collect Payment Form & Modal
const showPaymentModal = ref(false);
const defaultPrice = computed(() => primaryConnection.value?.current_package?.current_price?.price || 0);
const payForm = useForm({
    amount: 0,
    discount: 0,
    payment_method: 'cash',
    notes: 'Central office collection',
});

const openPaymentModal = () => {
    payForm.amount = defaultPrice.value;
    payForm.discount = 0;
    payForm.payment_method = 'cash';
    payForm.notes = 'Central office collection';
    showPaymentModal.value = true;
};

const handleDiscountChange = () => {
    const base = Number(defaultPrice.value) || 0;
    const disc = Number(payForm.discount) || 0;
    payForm.amount = Math.max(0, base - disc);
};

const submitPayment = () => {
    if (!payForm.amount || payForm.amount <= 0) {
        alert('সঠিক টাকার অঙ্ক লিখুন (Amount must be greater than 0)');
        return;
    }

    payForm.post(route('customers.pay', props.customer.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
        },
        onError: (errors) => {
            const errList = Object.values(errors).flat().join('\n');
            alert('বিল আদায়ে ত্রুটি:\n' + (errList || 'অনুগ্রহ করে পুনরায় চেষ্টা করুন'));
        }
    });
};

// Delete Customer Logic
const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const executeDelete = () => {
    deleteForm.delete(route('admin.customers.destroy', props.customer.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
        },
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
                    @click="showMoveModal = true"
                    class="rounded-xl border border-cyan-500/40 bg-cyan-500/15 hover:bg-cyan-500/25 px-3.5 py-2 text-xs font-bold text-cyan-300 hover:text-white transition cursor-pointer flex items-center gap-1.5 shadow-sm"
                    title="গ্রাহককে অন্য ক্যাটাগরি বা ফিল্টারে মুভ করুন"
                >
                    <span>🔀</span>
                    <span>মুভ / ফিল্টার পরিবর্তন</span>
                </button>
                <button
                    type="button"
                    @click="showRenewalModal = true"
                    class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-purple-600/25 transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>⚡</span>
                    <span>রিনিউ / গ্রেস দিন</span>
                </button>
                <button
                    type="button"
                    @click="openPaymentModal"
                    class="rounded-xl bg-emerald-600 hover:bg-emerald-500 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-emerald-600/25 transition cursor-pointer"
                >
                    Collect Payment
                </button>
                <button
                    type="button"
                    @click="isDeleteModalOpen = true"
                    class="rounded-xl border border-rose-500/30 bg-rose-500/10 hover:bg-rose-500/20 px-3 py-2 text-xs font-semibold text-rose-400 hover:text-rose-300 transition cursor-pointer flex items-center gap-1.5"
                    title="গ্রাহক মুছে ফেলুন"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete
                </button>
            </div>
        </div>

        <!-- Active Zero-Charge Grace Notice Banner -->
        <div v-if="customer.has_active_zero_charge" class="mb-5 p-4 rounded-2xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-between gap-4 backdrop-blur-sm shadow-lg shadow-purple-900/20">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-purple-600/20 border border-purple-400/30 flex items-center justify-center text-purple-300 text-lg">
                    🎁
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-purple-300 flex items-center gap-1.5">
                        <span>টাকা ছাড়া গ্রেস রিনিউ সচল রয়েছে (Zero-Charge Grace Active)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-purple-500/20 border border-purple-500/40 text-purple-200 font-mono">
                            +{{ customer.zero_charge_days || 'গ্রেস' }} দিন
                        </span>
                    </h3>
                    <p class="text-xs text-slate-300 mt-0.5">
                        এই গ্রাহককে টাকা ছাড়া সাময়িক মেয়াদ বাড়ানো হয়েছিল। পরবর্তীতে পুরো মাসের বিল কালেকশন করার সময় এই দিনগুলো বিল সাইকেল থেকে স্বয়ংক্রিয়ভাবে সমন্বয় হয়ে যাবে।
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="openPaymentModal"
                    class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 px-3.5 py-2 text-xs font-bold text-white shadow-md transition cursor-pointer whitespace-nowrap"
                >
                    বিল আদায় করুন
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
                            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Expiry Date</span>
                            <div class="text-sm font-black text-amber-400 mt-1 font-mono flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-amber-400/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ formatDate(primaryConnection?.expiry_date) }}</span>
                            </div>
                            <!-- Modern Pill Button Group for Renew & Edit -->
                            <div class="flex items-center gap-1.5 mt-2">
                                <button
                                    type="button"
                                    @click="showRenewalModal = true"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-700 hover:from-purple-500 hover:to-indigo-500 text-white shadow-md shadow-purple-600/30 border border-purple-400/30 transition-all hover:scale-105 active:scale-95 cursor-pointer"
                                    title="প্যাকেজ রিনিউ বা টাকা ছাড়া গ্রেস প্রদান"
                                >
                                    <svg class="w-3 h-3 text-purple-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span>রিনিউ</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openExpiryModal"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-800/90 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 hover:border-slate-600 transition-all hover:scale-105 active:scale-95 cursor-pointer"
                                    title="মেয়াদ সরাসরি পরিবর্তন করুন"
                                >
                                    <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <span>Edit</span>
                                </button>
                            </div>
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

        <!-- Collect Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="w-full max-w-sm rounded-3xl border border-slate-700 bg-[#071527] p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-base font-bold text-white">বিল আদায় (Collect Payment)</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ customer.name }} ({{ customer.customer_code }})</p>
                    </div>
                    <button @click="showPaymentModal = false" class="text-slate-400 hover:text-white text-base">✕</button>
                </div>

                <form @submit.prevent="submitPayment" class="space-y-3.5">
                    <!-- Package Price Reference -->
                    <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 text-xs">
                        <div class="flex justify-between text-slate-400">
                            <span>প্যাকেজ রেট:</span>
                            <span class="font-bold text-white font-mono">৳{{ defaultPrice }}</span>
                        </div>
                        <div class="flex justify-between text-slate-400 mt-1">
                            <span>বিলিং ডে:</span>
                            <span class="font-bold text-emerald-400 font-mono">{{ customer.billing_day }} তারিখ</span>
                        </div>
                    </div>

                    <!-- Discount -->
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">ডিস্কাউন্ট বা ছাড় (৳)</label>
                        <input
                            v-model.number="payForm.discount"
                            type="number"
                            min="0"
                            :max="defaultPrice"
                            @input="handleDiscountChange"
                            placeholder="0"
                            class="w-full rounded-xl bg-slate-900 border border-slate-700 p-2.5 text-xs text-white font-mono focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none"
                        />
                    </div>

                    <!-- Net Payable Amount -->
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">মোট আদায়যোগ্য পরিমাণ (৳) *</label>
                        <input
                            v-model.number="payForm.amount"
                            type="number"
                            min="1"
                            required
                            class="w-full rounded-xl bg-slate-900 border border-slate-700 p-2.5 text-xs font-bold text-emerald-400 font-mono focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none"
                        />
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">পেমেন্ট মেথড *</label>
                        <select
                            v-model="payForm.payment_method"
                            class="w-full rounded-xl bg-slate-900 border border-slate-700 p-2.5 text-xs text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none"
                        >
                            <option value="cash">নগদ ক্যাশ (Cash)</option>
                            <option value="bkash">বিকাশ (bKash)</option>
                            <option value="nagad">নগদ (Nagad)</option>
                            <option value="bank">ব্যাংক (Bank)</option>
                            <option value="other">অন্যান্য (Other)</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">মন্তব্য (নোট)</label>
                        <input
                            v-model="payForm.notes"
                            type="text"
                            placeholder="নোট বা রেফারেন্স..."
                            class="w-full rounded-xl bg-slate-900 border border-slate-700 p-2.5 text-xs text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none"
                        />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button
                            type="button"
                            @click="showPaymentModal = false"
                            class="flex-1 rounded-xl bg-slate-800 border border-slate-700 py-2.5 text-xs font-semibold text-slate-300 hover:text-white transition"
                        >
                            বাতিল
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="flex-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 py-2.5 text-xs font-bold text-white shadow-lg shadow-emerald-600/30 transition disabled:opacity-50 flex items-center justify-center gap-1.5"
                        >
                            <span v-if="payForm.processing">সংরক্ষণ হচ্ছে...</span>
                            <span v-else>আদায় কনফার্ম করুন</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit Expiry Date Modal -->
        <div v-if="showExpiryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="w-full max-w-sm rounded-3xl border border-slate-700 bg-[#071527] p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-base font-bold text-white">মেয়াদ পরিবর্তন (Edit Expiry Date)</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ customer.name }} ({{ customer.customer_code }})</p>
                    </div>
                    <button @click="showExpiryModal = false" class="text-slate-400 hover:text-white text-base">✕</button>
                </div>

                <form @submit.prevent="submitExpiryUpdate" class="space-y-4">
                    <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 text-xs">
                        <div class="flex justify-between text-slate-400">
                            <span>প্যাকেজ:</span>
                            <span class="font-bold text-white">{{ primaryConnection?.current_package?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between text-slate-400 mt-1">
                            <span>বর্তমান মেয়াদ:</span>
                            <span class="font-bold text-amber-400 font-mono">{{ formatDate(primaryConnection?.expiry_date) }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">নতুন মেয়াদের তারিখ (New Expiry Date) *</label>
                        <input
                            v-model="expiryForm.expiry_date"
                            type="date"
                            required
                            class="w-full rounded-xl border border-slate-700 bg-slate-900 p-2.5 text-sm text-white font-mono focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                        />
                        <div v-if="expiryForm.errors.expiry_date" class="text-xs text-rose-400 mt-1">{{ expiryForm.errors.expiry_date }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Billing Cycle Day (১-৩১)</label>
                        <input
                            v-model.number="expiryForm.billing_day"
                            type="number"
                            min="1"
                            max="31"
                            required
                            class="w-full rounded-xl border border-slate-700 bg-slate-900 p-2.5 text-sm text-white font-mono focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                        />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button
                            type="button"
                            @click="showExpiryModal = false"
                            class="flex-1 rounded-xl bg-slate-800 border border-slate-700 py-2.5 text-xs font-semibold text-slate-300 hover:text-white transition"
                        >
                            বাতিল
                        </button>
                        <button
                            type="submit"
                            :disabled="expiryForm.processing"
                            class="flex-1 rounded-xl bg-amber-600 hover:bg-amber-500 py-2.5 text-xs font-bold text-white shadow-lg shadow-amber-600/30 transition disabled:opacity-50 flex items-center justify-center gap-1.5"
                        >
                            <span v-if="expiryForm.processing">আপডেট হচ্ছে...</span>
                            <span v-else>মেয়াদ সংরক্ষণ করুন</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CUSTOMER DELETE CONFIRMATION MODAL -->
        <div v-if="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="isDeleteModalOpen = false" class="fixed inset-0 bg-black/80 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-md rounded-3xl border border-rose-500/30 bg-[#091A2E] p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-400 border border-rose-500/30">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">গ্রাহক মুছে ফেলার নিশ্চিতকরণ</h3>
                        <p class="text-xs text-slate-400">এই কাজটি অপরিবর্তনীয় (Irreversible action)</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4 text-xs text-slate-300 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-400">নাম:</span>
                        <span class="font-bold text-white">{{ customer.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">কাস্টমার আইডি:</span>
                        <span class="font-mono text-brand-sky">{{ customer.customer_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">ব্যালেন্স / বকেয়া:</span>
                        <span class="font-mono text-amber-400">৳{{ customer.balance }}</span>
                    </div>
                </div>

                <p class="text-xs text-rose-300/90 leading-relaxed bg-rose-500/10 p-3 rounded-xl border border-rose-500/20">
                    ⚠️ সতর্কবার্তা: গ্রাহক ডিলিট করলে এর সাথে যুক্ত সংযোগ (Connection), PPPoE ক্রেডেনশিয়াল এবং সংশ্লিষ্ট তথ্য ডাটাবেজ থেকে স্থায়ীভাবে মুছে যাবে।
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="isDeleteModalOpen = false"
                        :disabled="deleteForm.processing"
                        class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white transition cursor-pointer"
                    >
                        বাতিল করুন
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        :disabled="deleteForm.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 px-5 py-2 text-xs font-bold text-white shadow-lg shadow-rose-600/30 transition disabled:opacity-50 cursor-pointer"
                    >
                        <svg v-if="deleteForm.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ deleteForm.processing ? 'ডিলিট হচ্ছে...' : 'হ্যাঁ, ডিলিট করুন' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Reusable Connection Renewal Modal -->
        <CustomerRenewalModal
            :is-open="showRenewalModal"
            :customer="customer"
            :packages="packages"
            @close="showRenewalModal = false"
            @success="router.reload({ only: ['customer'] })"
        />

        <!-- Reusable Customer Move Modal -->
        <CustomerMoveModal
            :is-open="showMoveModal"
            :customer="customer"
            :is-bulk="false"
            :packages="packages"
            @close="showMoveModal = false"
            @success="router.reload()"
        />
    </AdminLayout>
</template>

