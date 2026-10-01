<script setup>
import { computed } from 'vue';

const props = defineProps({
    text: {
        type: String,
        default: '',
    },
    showBreakdown: {
        type: Boolean,
        default: true,
    },
    recipientCount: {
        type: Number,
        default: 1,
    },
    panelBalance: {
        type: [String, Number],
        default: null,
    },
    remainingSms: {
        type: [Number, String],
        default: null,
    },
    smsRate: {
        type: [Number, String],
        default: null,
    },
    panelGatewayName: {
        type: String,
        default: null,
    },
    isCheckingBalance: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['refresh-balance']);

// Check if string contains any character outside standard GSM 7-bit charset
// Any Bengali character ([\u0980-\u09FF]), Bengali digits, Bengali punctuation, emojis, curved quotes etc. forces UCS-2 Unicode.
const isUnicode = computed(() => {
    if (!props.text) return false;
    return /[^\u0020-\u007E\r\n\t]/.test(props.text);
});

const charCount = computed(() => props.text?.length || 0);

const calculation = computed(() => {
    const len = charCount.value;
    const unicode = isUnicode.value;

    const maxSingle = unicode ? 70 : 160;
    const maxMulti = unicode ? 67 : 153;

    if (len === 0) {
        return {
            smsCount: 0,
            remaining: maxSingle,
            currentPartLimit: maxSingle,
            usedInPart: 0,
            percentInPart: 0,
            encoding: unicode ? 'বাংলা / ইউনিকোড (UCS-2)' : 'ইংরেজি (GSM-7)',
            ruleText: unicode ? '১ম SMS: ৭০ অক্ষর | পরবর্তী: ৬৭ অক্ষর/SMS' : '১ম SMS: ১৬০ অক্ষর | পরবর্তী: ১৫৩ অক্ষর/SMS',
        };
    }

    let smsCount = 1;
    let remaining = 0;
    let currentPartLimit = maxSingle;
    let usedInPart = len;

    if (len <= maxSingle) {
        smsCount = 1;
        remaining = maxSingle - len;
        currentPartLimit = maxSingle;
        usedInPart = len;
    } else {
        smsCount = Math.ceil(len / maxMulti);
        remaining = (smsCount * maxMulti) - len;
        currentPartLimit = maxMulti;
        // How many characters used in the current part
        usedInPart = maxMulti - remaining;
    }

    const percentInPart = Math.min(100, Math.round((usedInPart / currentPartLimit) * 100));

    return {
        smsCount,
        remaining,
        currentPartLimit,
        usedInPart,
        percentInPart,
        encoding: unicode ? 'বাংলা / ইউনিকোড (UCS-2)' : 'ইংরেজি (GSM-7)',
        ruleText: unicode ? '১ম SMS: ৭০ অক্ষর | পরবর্তী: ৬৭ অক্ষর/SMS' : '১ম SMS: ১৬০ অক্ষর | পরবর্তী: ১৫৩ অক্ষর/SMS',
    };
});

const totalSmsNeeded = computed(() => {
    return calculation.value.smsCount * (props.recipientCount || 1);
});

const effectiveRemainingSms = computed(() => {
    if (props.remainingSms !== null && props.remainingSms !== undefined && !isNaN(Number(props.remainingSms))) {
        return Math.floor(Number(props.remainingSms));
    }
    if (props.panelBalance !== null && props.panelBalance !== undefined && !isNaN(parseFloat(props.panelBalance))) {
        const rate = (props.smsRate && Number(props.smsRate) > 0) ? Number(props.smsRate) : 0.30;
        return Math.floor(parseFloat(props.panelBalance) / rate);
    }
    return null;
});

const isDeficit = computed(() => {
    if (effectiveRemainingSms.value === null || totalSmsNeeded.value <= 0) return false;
    return totalSmsNeeded.value > effectiveRemainingSms.value;
});

const deficitCount = computed(() => {
    if (!isDeficit.value) return 0;
    return totalSmsNeeded.value - effectiveRemainingSms.value;
});

const hasTemplateTags = computed(() => {
    return /\{[a-zA-Z0-9_]+\}/.test(props.text || '');
});
</script>

<template>
    <div class="mt-2 space-y-2 rounded-xl bg-[#061220]/90 border border-brand-navy p-3 text-xs select-none">
        <!-- Live Panel Remaining SMS & Balance Strip -->
        <div
            v-if="panelBalance !== null && panelBalance !== undefined"
            class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 rounded-lg bg-[#071322] border border-brand-navy shadow-inner"
        >
            <div class="flex items-center gap-2 flex-wrap">
                <span :class="['w-2 h-2 rounded-full animate-pulse', isDeficit ? 'bg-amber-400' : 'bg-emerald-400']"></span>
                <span class="text-slate-400 font-medium">প্যানেলে অবশিষ্ট আছে:</span>

                <span v-if="effectiveRemainingSms !== null" class="flex items-center gap-1.5">
                    <strong :class="['font-mono font-bold text-sm tracking-tight', isDeficit ? 'text-amber-300' : 'text-emerald-400']">
                        ~{{ effectiveRemainingSms }} টি SMS
                    </strong>
                    <span class="text-[11px] text-slate-400 font-mono">(৳ {{ panelBalance }})</span>
                </span>
                <strong v-else class="text-emerald-400 font-mono font-bold text-sm tracking-tight">
                    {{ panelBalance }}
                </strong>

                <button
                    type="button"
                    @click="$emit('refresh-balance')"
                    :disabled="isCheckingBalance"
                    class="text-slate-400 hover:text-brand-sky p-0.5 rounded transition disabled:opacity-50"
                    title="প্যানেল ব্যালেন্স রিফ্রেশ করুন"
                >
                    <svg :class="['w-3.5 h-3.5', isCheckingBalance ? 'animate-spin text-brand-sky' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>

                <span v-if="isDeficit" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                    ⚠️ ঘাটতি: ~{{ deficitCount }} টি SMS
                </span>
            </div>

            <div v-if="recipientCount > 1" class="text-slate-300 font-medium text-[11px] flex items-center gap-1.5">
                <span>প্রাপক: <strong class="text-white font-mono">{{ recipientCount }}</strong> জন</span>
                <span class="text-slate-500">•</span>
                <span>মোট প্রয়োজন: <strong class="text-brand-sky font-mono font-bold">{{ totalSmsNeeded }}</strong> টি SMS</span>
            </div>
        </div>

        <!-- Top Metrics Row -->
        <div class="flex flex-wrap items-center justify-between gap-2">
            <!-- Left: Character Count & Encoding Badge -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#091A2E] border border-brand-navy font-mono text-slate-200">
                    <span class="text-slate-400 font-sans">অক্ষর:</span>
                    <strong class="text-white">{{ charCount }}</strong>
                </span>

                <span
                    :class="[
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition-all',
                        isUnicode
                            ? 'bg-amber-950/40 text-amber-300 border-amber-800/50 shadow-sm'
                            : 'bg-brand-navy/60 text-brand-sky border-brand-navy'
                    ]"
                >
                    <span v-if="isUnicode" class="text-xs">🇧🇩</span>
                    <span v-else class="text-xs">🇬🇧</span>
                    {{ calculation.encoding }}
                </span>
            </div>

            <!-- Right: SMS Count & Remaining Characters Badge -->
            <div class="flex items-center gap-2 flex-wrap">
                <span
                    :class="[
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border shadow-sm transition-all',
                        calculation.smsCount <= 1
                            ? 'bg-emerald-950/50 text-emerald-400 border-emerald-800/50'
                            : calculation.smsCount === 2
                                ? 'bg-amber-950/50 text-amber-300 border-amber-800/50'
                                : 'bg-rose-950/50 text-rose-300 border-rose-800/50'
                    ]"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <span v-if="recipientCount > 1">
                        <span class="font-mono text-sm">{{ calculation.smsCount }}</span> টি/জন
                        <span class="font-normal text-[11px] opacity-90">(মোট {{ totalSmsNeeded }} টি)</span>
                    </span>
                    <span v-else>
                        <span class="font-mono text-sm">{{ calculation.smsCount }}</span> টি SMS কাটবে
                    </span>
                </span>

                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#091A2E] border border-brand-navy text-[11px] font-mono text-slate-300">
                    <span>বাকি:</span>
                    <strong :class="calculation.remaining <= 10 ? 'text-amber-400' : 'text-slate-200'">
                        {{ calculation.remaining }}
                    </strong>
                    <span class="text-slate-500 font-sans">অক্ষর</span>
                </span>
            </div>
        </div>

        <!-- Progress Bar for Current SMS Part -->
        <div v-if="charCount > 0" class="space-y-1 pt-1">
            <div class="w-full bg-[#091A2E] h-1.5 rounded-full overflow-hidden border border-brand-navy/60">
                <div
                    class="h-full transition-all duration-300 rounded-full"
                    :class="[
                        calculation.smsCount <= 1
                            ? 'bg-gradient-to-r from-emerald-500 to-teal-400'
                            : calculation.smsCount === 2
                                ? 'bg-gradient-to-r from-amber-500 to-brand-orange'
                                : 'bg-gradient-to-r from-rose-500 to-red-400'
                    ]"
                    :style="{ width: `${calculation.percentInPart}%` }"
                ></div>
            </div>
        </div>

        <!-- Bottom Explainer & Warning -->
        <div v-if="showBreakdown" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-[11px] text-slate-400 pt-1 border-t border-brand-navy/40">
            <div class="flex items-center gap-1.5">
                <span class="text-brand-sky">ℹ️</span>
                <span>{{ calculation.ruleText }}</span>
            </div>

            <div v-if="hasTemplateTags" class="text-amber-400/90 flex items-center gap-1">
                <span>⚠️</span>
                <span>ডায়নামিক ট্যাগ যুক্ত থাকায় গ্রাহকভেদে দৈর্ঘ্য পরিবর্তিত হতে পারে।</span>
            </div>
        </div>
    </div>
</template>
