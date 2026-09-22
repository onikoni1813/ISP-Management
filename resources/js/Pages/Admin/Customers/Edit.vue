<script setup>
import { ref } from 'vue';
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
            <div class="flex items-center justify-between pt-2">
                <button
                    type="button"
                    @click="isDeleteModalOpen = true"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-500/30 bg-rose-500/10 hover:bg-rose-500/20 px-4 py-2.5 text-sm font-semibold text-rose-400 hover:text-rose-300 transition cursor-pointer"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Customer
                </button>

                <div class="flex items-center gap-3">
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
            </div>
        </form>

        <!-- DELETE CONFIRMATION MODAL -->
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
    </AdminLayout>
</template>

