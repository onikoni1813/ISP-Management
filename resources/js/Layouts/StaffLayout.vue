<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { syncService } from '@/Services/syncService';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    title: {
        type: String,
        default: 'Staff Portal',
    },
});

const page = usePage();
const user = page.props.auth.user;

const staffDisplayName = computed(() => {
    if (!user?.name) return 'Staff';
    return user.name
        .replace(/^Field\s+Technician\s+/i, '')
        .replace(/^Field\s+/i, '')
        .trim() || user.name;
});

const isOnline = ref(navigator.onLine);
const pendingSyncCount = ref(0);
const isSyncing = ref(false);
const syncMessage = ref('');
const assignedComplaintsCount = ref(0);
const hasNewTaskAlert = ref(false);
let lastKnownComplaintId = null;

const playNotificationSound = () => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
        osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.15); // A5
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.35);
    } catch (e) {
        // audio might be blocked until user interaction
    }
};

const checkLiveAssignedComplaints = async () => {
    if (!navigator.onLine) return;
    try {
        const res = await fetch(route('staff.api.live-counts'));
        if (res.ok) {
            const data = await res.json();
            const prevCount = assignedComplaintsCount.value;
            assignedComplaintsCount.value = data.open_complaints_count || 0;

            if (data.latest_complaint) {
                if (lastKnownComplaintId && data.latest_complaint.id > lastKnownComplaintId) {
                    hasNewTaskAlert.value = true;
                    playNotificationSound();
                }
                lastKnownComplaintId = data.latest_complaint.id;
            }
        }
    } catch (e) {
        // silent fail
    }
};

const checkSyncStatus = async () => {
    try {
        const status = await syncService.getSyncStatus();
        pendingSyncCount.value = status.pendingCount;

        // Auto-flush pending mutations whenever online and mutations exist
        if (navigator.onLine && pendingSyncCount.value > 0 && !isSyncing.value) {
            await autoSync();
        }
    } catch (e) {
        console.error('Error getting sync status', e);
    }
};

const autoSync = async (silent = true) => {
    if (!navigator.onLine || isSyncing.value) return;

    isSyncing.value = true;
    if (!silent) syncMessage.value = 'Syncing...';

    try {
        // 1. Flush any pending offline mutations to server
        await syncService.flushPendingMutations();
        // 2. Download fresh cache updates from server
        await syncService.downloadBootstrapCache();
        await checkSyncStatus();
        
        syncMessage.value = 'Synced';
        setTimeout(() => { syncMessage.value = ''; }, 2000);
    } catch (err) {
        console.error('Auto sync failed', err);
        if (!silent) {
            syncMessage.value = 'Sync error';
            setTimeout(() => { syncMessage.value = ''; }, 3000);
        }
    } finally {
        isSyncing.value = false;
    }
};

const triggerSync = async () => {
    if (!navigator.onLine) {
        alert('Network offline. Connect to internet to sync.');
        return;
    }
    await autoSync(false);
};

const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
    if (isOnline.value) {
        // Automatically sync immediately when coming back online
        autoSync(false);
    }
};

let syncInterval = null;
let autoSyncRoutine = null;
let liveComplaintInterval = null;

const cacheCurrentPageForOffline = async () => {
    if ('caches' in window && 'serviceWorker' in navigator) {
        try {
            const cache = await caches.open('pirgacha-isp-cache-v5');
            const res = await fetch(window.location.href, { credentials: 'same-origin' });
            if (res && res.status === 200) {
                await cache.put(window.location.href, res.clone());
                await cache.put('/staff/dashboard', res.clone());
                await cache.put('/staff/login', res.clone());
            }
        } catch (e) {
            // silent catch
        }
    }
};

onMounted(() => {
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    
    // Initial status check and initial background sync
    checkSyncStatus();
    if (navigator.onLine) {
        cacheCurrentPageForOffline();
        autoSync(true);
        checkLiveAssignedComplaints();
    }

    // Check sync status every 10 seconds
    syncInterval = setInterval(checkSyncStatus, 10000);
    // Poll for new assigned tasks every 12 seconds
    liveComplaintInterval = setInterval(checkLiveAssignedComplaints, 12000);
    // Background auto-sync routine every 60 seconds
    autoSyncRoutine = setInterval(() => {
        if (navigator.onLine) {
            autoSync(true);
        }
    }, 60000);
});

onUnmounted(() => {
    window.removeEventListener('online', updateOnlineStatus);
    window.removeEventListener('offline', updateOnlineStatus);
    if (syncInterval) clearInterval(syncInterval);
    if (liveComplaintInterval) clearInterval(liveComplaintInterval);
    if (autoSyncRoutine) clearInterval(autoSyncRoutine);
});


</script>

<template>
    <div class="min-h-screen bg-[#071322] text-slate-100 flex flex-col antialiased selection:bg-brand-orange selection:text-white pb-20 md:pb-6">
        <!-- Staff Top App Bar -->
        <header class="sticky top-0 z-30 flex items-center justify-between border-b border-brand-navy bg-[#071322]/95 px-4 py-3 backdrop-blur-md">
            <Link :href="route('staff.dashboard')" class="flex items-center gap-3">
                <ApplicationLogo 
                    size="sm" 
                    :animated="true" 
                    :with-text="true" 
                    badge="Staff" 
                    :sub-text="staffDisplayName" 
                />
            </Link>

            <!-- Connection Status Badge & Sync Control -->
            <div class="flex items-center gap-2">
                <!-- Pending Queue Indicator -->
                <div v-if="pendingSyncCount > 0" class="flex items-center gap-1 rounded-full bg-brand-orange/10 border border-brand-orange/30 px-2.5 py-1 text-xs font-semibold text-brand-orange">
                    <span class="h-2 w-2 rounded-full bg-brand-orange animate-pulse"></span>
                    <span>{{ pendingSyncCount }} Queued</span>
                </div>

                <!-- Online / Offline Status -->
                <div 
                    :class="[
                        isOnline 
                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' 
                            : 'border-rose-500/30 bg-rose-500/10 text-rose-400',
                        'flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold transition-all duration-300'
                    ]"
                >
                    <span 
                        :class="[
                            isOnline ? 'bg-emerald-400' : 'bg-rose-400',
                            'h-2 w-2 rounded-full'
                        ]"
                    ></span>
                    <span>{{ isOnline ? 'ONLINE' : 'OFFLINE' }}</span>
                </div>

                <!-- Manual Sync Trigger Button -->
                <button
                    @click="triggerSync"
                    :disabled="isSyncing || !isOnline"
                    :class="[
                        isOnline ? 'text-slate-300 hover:text-white hover:bg-brand-navy/60' : 'text-slate-600 cursor-not-allowed',
                        'rounded-xl border border-brand-navy bg-[#0B1E36] p-2 transition flex items-center gap-1 text-xs font-medium'
                    ]"
                    :title="isOnline ? 'Sync with Server' : 'Offline'"
                >
                    <svg :class="['h-4 w-4 text-brand-sky', isSyncing ? 'animate-spin' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span v-if="syncMessage" class="text-[10px] text-brand-cyan pr-1">{{ syncMessage }}</span>
                </button>

                <!-- Live Complaint Notification Bell -->
                <Link
                    :href="route('staff.dashboard', { tab: 'complaints' })"
                    class="relative rounded-xl border border-brand-navy bg-[#0B1E36] p-2 text-slate-300 hover:text-white transition flex items-center justify-center"
                    title="Assigned Tasks / Complaints"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <!-- Badge -->
                    <span 
                        v-if="assignedComplaintsCount > 0" 
                        class="absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white shadow-md animate-pulse"
                    >
                        {{ assignedComplaintsCount }}
                    </span>
                </Link>

                <!-- Admin Link if Admin -->
                <Link
                    v-if="user?.roles?.includes('admin')"
                    :href="route('admin.dashboard')"
                    class="rounded-xl border border-brand-navy bg-[#0B1E36] p-2 text-brand-sky hover:text-white"
                    title="Switch to Admin Console"
                >
                    <svg class="h-5 w-5 text-brand-sky" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>
                </Link>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-xl border border-brand-navy bg-[#0B1E36] p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10"
                    title="Logout"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </Link>
            </div>
        </header>

        <!-- Main Content View -->
        <main class="flex-1 p-4 md:p-6 max-w-4xl mx-auto w-full">
            <!-- New Task Assigned Real-Time Alert Banner -->
            <div 
                v-if="hasNewTaskAlert" 
                class="mb-4 rounded-2xl border-2 border-rose-500 bg-rose-500/20 p-4 shadow-xl flex items-center justify-between animate-bounce"
            >
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center font-bold text-lg">
                        🔔
                    </div>
                    <div>
                        <div class="text-xs font-black uppercase text-rose-300 tracking-wider">
                            নতুন কাজ এসাইন করা হয়েছে! (New Task Assigned)
                        </div>
                        <div class="text-xs text-white mt-0.5 font-medium">
                            অ্যাডমিন থেকে আপনার নামে নতুন কমপ্লেইন টিকিট এসেছে।
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('staff.dashboard', { tab: 'complaints' })"
                        @click="hasNewTaskAlert = false"
                        class="rounded-xl bg-rose-500 hover:bg-rose-600 px-3 py-1.5 text-xs font-black text-white shadow-md transition"
                    >
                        কাজ দেখুন →
                    </Link>
                    <button 
                        @click="hasNewTaskAlert = false" 
                        class="text-slate-400 hover:text-white p-1 text-xs"
                    >
                        ✕
                    </button>
                </div>
            </div>
            <!-- Flash Notification Banner -->
            <div v-if="$page.props.flash?.success" class="mb-4 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-3.5 text-xs font-bold text-emerald-400 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
            </div>
            <div v-if="$page.props.flash?.error" class="mb-4 rounded-2xl border border-rose-500/40 bg-rose-500/10 p-3.5 text-xs font-bold text-rose-400 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
            </div>

            <slot />

            <!-- Project Credit Footer -->
            <footer class="mt-12 text-center pb-4">
                <p class="text-[11px] sm:text-xs text-slate-500 font-medium tracking-wide">
                    &copy; {{ new Date().getFullYear() }} Pirgacha Internet. 
                    Developed with <span class="text-rose-500 mx-0.5">❤️</span> by 
                    <a href="https://www.facebook.com/rashedsarkarofficial" target="_blank" class="font-bold text-brand-sky hover:text-brand-cyan transition ml-0.5">Rashed Sarkar</a>
                </p>
            </footer>
        </main>


    </div>
</template>
