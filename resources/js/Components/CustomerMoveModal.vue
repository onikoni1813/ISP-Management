<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    // Single customer mode
    customer: {
        type: Object,
        default: null,
    },
    // Bulk mode props
    isBulk: {
        type: Boolean,
        default: false,
    },
    customerIds: {
        type: Array,
        default: () => [],
    },
    targetAllFiltered: {
        type: Boolean,
        default: false,
    },
    totalFilteredCount: {
        type: Number,
        default: 0,
    },
    filterParams: {
        type: Object,
        default: () => ({}),
    },
    packages: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'success']);

const targetCategory = ref('zero_charge_renewed');
const presetDays = [1, 2, 3, 5, 7, 10];
const selectedDays = ref(3);

const primaryConnection = computed(() => {
    return props.customer?.connections?.[0] || null;
});

const currentPackage = computed(() => {
    return primaryConnection.value?.current_package || null;
});

const defaultAmount = computed(() => {
    return currentPackage.value?.current_price?.price || 500;
});

const form = useForm({
    target_category: 'zero_charge_renewed',
    validity_days: 3,
    reverse_accidental_payment: true,
    void_renewal_invoice: true,
    amount: 500,
    payment_method: 'cash',
    notes: '',
    // Bulk fields
    customer_ids: [],
    target_all_filtered: false,
    search: '',
    status: '',
    area_id: '',
    advanced_filter: '',
});

watch(() => props.isOpen, (open) => {
    if (open) {
        targetCategory.value = 'zero_charge_renewed';
        selectedDays.value = 3;
        form.reset();
        form.clearErrors();

        form.target_category = 'zero_charge_renewed';
        form.validity_days = 3;
        form.reverse_accidental_payment = true;
        form.void_renewal_invoice = true;
        form.amount = defaultAmount.value;
        form.payment_method = 'cash';
        form.notes = props.isBulk 
            ? 'বাল্ক ক্যাটাগরি মুভ ও ফিল্টার সমন্বয়'
            : (props.customer ? `গ্রাহক #${props.customer.customer_code} ক্যাটাগরি ও ফিল্টার সংশোধন` : '');

        if (props.isBulk) {
            form.customer_ids = props.targetAllFiltered ? [] : props.customerIds;
            form.target_all_filtered = props.targetAllFiltered;
            form.search = props.filterParams.search || '';
            form.status = props.filterParams.status || '';
            form.area_id = props.filterParams.area_id || '';
            form.advanced_filter = props.filterParams.advanced_filter || '';
        }
    }
});

watch(targetCategory, (val) => {
    form.target_category = val;
    form.clearErrors();
    if (val === 'zero_charge_renewed') {
        form.validity_days = selectedDays.value || 3;
        form.reverse_accidental_payment = true;
        form.void_renewal_invoice = true;
    } else if (val === 'expiring_3d') {
        form.validity_days = 2;
    } else if (val === 'paid_this_month') {
        form.amount = defaultAmount.value;
    }
});

const selectPreset = (days) => {
    selectedDays.value = days;
    form.validity_days = days;
};

const submitMove = () => {
    if (!props.isBulk && !props.customer) {
        alert('গ্রাহকের তথ্য পাওয়া যায়নি।');
        return;
    }
    if (props.isBulk && !props.targetAllFiltered && (!props.customerIds || props.customerIds.length === 0)) {
        alert('কোনো গ্রাহক নির্বাচন করা হয়নি।');
        return;
    }

    form.target_category = targetCategory.value;

    if (props.isBulk) {
        form.post(route('admin.customers.bulk-move-category'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    } else {
        form.post(route('admin.customers.move-category', props.customer.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('success');
                emit('close');
            },
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity" @click="$emit('close')"></div>

        <!-- Modal Dialog -->
        <div class="relative w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-3xl border border-brand-navy/80 bg-[#091A2E] p-6 shadow-2xl shadow-black/60 space-y-5">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-brand-navy pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 text-xl font-bold shadow-inner">
                        🔀
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-white flex items-center gap-2">
                            <span>গ্রাহক মুভ ও ফিল্টার সমন্বয় সিস্টেম</span>
                            <span v-if="isBulk" class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-purple-500/20 text-purple-300 border border-purple-500/40">
                                Bulk Mode
                            </span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            ভুল রিনিউ সংশোধন বা যেকোনো ফিল্টার ট্যাবে সরাসরি গ্রাহক মুভ করুন।
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="rounded-xl p-2 text-slate-400 hover:text-white hover:bg-slate-800 transition"
                >
                    ✕
                </button>
            </div>

            <!-- Target Scope Summary (Single vs Bulk) -->
            <div v-if="!isBulk && customer" class="rounded-2xl border border-brand-navy bg-[#071322] p-4 text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">গ্রাহকের নাম ও কোড:</span>
                    <span class="font-bold text-white font-mono">{{ customer.name }} ({{ customer.customer_code }})</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">বর্তমান প্যাকেজ ও মেয়াদ:</span>
                    <span class="font-mono text-cyan-400 font-bold">
                        {{ primaryConnection?.current_package?.name || 'N/A' }} 
                        <span class="text-slate-300 font-normal">| মেয়াদ: {{ primaryConnection?.expiry_date || 'N/A' }}</span>
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">বর্তমান অ্যাকাউন্ট ব্যালেন্স:</span>
                    <span :class="customer.balance > 0 ? 'text-rose-400 font-bold' : 'text-emerald-400 font-bold'" class="font-mono">
                        ৳{{ Number(customer.balance || 0).toFixed(2) }}
                    </span>
                </div>
            </div>

            <!-- Bulk Scope Summary -->
            <div v-else-if="isBulk" class="rounded-2xl border border-purple-500/30 bg-purple-950/20 p-4 text-xs flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base">👥</span>
                    <span class="text-slate-200 font-semibold">
                        {{ targetAllFiltered ? `সম্পূর্ণ ফিল্টারকৃত মোট ${totalFilteredCount} জন গ্রাহক নির্বাচিত` : `নির্বাচিত মোট ${customerIds.length} জন গ্রাহক` }}
                    </span>
                </div>
                <span class="px-2.5 py-1 rounded-xl text-xs font-mono font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                    {{ targetAllFiltered ? totalFilteredCount : customerIds.length }} Customers Selected
                </span>
            </div>

            <!-- Category Selection Cards -->
            <div class="space-y-2.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    কোথায় মুভ করতে চান? (লক্ষ্য ফিল্টার নির্ধারণ করুন):
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Option 1: Zero-charge Grace -->
                    <div
                        @click="targetCategory = 'zero_charge_renewed'"
                        :class="[
                            'p-3.5 rounded-2xl border cursor-pointer transition relative overflow-hidden flex flex-col justify-between',
                            targetCategory === 'zero_charge_renewed'
                                ? 'bg-purple-500/20 border-purple-400 shadow-lg shadow-purple-500/20 ring-1 ring-purple-400'
                                : 'bg-[#071322] border-brand-navy/70 hover:border-purple-400/50 hover:bg-[#0B1E36]'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-black text-purple-300 flex items-center gap-1.5">
                                <span>🎁</span>
                                <span>টাকা ছাড়া গ্রেস রিনিউ</span>
                            </span>
                            <span v-if="targetCategory === 'zero_charge_renewed'" class="w-2.5 h-2.5 rounded-full bg-purple-400"></span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            ভুল পেইড রিনিউ ফেরত নিয়ে ৩/৫ দিনের গ্রেস প্রদান এবং গ্রেস ফিল্টারে স্থানান্তর।
                        </p>
                    </div>

                    <!-- Option 2: Paid This Month -->
                    <div
                        @click="targetCategory = 'paid_this_month'"
                        :class="[
                            'p-3.5 rounded-2xl border cursor-pointer transition relative overflow-hidden flex flex-col justify-between',
                            targetCategory === 'paid_this_month'
                                ? 'bg-emerald-500/20 border-emerald-400 shadow-lg shadow-emerald-500/20 ring-1 ring-emerald-400'
                                : 'bg-[#071322] border-brand-navy/70 hover:border-emerald-400/50 hover:bg-[#0B1E36]'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-black text-emerald-400 flex items-center gap-1.5">
                                <span>💰</span>
                                <span>চলতি বিল পরিশোধিত</span>
                            </span>
                            <span v-if="targetCategory === 'paid_this_month'" class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            ১ মাসের সম্পূর্ণ রিনিউ ও বিল পরিশোধিত সম্পন্ন হিসেবে পেইড ফিল্টারে স্থানান্তর।
                        </p>
                    </div>

                    <!-- Option 3: Move to Due -->
                    <div
                        @click="targetCategory = 'due'"
                        :class="[
                            'p-3.5 rounded-2xl border cursor-pointer transition relative overflow-hidden flex flex-col justify-between',
                            targetCategory === 'due'
                                ? 'bg-rose-500/20 border-rose-500 shadow-lg shadow-rose-500/20 ring-1 ring-rose-500'
                                : 'bg-[#071322] border-brand-navy/70 hover:border-rose-500/50 hover:bg-[#0B1E36]'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-black text-rose-400 flex items-center gap-1.5">
                                <span>⚠️</span>
                                <span>বকেয়া রয়েছে (Due)</span>
                            </span>
                            <span v-if="targetCategory === 'due'" class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            পেমেন্ট রিভার্স করে বিল বকেয়া অবস্থায় স্থানান্তর করুন।
                        </p>
                    </div>

                    <!-- Option 4: Expiring in 3 Days -->
                    <div
                        @click="targetCategory = 'expiring_3d'"
                        :class="[
                            'p-3.5 rounded-2xl border cursor-pointer transition relative overflow-hidden flex flex-col justify-between',
                            targetCategory === 'expiring_3d'
                                ? 'bg-amber-500/20 border-amber-400 shadow-lg shadow-amber-500/20 ring-1 ring-amber-400'
                                : 'bg-[#071322] border-brand-navy/70 hover:border-amber-400/50 hover:bg-[#0B1E36]'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-black text-amber-400 flex items-center gap-1.5">
                                <span>⏰</span>
                                <span>৩ দিনে মেয়াদ শেষ</span>
                            </span>
                            <span v-if="targetCategory === 'expiring_3d'" class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            মেয়াদ আসন্ন সমাপ্তি (২-৩ দিন) ক্যাটাগরিতে স্থানান্তরিত করুন।
                        </p>
                    </div>

                    <!-- Option 5: Expired (Full width on sm) -->
                    <div
                        @click="targetCategory = 'expired'"
                        :class="[
                            'sm:col-span-2 p-3.5 rounded-2xl border cursor-pointer transition relative overflow-hidden flex flex-col justify-between',
                            targetCategory === 'expired'
                                ? 'bg-rose-950/40 border-rose-600 shadow-lg ring-1 ring-rose-600'
                                : 'bg-[#071322] border-brand-navy/70 hover:border-slate-500 hover:bg-[#0B1E36]'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-black text-slate-300 flex items-center gap-1.5">
                                <span>🚫</span>
                                <span>মেয়াদোত্তীর্ণ (Expired)</span>
                            </span>
                            <span v-if="targetCategory === 'expired'" class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            মেয়াদ গতকাল করে সংযোগ বন্ধ/মেয়াদোত্তীর্ণ তালিকায় নিন।
                        </p>
                    </div>
                </div>
            </div>

            <!-- Dynamic Settings for Selected Target -->
            <div class="rounded-2xl border border-brand-navy bg-[#071322]/80 p-4 space-y-4">
                <!-- Case: Zero Charge Grace Controls -->
                <div v-if="targetCategory === 'zero_charge_renewed'" class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-300">গ্রেস মেয়াদের দিন সংখ্যা নির্ধারণ করুন:</label>
                            <span class="text-xs font-mono font-bold text-purple-400">{{ form.validity_days }} দিন</span>
                        </div>
                        <!-- Preset Pill Buttons -->
                        <div class="flex flex-wrap gap-2 mb-2">
                            <button
                                v-for="d in presetDays"
                                :key="d"
                                type="button"
                                @click="selectPreset(d)"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-xs font-bold font-mono transition',
                                    form.validity_days === d
                                        ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30 border border-purple-400'
                                        : 'bg-[#091A2E] text-slate-300 border border-brand-navy hover:border-purple-400/40'
                                ]"
                            >
                                +{{ d }} দিন
                            </button>
                        </div>
                    </div>

                    <!-- Automatic Reversal Options -->
                    <div class="pt-2 border-t border-brand-navy/60 space-y-2">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none">
                            <input
                                v-model="form.reverse_accidental_payment"
                                type="checkbox"
                                class="mt-0.5 rounded border-brand-navy bg-[#091A2E] text-purple-600 focus:ring-purple-500"
                            />
                            <span class="text-xs text-slate-300 leading-snug">
                                <strong class="text-purple-300">চলতি মাসের ভুল পেমেন্ট রিভার্স (Reverse) করুন:</strong>
                                গ্রাহকের জমা হওয়া ভুল কালেকশন স্বয়ংক্রিয়ভাবে রিভার্স করে ব্যালেন্স সমন্বয় করা হবে।
                            </span>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none">
                            <input
                                v-model="form.void_renewal_invoice"
                                type="checkbox"
                                class="mt-0.5 rounded border-brand-navy bg-[#091A2E] text-purple-600 focus:ring-purple-500"
                            />
                            <span class="text-xs text-slate-300 leading-snug">
                                <strong class="text-purple-300">ভুল ইনভয়েস বাতিল (Cancel/Void) করুন:</strong>
                                ভুল রিনিউর সাথে তৈরি হওয়া ইনভয়েস বাতিল হবে যেন কোনো বকেয়া ব্যালেন্স না থাকে।
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Case: Paid This Month Controls -->
                <div v-else-if="targetCategory === 'paid_this_month'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">পেমেন্ট মেথড</label>
                        <select
                            v-model="form.payment_method"
                            class="w-full rounded-xl border border-brand-navy bg-[#091A2E] py-2 px-3 text-xs text-white focus:border-brand-sky"
                        >
                            <option value="cash">নগদ ক্যাশ (Cash)</option>
                            <option value="bkash">বিকাশ (bKash)</option>
                            <option value="nagad">নগদ (Nagad)</option>
                            <option value="bank">ব্যাংক (Bank)</option>
                            <option value="other">অন্যান্য (Other)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">প্যাকেজ বিলের পরিমাণ (৳)</label>
                        <input
                            v-model.number="form.amount"
                            type="number"
                            min="0"
                            class="w-full rounded-xl border border-brand-navy bg-[#091A2E] py-2 px-3 text-xs text-white font-mono focus:border-brand-sky"
                        />
                    </div>
                    <div class="sm:col-span-2 text-[11px] text-emerald-300/90 bg-emerald-500/10 p-2.5 rounded-xl border border-emerald-500/20">
                        ✓ ১ মাসের মেয়াদ যুক্ত হবে এবং পেমেন্ট সম্পন্ন হিসেবে গ্রাহক <strong>চলতি বিল পরিশোধিত</strong> ফিল্টারে মুভ হবে।
                    </div>
                </div>

                <!-- Case: Due Controls -->
                <div v-else-if="targetCategory === 'due'" class="space-y-2">
                    <p class="text-xs text-rose-300/90 leading-relaxed bg-rose-500/10 p-3 rounded-xl border border-rose-500/20">
                        ⚠️ <strong>বকেয়া তালিকায় স্থানান্তর:</strong> গ্রাহকের চলতি মাসের কোনো পেইড পেমেন্ট থাকলে তা স্বয়ংক্রিয়ভাবে রিভার্স করা হবে এবং বকেয়া ইনভয়েস কার্যকর হবে, ফলে গ্রাহক বকেয়া তালিকায় প্রদর্শিত হবে।
                    </p>
                </div>

                <!-- Case: Expiring in 3 Days Controls -->
                <div v-else-if="targetCategory === 'expiring_3d'" class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-300">মেয়াদ শেষের দিন (আজ থেকে কত দিন পর?):</label>
                    <div class="flex gap-2">
                        <button
                            v-for="d in [1, 2, 3]"
                            :key="d"
                            type="button"
                            @click="form.validity_days = d"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold font-mono transition',
                                form.validity_days === d
                                    ? 'bg-amber-500 text-white shadow-md shadow-amber-500/30'
                                    : 'bg-[#091A2E] text-slate-300 border border-brand-navy hover:border-amber-400'
                            ]"
                        >
                            {{ d }} দিন পর
                        </button>
                    </div>
                </div>

                <!-- Case: Expired Controls -->
                <div v-else-if="targetCategory === 'expired'" class="space-y-2">
                    <p class="text-xs text-slate-300 bg-slate-800/60 p-3 rounded-xl border border-brand-navy">
                        মেয়াদ <strong>গতকাল</strong> নির্ধারণ করা হবে এবং স্ট্যাটাস <strong>Expired</strong> করা হবে।
                    </p>
                </div>

                <!-- Notes / Reason -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                        মুভ করার কারণ / রিমার্কস (ঐচ্ছিক):
                    </label>
                    <input
                        v-model="form.notes"
                        type="text"
                        placeholder="যেমন: ভুল রিনিউ সংশোধন বা এডভান্স গ্রেস সমন্বয়..."
                        class="w-full rounded-xl border border-brand-navy bg-[#091A2E] py-2 px-3 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none"
                    />
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-navy">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="rounded-xl border border-brand-navy bg-transparent px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                >
                    বাতিল
                </button>
                <button
                    type="button"
                    @click="submitMove"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-600 via-brand-sky to-blue-600 hover:opacity-95 px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-cyan-600/30 transition disabled:opacity-50 cursor-pointer"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ form.processing ? 'মুভ হচ্ছে...' : (isBulk ? 'নির্বাচিতদের মুভ সম্পন্ন করুন ✓' : 'মুভ সম্পন্ন করুন ✓') }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
