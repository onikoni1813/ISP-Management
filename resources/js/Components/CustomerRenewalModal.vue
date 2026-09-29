<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    customer: {
        type: Object,
        default: null,
    },
    packages: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'success']);

const activeTab = ref('zero_charge'); // 'zero_charge' | 'paid'
const customDays = ref(3);
const presetDays = [1, 2, 3, 5, 7, 10];

const primaryConnection = computed(() => {
    return props.customer?.connections?.[0] || null;
});

const currentPackage = computed(() => {
    if (!props.customer) return null;
    return primaryConnection.value?.current_package || null;
});

const currentPrice = computed(() => {
    return currentPackage.value?.current_price?.price || 0;
});

const form = useForm({
    package_id: '',
    validity_days: 3,
    is_zero_charge: true,
    mode: 'standard',
    amount: 0,
    collect_payment: false,
    payment_method: 'cash',
    notes: '',
});

// Sync form with customer state on modal open
watch(() => props.isOpen, (open) => {
    if (open && props.customer) {
        activeTab.value = 'zero_charge';
        customDays.value = 3;
        form.reset();
        form.clearErrors();
        form.package_id = primaryConnection.value?.current_package_id || (props.packages?.[0]?.id || '');
        form.validity_days = 3;
        form.is_zero_charge = true;
        form.mode = 'standard';
        form.amount = 0;
        form.collect_payment = false;
        form.payment_method = 'cash';
        form.notes = 'জরুরি গ্রেস রিনিউ (টাকা ছাড়া)';
    }
});

// Watch tab change
watch(activeTab, (tab) => {
    form.clearErrors();
    if (tab === 'zero_charge') {
        form.is_zero_charge = true;
        form.mode = 'standard';
        form.validity_days = customDays.value || 3;
        form.amount = 0;
        form.collect_payment = false;
        form.notes = 'জরুরি গ্রেস রিনিউ (টাকা ছাড়া)';
    } else {
        form.is_zero_charge = false;
        form.mode = 'standard';
        form.validity_days = 30;
        const selectedPkg = props.packages.find(p => p.id === Number(form.package_id)) || currentPackage.value;
        form.amount = selectedPkg?.current_price?.price || currentPrice.value;
        form.collect_payment = true;
        form.notes = 'নিয়মিত প্যাকেজ রিনিউ ও বিল আদায়';
    }
});

const selectPresetDays = (days) => {
    customDays.value = days;
    form.validity_days = days;
};

const handlePackageChange = () => {
    const selectedPkg = props.packages.find(p => p.id === Number(form.package_id));
    if (selectedPkg) {
        form.validity_days = selectedPkg.current_price?.validity_days || 30;
        if (activeTab.value === 'paid') {
            form.amount = selectedPkg.current_price?.price || 0;
        }
    }
};

// Calculate preview of new expiry date
const previewNewExpiry = computed(() => {
    const days = Number(form.validity_days) || 0;
    if (days <= 0) return '—';

    const currentExpiryStr = primaryConnection.value?.expiry_date;
    const now = new Date();
    let baseDate = now;

    if (currentExpiryStr) {
        const curDate = new Date(currentExpiryStr);
        if (!isNaN(curDate.getTime()) && curDate >= now) {
            baseDate = curDate;
        }
    }

    const nextDate = new Date(baseDate);
    nextDate.setDate(nextDate.getDate() + days);
    return nextDate.toISOString().substring(0, 10);
});

const submitRenewal = () => {
    if (!props.customer || !primaryConnection.value) {
        alert('গ্রাহকের ইন্টারনেট সংযোগ পাওয়া যায়নি।');
        return;
    }

    const payload = {
        package_id: form.package_id || primaryConnection.value.current_package_id,
        validity_days: Number(form.validity_days),
        is_zero_charge: form.is_zero_charge,
        mode: 'standard',
        amount: form.is_zero_charge ? 0 : Number(form.amount),
        collect_payment: form.is_zero_charge ? false : Boolean(form.collect_payment),
        payment_method: form.is_zero_charge ? null : form.payment_method,
        notes: form.notes || (form.is_zero_charge ? 'টাকা ছাড়া গ্রেস রিনিউ' : 'পেইড রিনিউ'),
    };

    form.transform(() => payload).post(route('customers.renew', {
        customer: props.customer.id,
        connection: primaryConnection.value.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
        },
        onError: (err) => {
            const errList = Object.values(err).flat().join('\n');
            alert('রিনিউ করতে ত্রুটি:\n' + (errList || 'অনুগ্রহ করে পুনরায় চেষ্টা করুন'));
        }
    });
};
</script>

<template>
    <div v-if="isOpen && customer" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="emit('close')"></div>

        <!-- Modal Box -->
        <div class="relative w-full max-w-lg max-h-[92vh] overflow-y-auto rounded-3xl border border-brand-navy bg-[#091A2E] p-6 shadow-2xl space-y-5">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-brand-navy pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-500 text-white font-black text-xl shadow-lg shadow-purple-600/30">
                        ⚡
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-white">সংযোগ নবায়ন (Connection Renewal)</h2>
                        <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                            <span class="font-bold text-white">{{ customer.name }}</span>
                            <span>•</span>
                            <span class="font-mono text-brand-sky">{{ customer.customer_code }}</span>
                        </div>
                    </div>
                </div>
                <button
                    @click="emit('close')"
                    class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition"
                >
                    ✕
                </button>
            </div>

            <!-- Current Connection Status Mini-Card -->
            <div class="p-3.5 rounded-2xl bg-[#071322] border border-brand-navy flex items-center justify-between text-xs">
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">বর্তমান প্যাকেজ</span>
                    <span class="font-bold text-white">{{ currentPackage?.name || 'Standard Plan' }} ({{ currentPackage?.speed_mbps || 0 }}M)</span>
                </div>
                <div class="text-right">
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">বর্তমান মেয়াদ</span>
                    <span class="font-mono font-bold text-amber-400">
                        {{ primaryConnection?.expiry_date ? formatDate(primaryConnection.expiry_date) : 'মেয়াদ নেই' }}
                    </span>
                </div>
            </div>

            <!-- Renewal Mode Switcher Tabs -->
            <div class="grid grid-cols-2 gap-2 p-1.5 rounded-2xl bg-[#071322] border border-brand-navy">
                <button
                    type="button"
                    @click="activeTab = 'zero_charge'"
                    :class="[
                        'py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer',
                        activeTab === 'zero_charge'
                            ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md shadow-purple-600/25'
                            : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <span>🎁 টাকা ছাড়া রিনিউ (গ্রেস)</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'paid'"
                    :class="[
                        'py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer',
                        activeTab === 'paid'
                            ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-600/25'
                            : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <span>💳 নিয়মিত পেইড রিনিউ</span>
                </button>
            </div>

            <!-- Renewal Form -->
            <form @submit.prevent="submitRenewal" class="space-y-4">
                <!-- TAB 1: ZERO CHARGE RENEWAL (GRACE) -->
                <div v-if="activeTab === 'zero_charge'" class="space-y-4">
                    <div class="p-3.5 rounded-2xl bg-purple-500/10 border border-purple-500/25 space-y-1.5">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-purple-300">
                            <span>✨ টাকা ছাড়া জরুরি গ্রেস মেয়াদ (Zero-Charge)</span>
                        </div>
                        <p class="text-[11px] text-purple-200/80 leading-relaxed">
                            কোনো ফি ছাড়া সংযোগ সচল থাকবে (৳০)। পরবর্তীতে গ্রাহক যখন সম্পূর্ণ মাসের বিল পরিশোধ করবেন, তখন এই গ্রেস দিনগুলো বিল সাইকেল থেকে স্বয়ংক্রিয়ভাবে সমন্বয় হয়ে যাবে।
                        </p>
                    </div>

                    <!-- Preset Grace Days Buttons -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2">গ্রেস মেয়াদ নির্বাচন করুন:</label>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <button
                                v-for="d in presetDays"
                                :key="d"
                                type="button"
                                @click="selectPresetDays(d)"
                                :class="[
                                    'py-2 rounded-xl text-xs font-mono font-bold border transition cursor-pointer',
                                    form.validity_days === d
                                        ? 'bg-purple-600 border-purple-400 text-white shadow-md shadow-purple-600/30'
                                        : 'bg-[#071322] border-brand-navy text-slate-300 hover:border-purple-400/50'
                                ]"
                            >
                                +{{ d }} দিন
                            </button>
                        </div>
                    </div>

                    <!-- Custom Days Input -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">অথবা কাস্টম দিন লিখুন:</label>
                        <input
                            v-model.number="form.validity_days"
                            type="number"
                            min="1"
                            max="60"
                            class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white px-3.5 py-2 font-mono focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                        />
                    </div>
                </div>

                <!-- TAB 2: STANDARD PAID RENEWAL -->
                <div v-else class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">প্যাকেজ</label>
                        <select
                            v-model="form.package_id"
                            @change="handlePackageChange"
                            class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white px-3.5 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer"
                        >
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                {{ pkg.name }} ({{ pkg.speed_mbps }} Mbps) — ৳{{ pkg.current_price?.price || 0 }}/মাস
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">মেয়াদ (দিন)</label>
                            <input
                                v-model.number="form.validity_days"
                                type="number"
                                min="1"
                                max="365"
                                class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white px-3 py-2 font-mono"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">টাকার অঙ্ক (৳)</label>
                            <input
                                v-model.number="form.amount"
                                type="number"
                                min="0"
                                class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white px-3 py-2 font-mono font-bold"
                            />
                        </div>
                    </div>

                    <!-- Collect Payment Options -->
                    <div class="p-3.5 rounded-2xl bg-[#071322] border border-brand-navy space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.collect_payment"
                                class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-emerald-500 h-4 w-4"
                            />
                            <span class="text-xs font-bold text-white">বিল তাৎক্ষণিক আদায় করুন (Auto-Collect Payment)</span>
                        </label>

                        <div v-if="form.collect_payment" class="grid grid-cols-3 gap-2 pt-1">
                            <button
                                v-for="method in ['cash', 'bkash', 'nagad']"
                                :key="method"
                                type="button"
                                @click="form.payment_method = method"
                                :class="[
                                    'py-1.5 px-2 rounded-xl text-xs font-bold uppercase transition border text-center cursor-pointer',
                                    form.payment_method === method
                                        ? 'bg-emerald-600 border-emerald-400 text-white shadow'
                                        : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'
                                ]"
                            >
                                {{ method }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Real-time Expiry Preview Box -->
                <div class="p-4 rounded-2xl bg-gradient-to-br from-[#0B1E36] to-[#071322] border border-brand-navy flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">নবায়নের পর নতুন মেয়াদ:</span>
                        <div class="text-sm font-black font-mono mt-0.5" :class="activeTab === 'zero_charge' ? 'text-purple-400' : 'text-emerald-400'">
                            📅 {{ previewNewExpiry }} (+{{ form.validity_days }} দিন)
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block text-[11px]">নির্ধারিত চার্জ:</span>
                        <div class="text-sm font-black font-mono mt-0.5" :class="activeTab === 'zero_charge' ? 'text-purple-400' : 'text-emerald-400'">
                            {{ activeTab === 'zero_charge' ? '৳০ (বিনা মূল্যে)' : `৳${form.amount}` }}
                        </div>
                    </div>
                </div>

                <!-- Notes / Remarks -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">নোট / কারণ (ঐচ্ছিক):</label>
                    <input
                        v-model="form.notes"
                        type="text"
                        placeholder="যেমন: গ্রাহকের অনুরোধে সাময়িক গ্রেস প্রদান..."
                        class="w-full text-xs rounded-xl bg-[#071322] border-brand-navy text-white px-3.5 py-2 placeholder-slate-500"
                    />
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-navy">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-xl border border-brand-navy px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white transition"
                    >
                        বাতিল
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="[
                            'rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-lg transition disabled:opacity-50 flex items-center gap-2 cursor-pointer',
                            activeTab === 'zero_charge'
                                ? 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-purple-600/30'
                                : 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-emerald-600/30'
                        ]"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>
                            {{ form.processing ? 'প্রসেসিং হচ্ছে...' : (activeTab === 'zero_charge' ? 'টাকা ছাড়া গ্রেস রিনিউ করুন (৳০)' : 'বিল আদায় ও রিনিউ নিশ্চিত করুন') }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
