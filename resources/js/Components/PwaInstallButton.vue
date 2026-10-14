<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'navbar', // 'navbar' | 'drawer' | 'banner'
    },
    label: {
        type: String,
        default: 'ইনস্টল অ্যাপ',
    },
    title: {
        type: String,
        default: 'Pirgacha Internet App ইনস্টল করুন',
    },
    description: {
        type: String,
        default: 'ডেস্কটপ, ল্যাপটপ বা মোবাইলে পূর্ণ স্ক্রিন অ্যাপ হিসেবে ব্যবহার করুন — ব্রাউজার না খুলেই তাৎক্ষণিক এক্সেস!',
    },
    storageKey: {
        type: String,
        default: 'admin_pwa_banner_dismissed',
    },
});

const isStandalone = ref(false);
const canInstall = ref(false);
const showIosModal = ref(false);
const showDesktopModal = ref(false);
const isDismissed = ref(false);
const isIos = ref(false);

const checkDevice = () => {
    // Check if already in standalone PWA mode
    isStandalone.value = window.matchMedia('(display-mode: standalone)').matches 
        || Boolean(window.navigator.standalone) 
        || document.referrer.includes('android-app://');

    // Check iOS Safari
    isIos.value = /iphone|ipad|ipod/i.test(navigator.userAgent) 
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    if (window.deferredPwaPrompt) {
        canInstall.value = true;
    } else if (isIos.value && !isStandalone.value) {
        canInstall.value = true;
    } else {
        // Also show on desktop / Android browsers so users can always trigger instructions
        canInstall.value = !isStandalone.value;
    }
};

const handleInstallClick = async () => {
    // 1. If standard beforeinstallprompt is ready (Chrome, Edge, Android)
    if (window.deferredPwaPrompt) {
        try {
            window.deferredPwaPrompt.prompt();
            const choice = await window.deferredPwaPrompt.userChoice;
            if (choice && choice.outcome === 'accepted') {
                canInstall.value = false;
                window.deferredPwaPrompt = null;
            }
        } catch (e) {
            console.warn('PWA prompt failed:', e);
        }
        return;
    }

    // 2. If iOS device (Safari doesn't support beforeinstallprompt)
    if (isIos.value) {
        showIosModal.value = true;
        return;
    }

    // 3. Fallback for Desktop/Android where browser manages the prompt
    showDesktopModal.value = true;
};

const onPwaInstallable = () => {
    canInstall.value = true;
};

const dismissBanner = () => {
    isDismissed.value = true;
    try {
        sessionStorage.setItem(props.storageKey, 'true');
    } catch (e) {}
};

onMounted(() => {
    try {
        if (sessionStorage.getItem(props.storageKey) === 'true') {
            isDismissed.value = true;
        }
    } catch (e) {}

    checkDevice();
    window.addEventListener('pwa-installable', onPwaInstallable);
    window.addEventListener('appinstalled', () => {
        isStandalone.value = true;
        canInstall.value = false;
    });
});

onUnmounted(() => {
    window.removeEventListener('pwa-installable', onPwaInstallable);
});
</script>

<template>
    <!-- Don't render if already running in standalone app mode -->
    <div v-if="!isStandalone && canInstall" class="inline-block">
        <!-- Variant: Navbar Button (Header) -->
        <button
            v-if="variant === 'navbar'"
            type="button"
            @click="handleInstallClick"
            class="relative inline-flex items-center gap-1.5 rounded-xl border border-brand-orange/40 bg-gradient-to-r from-brand-orange/15 to-brand-amber/15 px-2.5 sm:px-3 py-1.5 text-xs font-bold text-brand-orange hover:text-white hover:bg-brand-orange hover:border-brand-orange transition-all duration-200 shadow-sm active:scale-95 group"
            title="কম্পিউটার বা মোবাইলে ইনস্টল করুন (Install PWA App)"
        >
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-orange opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-orange"></span>
            </span>
            <svg class="h-4 w-4 text-brand-orange group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span class="hidden sm:inline font-extrabold tracking-wide">{{ label }}</span>
        </button>

        <!-- Variant: Drawer Menu Item (Mobile Menu) -->
        <button
            v-else-if="variant === 'drawer'"
            type="button"
            @click="handleInstallClick"
            class="w-full flex items-center justify-between rounded-xl border border-brand-orange/40 bg-brand-orange/10 px-3.5 py-2.5 text-xs font-bold text-brand-orange hover:bg-brand-orange hover:text-white transition shadow-sm active:scale-95"
        >
            <div class="flex items-center gap-2.5">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-orange/20 text-brand-orange">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </span>
                <div class="text-left">
                    <div class="font-extrabold">{{ label }}</div>
                    <div class="text-[10px] text-slate-400 font-normal">মোবাইল বা পিসিতে ইনস্টল করুন</div>
                </div>
            </div>
            <svg class="h-4 w-4 text-brand-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
        </button>

        <!-- Variant: Dashboard Quick Banner -->
        <div
            v-else-if="variant === 'banner' && !isDismissed"
            class="relative overflow-hidden rounded-2xl border border-brand-orange/30 bg-gradient-to-r from-[#0B1E36] via-[#0E2A4D] to-[#0B1E36] p-4 sm:p-5 shadow-xl mb-6"
        >
            <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-brand-orange/10 blur-2xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-orange to-brand-amber text-white shadow-lg shadow-brand-orange/30">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-extrabold text-white">
                                {{ title }}
                            </h3>
                            <span class="rounded-full bg-brand-orange/20 border border-brand-orange/40 px-2 py-0.5 text-[10px] font-bold text-brand-orange uppercase tracking-wider">PWA</span>
                        </div>
                        <p class="text-xs text-slate-300 mt-0.5">
                            {{ description }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center">
                    <button
                        type="button"
                        @click="handleInstallClick"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-orange via-brand-amber to-brand-gold hover:opacity-95 px-4 py-2 text-xs font-black text-white shadow-lg shadow-brand-orange/30 transition active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>এখনই ইনস্টল করুন</span>
                    </button>
                    <button
                        type="button"
                        @click="dismissBanner"
                        class="rounded-xl border border-slate-700 hover:border-slate-500 bg-slate-800/60 p-2 text-slate-400 hover:text-white transition"
                        title="বাতিল করুন"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- iOS Step-by-Step Installation Modal -->
        <div v-if="showIosModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="relative w-full max-w-md rounded-3xl border border-brand-navy bg-[#0B1E36] p-6 text-center shadow-2xl">
                <button
                    type="button"
                    @click="showIosModal = false"
                    class="absolute top-4 right-4 text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-orange/20 text-brand-orange border border-brand-orange/40">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>

                <h3 class="text-lg font-black text-white">iPhone বা iPad-এ ইনস্টল করার নিয়ম</h3>
                <p class="text-xs text-slate-300 mt-1 mb-5">
                    Apple Safari ব্রাউজার থেকে সরাসরি ৩টি সহজ ধাপে অ্যাপটি আপনার ডিভাইসের হোম স্ক্রিনে যোগ করুন:
                </p>

                <div class="space-y-3 text-left bg-slate-900/60 border border-brand-navy/60 rounded-2xl p-4 text-xs text-slate-200">
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-orange text-white font-bold text-xs">১</span>
                        <div>Safari ব্রাউজারের নিচের মেনুতে থাকা <strong>Share ( <svg class="inline h-4 w-4 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg> )</strong> আইকনে ট্যাপ করুন।</div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-orange text-white font-bold text-xs">২</span>
                        <div>মেনুটি স্ক্রোল করে <strong>"Add to Home Screen" ( <svg class="inline h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg> )</strong> বাটনে ট্যাপ করুন।</div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-orange text-white font-bold text-xs">৩</span>
                        <div>উপরে ডানদিকের <strong>"Add"</strong> বাটনে চাপলেই আপনার মোবাইলের হোম স্ক্রিনে অ্যাপটি ইনস্টল হয়ে যাবে!</div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="showIosModal = false"
                    class="mt-5 w-full rounded-xl bg-brand-orange hover:bg-brand-orange/90 py-2.5 text-xs font-black text-white shadow-lg shadow-brand-orange/25 transition"
                >
                    বুঝেছি (Got it)
                </button>
            </div>
        </div>

        <!-- Desktop / Other Browser Guide Modal -->
        <div v-if="showDesktopModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="relative w-full max-w-md rounded-3xl border border-brand-navy bg-[#0B1E36] p-6 text-center shadow-2xl">
                <button
                    type="button"
                    @click="showDesktopModal = false"
                    class="absolute top-4 right-4 text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-sky/20 text-brand-sky border border-brand-sky/40">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>

                <h3 class="text-lg font-black text-white">ডেস্কটপ / পিসিতে ইনস্টল করার নিয়ম</h3>
                <p class="text-xs text-slate-300 mt-1 mb-5">
                    Chrome বা Edge ব্রাউজারে অ্যাপ ইনস্টল করার ধাপসমূহ:
                </p>

                <div class="space-y-3 text-left bg-slate-900/60 border border-brand-navy/60 rounded-2xl p-4 text-xs text-slate-200">
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-sky text-white font-bold text-xs">১</span>
                        <div>ব্রাউজারের ওপরের অ্যাড্রেস বার (URL bar)-এর একদম ডানপাশে থাকা <strong>ইনস্টল আইকন ( <svg class="inline h-3.5 w-3.5 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg> )</strong>-এ ক্লিক করুন।</div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-sky text-white font-bold text-xs">২</span>
                        <div>অথবা ব্রাউজারের ৩-ডট মেনু (⋮) ওপেন করে <strong>"Install Pirgacha Internet"</strong> বা <strong>"অ্যাপটি ইনস্টল করুন"</strong> চাপুন।</div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-sky text-white font-bold text-xs">৩</span>
                        <div>পপ-আপে <strong>"Install"</strong> বাটনে ক্লিক করলেই আপনার ডেস্কটপ/স্টার্ট মেনুতে অ্যাপটি যোগ হয়ে যাবে!</div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="showDesktopModal = false"
                    class="mt-5 w-full rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue hover:opacity-95 py-2.5 text-xs font-black text-white shadow-lg shadow-brand-sky/25 transition"
                >
                    ঠিক আছে (OK)
                </button>
            </div>
        </div>
    </div>
</template>
