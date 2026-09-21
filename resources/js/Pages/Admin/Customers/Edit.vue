<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    customer: Object,
    areas: Array,
});

const primaryConnection = props.customer.connections?.[0] || null;

// Format YYYY-MM-DD for date input
const initialExpiry = primaryConnection?.expiry_date 
    ? (typeof primaryConnection.expiry_date === 'string' 
        ? primaryConnection.expiry_date.substring(0, 10) 
        : primaryConnection.expiry_date)
    : '';

const form = useForm({
    name: props.customer.name || '',
    phone: props.customer.primary_contact?.phone || props.customer.contacts?.[0]?.phone || '',
    area_id: props.customer.area_id || '',
    status: props.customer.status || 'active',
    billing_day: props.customer.billing_day || 1,
    expiry_date: initialExpiry,
    notes: props.customer.notes || '',
});

const submit = () => {
    form.put(route('admin.customers.update', props.customer.id));
};
</script>

<template>
    <Head :title="`Edit Customer ${customer.name} - Pirgacha Internet`" />

    <AdminLayout>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Edit Customer Profile</h1>
                <p class="text-sm text-slate-400 mt-1">Update customer personal details, status, and billing cycle day.</p>
            </div>
            <Link :href="route('admin.customers.show', customer.id)" class="text-sm text-slate-400 hover:text-white transition">
                ← Back to Profile
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Customer Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500"
                        />
                        <div v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Primary Phone *</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            required
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono"
                        />
                        <div v-if="form.errors.phone" class="text-xs text-rose-400 mt-1">{{ form.errors.phone }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Operational Area *</label>
                        <select
                            v-model="form.area_id"
                            required
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }} ({{ area.code }})
                            </option>
                        </select>
                        <div v-if="form.errors.area_id" class="text-xs text-rose-400 mt-1">{{ form.errors.area_id }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Status *</label>
                        <select
                            v-model="form.status"
                            required
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 uppercase font-bold"
                        >
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="suspended">Suspended</option>
                            <option value="disconnected">Disconnected</option>
                            <option value="pending">Pending</option>
                            <option value="archived">Archived</option>
                        </select>
                        <div v-if="form.errors.status" class="text-xs text-rose-400 mt-1">{{ form.errors.status }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Monthly Billing Cycle Day (১ - ৩১) *</label>
                        <input
                            v-model="form.billing_day"
                            type="number"
                            min="1"
                            max="31"
                            required
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono"
                        />
                        <div v-if="form.errors.billing_day" class="text-xs text-rose-400 mt-1">{{ form.errors.billing_day }}</div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-300 uppercase">Connection Expiry Date</label>
                            <span class="text-[10px] text-amber-400 font-semibold uppercase">Manual Override</span>
                        </div>
                        <input
                            v-model="form.expiry_date"
                            type="date"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">প্রয়োজনে ম্যানুয়ালি মেয়াদের তারিখ পরিবর্তন করতে পারেন।</p>
                        <div v-if="form.errors.expiry_date" class="text-xs text-rose-400 mt-1">{{ form.errors.expiry_date }}</div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Notes / Remarks</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="w-full rounded-xl border-slate-800 bg-slate-950/80 p-3 text-sm text-white focus:border-emerald-500 focus:ring-emerald-500"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Link
                    :href="route('admin.customers.show', customer.id)"
                    class="rounded-xl border border-slate-700 bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-6 py-2.5 text-sm font-bold text-white shadow-xl shadow-emerald-600/30 transition disabled:opacity-50"
                >
                    Save Changes
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
