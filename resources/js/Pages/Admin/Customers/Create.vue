<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    areas: Array,
    packages: Array,
});

const form = useForm({
    name: '',
    phone: '',
    email: '',
    area_id: props.areas[0]?.id || '',
    address: '',
    package_id: props.packages[0]?.id || '',
    pppoe_username: '',
    pppoe_password: '',
    ip_address: '',
    mac_address: '',
    router_model: '',
    billing_day: 1,
    notes: '',
});

const submit = () => {
    form.post(route('admin.customers.store'));
};
</script>

<template>
    <Head title="Add New Customer - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Register New Customer</h1>
                <p class="text-sm text-slate-400 mt-1">Setup customer profile, connection, and PPPoE access.</p>
            </div>
            <Link :href="route('admin.customers.index')" class="text-sm text-slate-400 hover:text-white transition">
                ← Back to List
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6 max-w-4xl">
            <!-- Basic Details -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                    Personal & Contact Details
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Full Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g. Md. Rahim Uddin"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <div v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Primary Mobile Number *</label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            required
                            placeholder="017XXXXXXXX"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                        />
                        <div v-if="form.errors.phone" class="text-xs text-rose-400 mt-1">{{ form.errors.phone }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Email Address</label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="optional@domain.com"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Area / Zone *</label>
                        <select
                            v-model="form.area_id"
                            required
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }} ({{ area.code }})
                            </option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Installation Address *</label>
                        <textarea
                            v-model="form.address"
                            required
                            rows="2"
                            placeholder="House / Holding, Village / Road, Pirgacha..."
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Connection & Package Setup -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Internet Package & PPPoE Configuration
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Select Internet Package</label>
                        <select
                            v-model="form.package_id"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                {{ pkg.name }} ({{ pkg.speed_mbps }} Mbps) - ৳{{ pkg.current_price?.price || 0 }}/mo
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Monthly Billing Cycle Day</label>
                        <input
                            v-model="form.billing_day"
                            type="number"
                            min="1"
                            max="31"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">PPPoE Username</label>
                        <input
                            v-model="form.pppoe_username"
                            type="text"
                            placeholder="username_net"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">PPPoE Password (Encrypted)</label>
                        <input
                            v-model="form.pppoe_password"
                            type="password"
                            placeholder="••••••••"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Router Model</label>
                        <input
                            v-model="form.router_model"
                            type="text"
                            placeholder="TP-Link / Mi 4C"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">MAC Address</label>
                        <input
                            v-model="form.mac_address"
                            type="text"
                            placeholder="AA:BB:CC:DD:EE:FF"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono"
                        />
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4">
                <Link
                    :href="route('admin.customers.index')"
                    class="rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 text-sm font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-6 py-3 text-sm font-bold text-white shadow-xl shadow-indigo-600/30 transition disabled:opacity-50"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                    </svg>
                    Save & Create Customer
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
