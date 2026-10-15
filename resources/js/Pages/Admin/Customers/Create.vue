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
    send_welcome_sms: true,
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
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-300 uppercase">Installation Address</label>
                            <span class="text-[10px] text-slate-500 font-semibold uppercase">Optional</span>
                        </div>
                        <textarea
                            v-model="form.address"
                            rows="2"
                            placeholder="House / Holding, Village / Road, Pirgacha..."
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <div v-if="form.errors.address" class="text-xs text-rose-400 mt-1">{{ form.errors.address }}</div>
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
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">PPPoE Username *</label>
                        <input
                            v-model="form.pppoe_username"
                            type="text"
                            required
                            placeholder="username_net"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono"
                        />
                        <div v-if="form.errors.pppoe_username" class="text-xs text-rose-400 mt-1">{{ form.errors.pppoe_username }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">PPPoE Password (Encrypted) *</label>
                        <input
                            v-model="form.pppoe_password"
                            type="password"
                            required
                            placeholder="••••••••"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono"
                        />
                        <div v-if="form.errors.pppoe_password" class="text-xs text-rose-400 mt-1">{{ form.errors.pppoe_password }}</div>
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

            <!-- Automated Welcome SMS Notification -->
            <div class="rounded-2xl border border-brand-sky/30 bg-gradient-to-r from-brand-sky/15 via-[#0A1F36]/90 to-[#071322] p-5 backdrop-blur-sm shadow-xl shadow-black/25 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-sky/20 text-brand-sky border border-brand-sky/30 text-xl shadow-inner">
                        ✉️
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-bold text-white tracking-wide">স্বয়ংক্রিয় ওয়েলকাম SMS নোটিফিকেশন</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                অটোমেটিক চালু
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                            নতুন কাস্টমার সেভ করার সাথে সাথে তার মোবাইলে <span class="text-brand-sky font-semibold">PPPoE ইউজারনেম</span>, <span class="text-brand-sky font-semibold">পাসওয়ার্ড</span> এবং <span class="text-emerald-400 font-semibold">লগইন পোর্টাল লিংক</span> সহ ওয়েলকাম SMS স্বয়ংক্রিয়ভাবে পাঠানো হবে।
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0 self-end sm:self-center">
                    <label class="relative inline-flex items-center cursor-pointer select-none">
                        <input
                            v-model="form.send_welcome_sms"
                            type="checkbox"
                            class="sr-only peer"
                        />
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-brand-sky peer-checked:to-brand-blue border border-brand-navy"></div>
                        <span class="ml-2.5 text-xs font-bold" :class="form.send_welcome_sms ? 'text-brand-sky' : 'text-slate-400'">
                            {{ form.send_welcome_sms ? 'SMS চালু' : 'SMS বন্ধ' }}
                        </span>
                    </label>
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
