<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

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
                        <span>Joined: {{ customer.join_date }}</span>
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
                            <div class="text-sm font-bold text-amber-400 mt-1 font-mono">{{ primaryConnection?.expiry_date || 'N/A' }}</div>
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
            </div>

            <!-- Right Column: Personal Info & Address -->
            <div class="space-y-6">
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
