<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { syncService } from '@/Services/syncService';

defineProps({
    title: {
        type: String,
        default: 'Staff Portal',
    },
});

const page = usePage();
const user = page.props.auth.user;

const isOnline = ref(navigator.onLine);
const pendingSyncCount = ref(0);
const isSyncing = ref(false);
const syncMessage = ref('');

const checkSyncStatus = async () => {
    try {
        const status = await syncService.getSyncStatus();
        pendingSyncCount.value = status.pendingCount;
    } catch (e) {
        console.error('Error getting sync status', e);
    }
};

const triggerSync = async () => {
    if (!navigator.onLine) {
        alert('Network offline. Connect to internet to sync.');
        return;
    }

    isSyncing.value = true;
    syncMessage.value = 'Syncing...';
    try {
        // 1. Flush pending offline actions
        await syncService.flushPendingMutations();
        // 2. Download fresh caches
        await syncService.downloadBootstrapCache();
        await checkSyncStatus();
        syncMessage.value = 'Synced';
        setTimeout(() => { syncMessage.value = ''; }, 2500);
    } catch (err) {
        console.error('Manual sync failed', err);
        syncMessage.value = 'Sync error';
        setTimeout(() => { syncMessage.value = ''; }, 3000);
    } finally {
        isSyncing.value = false;
    }
};

const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
    if (isOnline.value) {
        // Auto-flush pending mutations when coming back online
        syncService.flushPendingMutations().then(checkSyncStatus);
    }
};

let syncInterval = null;

onMounted(() => {
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    checkSyncStatus();
    syncInterval = setInterval(checkSyncStatus, 15000);

    // Initial cache download if online
    if (navigator.onLine) {
        syncService.downloadBootstrapCache().catch(() => {});
    }
});

onUnmounted(() => {
    window.removeEventListener('online', updateOnlineStatus);
    window.removeEventListener('offline', updateOnlineStatus);
    if (syncInterval) clearInterval(syncInterval);
});

const quickActions = [
    { name: 'Search', icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', href: route('staff.dashboard') },
    { name: 'Collect', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', href: route('staff.dashboard') },
    { name: 'Renew', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', href: route('staff.dashboard') },
    { name: 'Tickets', icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z', href: route('staff.dashboard') },
];
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col antialiased selection:bg-emerald-500 selection:text-white pb-20 md:pb-6">
        <!-- Staff Top App Bar -->
        <header class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-800 bg-slate-900/90 px-4 py-3 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 font-bold text-white shadow-lg shadow-emerald-500/20">
                    S
                </div>
                <div>
                    <h1 class="text-sm font-bold text-white leading-tight">Field Assistant</h1>
                    <p class="text-[11px] text-slate-400 font-medium">{{ user?.name }}</p>
                </div>
            </div>

            <!-- Connection Status Badge & Sync Control -->
            <div class="flex items-center gap-2">
                <!-- Pending Queue Indicator -->
                <div v-if="pendingSyncCount > 0" class="flex items-center gap-1 rounded-full bg-amber-500/10 border border-amber-500/30 px-2.5 py-1 text-xs font-semibold text-amber-400">
                    <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
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
                        isOnline ? 'text-slate-300 hover:text-white hover:bg-slate-800' : 'text-slate-600 cursor-not-allowed',
                        'rounded-lg border border-slate-700 bg-slate-900/80 p-1.5 transition flex items-center gap-1 text-xs font-medium'
                    ]"
                    :title="isOnline ? 'Sync with Server' : 'Offline'"
                >
                    <svg :class="['h-4 w-4 text-cyan-400', isSyncing ? 'animate-spin' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span v-if="syncMessage" class="text-[10px] text-cyan-300 pr-1">{{ syncMessage }}</span>
                </button>

                <!-- Admin Link if Admin -->
                <Link
                    v-if="user?.roles?.includes('admin')"
                    :href="route('admin.dashboard')"
                    class="rounded-lg border border-slate-700 bg-slate-800 p-1.5 text-slate-300 hover:text-white"
                    title="Switch to Admin Console"
                >
                    <svg class="h-5 w-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>
                </Link>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </Link>
            </div>
        </header>

        <!-- Main Content View -->
        <main class="flex-1 p-4 md:p-6 max-w-4xl mx-auto w-full">
            <slot />
        </main>

        <!-- Mobile Bottom Navigation (PWA Optimized) -->
        <nav class="fixed bottom-0 inset-x-0 z-40 flex items-center justify-around border-t border-slate-800 bg-slate-900/95 py-2 px-3 backdrop-blur-lg md:hidden">
            <Link
                v-for="action in quickActions"
                :key="action.name"
                :href="action.href"
                class="flex flex-col items-center gap-1 text-slate-400 hover:text-emerald-400 active:scale-95 transition"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="action.icon" />
                </svg>
                <span class="text-[10px] font-medium">{{ action.name }}</span>
            </Link>
        </nav>
    </div>
</template>
