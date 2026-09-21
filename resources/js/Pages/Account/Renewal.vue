<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    customer: Object,
    primaryConnection: Object,
    paymentAccounts: Array,
});

const currentPackage = props.primaryConnection?.current_package;
const monthlyPrice = parseFloat(currentPackage?.current_price?.price || 500);

// Default fallback accounts if none configured
const defaultFallbackAccounts = [
    { 
        id: 'bangla_qr', 
        name: 'বাংলা QR (All Bank & MFS)', 
        type: 'Bangla QR', 
        account_number: '01711223344', 
        qr_image: '/uploads/qr/bangla_qr_merchant.svg' 
    },
    { id: 3, name: 'bKash Merchant Account', type: 'Mobile Banking', account_number: '01711223344' },
    { id: 4, name: 'Nagad Business AC', type: 'Mobile Banking', account_number: '01811223344' },
];

const availableAccounts = computed(() => {
    if (props.paymentAccounts && props.paymentAccounts.length > 0) {
        return props.paymentAccounts;
    }
    return defaultFallbackAccounts;
});

// Form state
const initialAccount = availableAccounts.value[0] || {};
const form = useForm({
    validity_days: 30,
    account_id: initialAccount.id || '',
    payment_method: initialAccount.name || 'Bangla QR',
    reference: '',
});

const copied = ref(false);
const zoomQrModal = ref(false);

const calculateTotal = () => {
    const mult = form.validity_days / 30;
    return (monthlyPrice * mult).toFixed(2);
};

const activeAccount = computed(() => {
    return availableAccounts.value.find(a => a.id === form.account_id) || availableAccounts.value[0] || {};
});

const selectAccount = (acc) => {
    form.account_id = acc.id;
    form.payment_method = acc.name;
};

// Dynamic visual theme based on payment brand
const getTheme = (name = '', type = '') => {
    const lower = name.toLowerCase();

    // 1. Bangla QR (National Interoperable Standard)
    if (type === 'Bangla QR' || lower.includes('bangla qr') || lower.includes('বাংলা qr') || lower.includes('banglaqr')) {
        return {
            brandName: 'বাংলা QR',
            brandBadge: 'বQR',
            brandTag: 'All Bank & MFS Apps',
            activeClass: 'border-[#006A4E] bg-gradient-to-br from-[#006A4E]/25 to-[#F42A41]/10 ring-2 ring-[#006A4E]/60 shadow-lg shadow-[#006A4E]/20',
            badgeBg: 'bg-[#006A4E] text-[#86EFAC]',
            tagColor: 'text-emerald-400 font-bold',
            isBanglaQr: true,
            appInstructions: 'QR স্ক্যানার অপশন',
        };
    }

    // 2. bKash
    if (lower.includes('bkash')) {
        return {
            brandName: 'bKash',
            brandBadge: 'bK',
            brandTag: 'Merchant Wallet',
            activeClass: 'border-[#E2136E] bg-gradient-to-br from-[#E2136E]/20 to-[#E2136E]/5 ring-2 ring-[#E2136E]/60 shadow-lg shadow-[#E2136E]/15',
            badgeBg: 'bg-[#E2136E]',
            tagColor: 'text-[#E2136E]',
            isBanglaQr: false,
            appInstructions: 'Make Payment',
        };
    }

    // 3. Nagad
    if (lower.includes('nagad')) {
        return {
            brandName: 'Nagad',
            brandBadge: 'নগদ',
            brandTag: 'Business Wallet',
            activeClass: 'border-[#F7941D] bg-gradient-to-br from-[#F7941D]/20 to-[#F7941D]/5 ring-2 ring-[#F7941D]/60 shadow-lg shadow-[#F7941D]/15',
            badgeBg: 'bg-[#F7941D]',
            tagColor: 'text-[#F7941D]',
            isBanglaQr: false,
            appInstructions: 'Merchant Pay / Send Money',
        };
    }

    // 4. Rocket
    if (lower.includes('rocket')) {
        return {
            brandName: 'Rocket',
            brandBadge: 'রকেট',
            brandTag: 'DBBL Wallet',
            activeClass: 'border-[#8C3494] bg-gradient-to-br from-[#8C3494]/20 to-[#8C3494]/5 ring-2 ring-[#8C3494]/60 shadow-lg shadow-[#8C3494]/15',
            badgeBg: 'bg-[#8C3494]',
            tagColor: 'text-[#C66BD3]',
            isBanglaQr: false,
            appInstructions: 'Merchant Pay / Send Money',
        };
    }

    // 5. Upay
    if (lower.includes('upay')) {
        return {
            brandName: 'Upay',
            brandBadge: 'upay',
            brandTag: 'UCB Wallet',
            activeClass: 'border-[#005CA9] bg-gradient-to-br from-[#005CA9]/20 to-[#005CA9]/5 ring-2 ring-[#005CA9]/60 shadow-lg shadow-[#005CA9]/15',
            badgeBg: 'bg-[#005CA9]',
            tagColor: 'text-sky-400',
            isBanglaQr: false,
            appInstructions: 'Payment / Send Money',
        };
    }

    // Generic fallback
    return {
        brandName: name,
        brandBadge: '💳',
        brandTag: 'Digital Payment',
        activeClass: 'border-emerald-500 bg-gradient-to-br from-emerald-500/20 to-emerald-500/5 ring-2 ring-emerald-500/60 shadow-lg shadow-emerald-500/15',
        badgeBg: 'bg-emerald-600',
        tagColor: 'text-emerald-400',
        isBanglaQr: false,
        appInstructions: 'Payment',
    };
};

const copyToClipboard = (text) => {
    if (!text) return;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text);
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2200);
};

const submitRenewal = () => {
    form.post(route('account.renewal.store'));
};
</script>

<template>
    <Head title="Renew Connection - Pirgacha Internet" />

    <CustomerLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header Title -->
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-brand-orange animate-pulse"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-brand-orange">Self-Service Renewal</span>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight mt-1">Renew Subscription</h1>
                <p class="text-xs text-slate-400 mt-0.5">প্যাকেজ মেয়াদ বৃদ্ধি ও ইনস্ট্যান্ট অটো-অ্যাক্টিভেশন</p>
            </div>

            <!-- Current Package Summary Card -->
            <div class="rounded-3xl border border-[#17304F] bg-[#071527]/95 p-6 shadow-xl backdrop-blur-md">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">বর্তমান এক্টিভ প্যাকেজ</span>
                        <h2 class="text-xl font-black text-white mt-1">{{ currentPackage?.name || 'Standard Broadband Plan' }}</h2>
                        <div class="text-xs font-bold text-brand-cyan font-mono mt-1 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-cyan"></span>
                            {{ currentPackage?.speed_mbps || 10 }} Mbps Optical Fiber
                        </div>
                    </div>
                    <div class="sm:text-right border-t sm:border-t-0 pt-3 sm:pt-0 border-[#17304F]">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">বর্তমান মেয়াদের শেষ তারিখ</span>
                        <div class="text-lg font-mono font-black text-brand-orange mt-1">
                            {{ formatDate(primaryConnection?.expiry_date) }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">মেয়াদ স্বয়ংক্রিয়ভাবে যুক্ত হবে</div>
                    </div>
                </div>
            </div>

            <!-- Renewal Order Form Card -->
            <form @submit.prevent="submitRenewal" class="rounded-3xl border border-[#17304F] bg-[#071527]/95 p-6 md:p-8 backdrop-blur-md space-y-6 shadow-2xl">
                <!-- Validation Error Alert -->
                <div v-if="form.hasErrors" class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 text-xs text-rose-300 space-y-1">
                    <div v-for="(err, key) in form.errors" :key="key" class="font-medium">• {{ err }}</div>
                </div>

                <!-- 1. Select Validity Period -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-3">
                        ১. মেয়াদের সময়সীমা নির্ধারণ করুন (Extension Period)
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <button
                            type="button"
                            @click="form.validity_days = 30"
                            :class="[
                                form.validity_days === 30 
                                    ? 'border-brand-orange bg-gradient-to-b from-brand-orange/20 to-brand-orange/5 text-white ring-2 ring-brand-orange/60 shadow-lg shadow-brand-orange/20' 
                                    : 'border-[#1C3A5E] bg-[#0B1E36] text-slate-300 hover:border-slate-500 hover:bg-[#0E2442]',
                                'rounded-2xl border p-4 text-center transition cursor-pointer relative overflow-hidden'
                            ]"
                        >
                            <span v-if="form.validity_days === 30" class="absolute top-2 right-2 text-[10px] text-brand-orange font-bold">✓</span>
                            <div class="text-sm font-bold">৩০ দিন</div>
                            <div class="text-xs font-mono font-black text-brand-orange mt-1.5">৳{{ (monthlyPrice * 1).toFixed(0) }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">১ মাস</div>
                        </button>

                        <button
                            type="button"
                            @click="form.validity_days = 60"
                            :class="[
                                form.validity_days === 60 
                                    ? 'border-brand-orange bg-gradient-to-b from-brand-orange/20 to-brand-orange/5 text-white ring-2 ring-brand-orange/60 shadow-lg shadow-brand-orange/20' 
                                    : 'border-[#1C3A5E] bg-[#0B1E36] text-slate-300 hover:border-slate-500 hover:bg-[#0E2442]',
                                'rounded-2xl border p-4 text-center transition cursor-pointer relative overflow-hidden'
                            ]"
                        >
                            <span v-if="form.validity_days === 60" class="absolute top-2 right-2 text-[10px] text-brand-orange font-bold">✓</span>
                            <div class="text-sm font-bold">৬০ দিন</div>
                            <div class="text-xs font-mono font-black text-brand-orange mt-1.5">৳{{ (monthlyPrice * 2).toFixed(0) }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">২ মাস</div>
                        </button>

                        <button
                            type="button"
                            @click="form.validity_days = 90"
                            :class="[
                                form.validity_days === 90 
                                    ? 'border-brand-orange bg-gradient-to-b from-brand-orange/20 to-brand-orange/5 text-white ring-2 ring-brand-orange/60 shadow-lg shadow-brand-orange/20' 
                                    : 'border-[#1C3A5E] bg-[#0B1E36] text-slate-300 hover:border-slate-500 hover:bg-[#0E2442]',
                                'rounded-2xl border p-4 text-center transition cursor-pointer relative overflow-hidden'
                            ]"
                        >
                            <span v-if="form.validity_days === 90" class="absolute top-2 right-2 text-[10px] text-brand-orange font-bold">✓</span>
                            <div class="text-sm font-bold">৯০ দিন</div>
                            <div class="text-xs font-mono font-black text-brand-orange mt-1.5">৳{{ (monthlyPrice * 3).toFixed(0) }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">৩ মাস (ত্রৈমাসিক)</div>
                        </button>
                    </div>
                </div>

                <!-- 2. Dynamic Payment Channels (with Bangla QR Highlight) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-3">
                        ২. পেমেন্ট মেথড নির্বাচন করুন (Payment Channel)
                    </label>
                    <div :class="[
                        'grid gap-3.5',
                        availableAccounts.length === 1 ? 'grid-cols-1' :
                        availableAccounts.length === 2 ? 'grid-cols-1 sm:grid-cols-2' :
                        'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
                    ]">
                        <!-- Dynamic Account Cards -->
                        <div 
                            v-for="acc in availableAccounts"
                            :key="acc.id"
                            @click="selectAccount(acc)"
                            :class="[
                                form.account_id === acc.id 
                                    ? getTheme(acc.name, acc.type).activeClass 
                                    : 'border-[#1C3A5E] bg-[#0B1E36] hover:border-slate-500 hover:bg-[#0E2442]',
                                'rounded-2xl border p-3.5 cursor-pointer transition relative flex flex-col justify-between select-none'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div :class="[
                                        getTheme(acc.name, acc.type).badgeBg,
                                        'w-9 h-9 rounded-xl flex items-center justify-center text-white font-black text-xs shadow-md shrink-0'
                                    ]">
                                        {{ getTheme(acc.name, acc.type).brandBadge }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-sm text-white line-clamp-1">{{ acc.name }}</div>
                                        <div :class="[getTheme(acc.name, acc.type).tagColor, 'text-[10px] uppercase tracking-wider line-clamp-1']">
                                            {{ getTheme(acc.name, acc.type).brandTag }}
                                        </div>
                                    </div>
                                </div>
                                <div :class="[
                                    form.account_id === acc.id ? 'bg-emerald-500 text-white' : 'border border-slate-600',
                                    'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0'
                                ]">
                                    <span v-if="form.account_id === acc.id">✓</span>
                                </div>
                            </div>
                            
                            <div class="mt-3 pt-2 border-t border-[#1C3A5E] flex items-center justify-between text-[11px]">
                                <span class="text-slate-400 font-mono">
                                    {{ getTheme(acc.name, acc.type).isBanglaQr ? 'স্ক্যান পেমেন্ট:' : 'হিসাব/নম্বর:' }}
                                </span>
                                <span class="font-mono font-bold text-white tracking-wider">
                                    {{ getTheme(acc.name, acc.type).isBanglaQr ? 'Bangla QR ✓' : (acc.account_number || 'Cash') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Dynamic Payment Instructions Box with BANGLA QR Display -->
                <div class="rounded-3xl border border-[#1E3A5F] bg-[#081B30] p-5 md:p-6 space-y-4 shadow-inner">
                    <!-- If Active Account is Bangla QR (or has qr_image) -->
                    <div v-if="getTheme(activeAccount.name, activeAccount.type).isBanglaQr || activeAccount.qr_image" class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#17304F]">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">📷</span>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 block">
                                        জাতীয় বাংলা কিউআর পেমেন্ট (Bangla QR)
                                    </span>
                                    <h3 class="text-sm font-bold text-white">
                                        যেকোনো ব্যাংক বা MFS অ্যাপ দিয়ে স্ক্যান করুন
                                    </h3>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="zoomQrModal = true"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-orange hover:text-orange-300 bg-[#040D18] border border-slate-700 hover:border-brand-orange px-3 py-1.5 rounded-xl transition cursor-pointer self-start sm:self-auto"
                            >
                                <span>🔍</span>
                                <span>বড় করে দেখুন (Zoom QR)</span>
                            </button>
                        </div>

                        <!-- Bank-Issued Bangla QR Display Container -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-center">
                            <!-- Bangla QR Graphic Frame -->
                            <div class="sm:col-span-5 flex flex-col items-center">
                                <div 
                                    @click="zoomQrModal = true"
                                    class="relative p-2.5 bg-white rounded-2xl shadow-xl border-2 border-emerald-500/40 cursor-pointer hover:scale-[1.02] transition max-w-[220px]"
                                    title="ক্লিক করে বড় করে দেখুন"
                                >
                                    <img 
                                        :src="activeAccount.qr_image || '/uploads/qr/bangla_qr_merchant.svg'" 
                                        alt="Bank-issued Bangla QR" 
                                        class="w-full h-auto rounded-xl object-contain mx-auto"
                                    />
                                    <div class="absolute inset-0 rounded-2xl bg-black/5 hover:bg-transparent transition"></div>
                                    <span class="absolute bottom-1.5 right-2 text-[9px] bg-black/75 text-white px-1.5 py-0.5 rounded font-bold">
                                        🔍 Zoom
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-400 mt-1.5 text-center block">
                                    ব্যাংক কর্তৃক প্রদত্ত অফিসিয়াল বাংলা কিউআর
                                </span>
                            </div>

                            <!-- Step by Step Instructions for Bangla QR -->
                            <div class="sm:col-span-7 space-y-2.5 text-xs text-slate-200">
                                <div class="bg-[#040D18] rounded-xl p-3 border border-[#1E3E66] space-y-1">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold block">সাপোর্টেড অ্যাপসমূহ:</span>
                                    <p class="font-bold text-emerald-400 text-[11px] leading-relaxed">
                                        bKash, Nagad, Rocket, Upay, Cellfin, Islami Bank, BRAC Astha, City Touch, NexusPay ইত্যাদি।
                                    </p>
                                </div>

                                <div class="space-y-1.5 leading-relaxed text-slate-300">
                                    <div class="flex items-start gap-2">
                                        <span class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">১</span>
                                        <span>আপনার যেকোনো <strong>ব্যাংক বা MFS অ্যাপ</strong>-এ ঢুকে <strong>QR স্ক্যানার</strong> চালু করুন।</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">২</span>
                                        <span>পাশের <strong>বাংলা QR</strong> কোডটি স্ক্যান করে বিলের পরিমাণ <strong>৳{{ calculateTotal() }}</strong> দিন।</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">৩</span>
                                        <span>পেমেন্ট নিশ্চিত করার পর পাওয়া <strong>TrxID / Reference</strong> নিচের বক্সে দিন।</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- If Active Account is Standard Mobile Wallet (without QR or direct number) -->
                    <div v-else class="space-y-3.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#17304F]">
                            <div class="flex items-center gap-2">
                                <span class="text-base">💳</span>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                                        পেমেন্ট চ্যানেল
                                    </span>
                                    <span class="text-xs font-semibold text-white">
                                        {{ activeAccount.name }} ({{ activeAccount.type || 'Mobile Banking' }})
                                    </span>
                                </div>
                            </div>

                            <!-- Payment Number Highlight with Copy Button -->
                            <div v-if="activeAccount.account_number" class="flex items-center gap-2 bg-[#040D18] border border-[#1E3E66] rounded-xl px-3 py-1.5">
                                <span class="text-sm font-mono font-black text-brand-orange tracking-wider">
                                    {{ activeAccount.account_number }}
                                </span>
                                <button
                                    type="button"
                                    @click="copyToClipboard(activeAccount.account_number)"
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-brand-navy hover:bg-brand-orange hover:text-white text-slate-200 border border-slate-700 transition flex items-center gap-1 cursor-pointer active:scale-95"
                                    title="নম্বর কপি করুন"
                                >
                                    <span v-if="copied" class="text-emerald-400">কপি হয়েছে ✓</span>
                                    <span v-else>কপি 📋</span>
                                </button>
                            </div>
                        </div>

                        <!-- Step by Step Instructions in Bengali -->
                        <div class="text-xs text-slate-300 space-y-1.5 leading-relaxed">
                            <div class="flex items-start gap-2">
                                <span class="text-brand-orange font-bold">১.</span>
                                <span>আপনার <strong>{{ getTheme(activeAccount.name).brandName }} App</strong>-এ গিয়ে <strong>{{ getTheme(activeAccount.name).appInstructions }}</strong> অপশন বেছে নিন।</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-brand-orange font-bold">২.</span>
                                <span>নম্বর বক্সে <strong>{{ activeAccount.account_number || 'প্রদত্ত নম্বর' }}</strong> দিন এবং টাকার পরিমাণ <strong>৳{{ calculateTotal() }}</strong> লিখুন।</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-brand-orange font-bold">৩.</span>
                                <span>পেমেন্ট সফল হওয়ার পর প্রাপ্ত <strong>Transaction ID (TrxID)</strong> কপি করে নিচের বক্সে দিন।</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Transaction Reference / TrxID Input -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            ৩. ট্রানজেকশন আইডি (TrxID) দিন <span class="text-rose-400">*</span>
                        </label>
                        <span class="text-[11px] font-mono text-brand-cyan">
                            e.g. 9JA882LK1
                        </span>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-mono font-bold text-xs">
                            TrxID:
                        </div>
                        <input
                            v-model="form.reference"
                            type="text"
                            required
                            :placeholder="`এখানে ${activeAccount.name || 'পেমেন্ট'} এর TrxID পেস্ট করুন`"
                            class="w-full pl-16 pr-4 py-3.5 rounded-2xl border-2 border-[#1E3E66] bg-[#0B1E36] text-sm text-white placeholder-slate-500 focus:border-brand-orange focus:bg-[#0E2442] focus:ring-4 focus:ring-brand-orange/20 font-mono tracking-wider uppercase outline-none transition shadow-inner"
                        />
                    </div>
                    <p class="text-[11px] text-slate-400">
                        সঠিক TrxID প্রদান করলে পেমেন্ট স্বয়ংক্রিয়ভাবে ভেরিফাই হয়ে আপনার ইন্টারনেট চালু হয়ে যাবে।
                    </p>
                </div>

                <!-- Total Summary & Submit Action -->
                <div class="pt-6 border-t border-[#17304F] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">মোট প্রদেয় বিল (Total Payable)</span>
                        <div class="text-3xl font-black text-brand-orange font-mono mt-0.5">৳{{ calculateTotal() }}</div>
                        <div class="text-[11px] text-slate-400">কোনো অতিরিক্ত চার্জ নেই • ইনস্ট্যান্ট রিনিউয়াল</div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-2xl bg-gradient-to-r from-brand-orange to-amber-500 hover:from-orange-500 hover:to-amber-400 px-8 py-4 text-xs font-black uppercase tracking-wider text-white shadow-xl shadow-brand-orange/30 transition active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span>{{ form.processing ? 'যাচাই করা হচ্ছে...' : 'পেমেন্ট নিশ্চিত ও রিনিউ করুন →' }}</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Zoom Bangla QR Modal -->
        <div v-if="zoomQrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md">
            <div class="w-full max-w-md rounded-3xl border border-slate-700 bg-slate-900 p-6 shadow-2xl space-y-4 text-center">
                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">📷</span>
                        <h3 class="text-sm font-bold text-white">অফিসিয়াল ব্যাংক বাংলা QR (Bangla QR)</h3>
                    </div>
                    <button @click="zoomQrModal = false" class="text-slate-400 hover:text-white text-base">✕</button>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-2xl border-4 border-emerald-600/30">
                    <img 
                        :src="activeAccount.qr_image || '/uploads/qr/bangla_qr_merchant.svg'" 
                        alt="Bangla QR" 
                        class="w-full max-h-96 object-contain mx-auto" 
                    />
                </div>

                <div class="space-y-1 text-xs text-slate-300">
                    <div>প্রদেয় পরিমাণ: <strong class="text-brand-orange font-mono text-sm">৳{{ calculateTotal() }}</strong></div>
                    <div class="text-[11px] text-slate-400">যেকোনো ব্যাংক বা বিকাশ, নগদ, রকেট, সেলফিন দিয়ে স্ক্যান করুন</div>
                </div>

                <div class="flex gap-2 pt-2">
                    <a 
                        :href="activeAccount.qr_image || '/uploads/qr/bangla_qr_merchant.svg'" 
                        download="Bangla_QR_Pirgacha_Internet" 
                        target="_blank"
                        class="flex-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 py-2.5 text-xs font-bold text-white transition text-center"
                    >
                        📥 কিউআর ডাউনলোড করুন
                    </a>
                    <button
                        type="button"
                        @click="zoomQrModal = false"
                        class="flex-1 rounded-xl bg-slate-800 hover:bg-slate-700 py-2.5 text-xs font-bold text-slate-200 transition"
                    >
                        বন্ধ করুন
                    </button>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
