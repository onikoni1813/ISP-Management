<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'Subscriber Portal',
    },
});

const page = usePage();
const user = page.props.auth.user;

const navLinks = [
    { name: 'Dashboard', href: route('account.dashboard'), active: 'account.dashboard' },
    { name: 'My Invoices', href: route('account.invoices'), active: 'account.invoices' },
    { name: 'Payments', href: route('account.payments'), active: 'account.payments' },
    { name: 'Renew Connection', href: route('account.renewal'), active: 'account.renewal' },
    { name: 'Support Tickets', href: route('account.complaints'), active: 'account.complaints' },
    { name: 'Profile', href: route('account.profile'), active: 'account.profile' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col antialiased selection:bg-cyan-500 selection:text-white">
        <!-- Top App Navigation -->
        <header class="sticky top-0 z-30 border-b border-slate-800/80 bg-slate-900/90 px-4 py-3.5 backdrop-blur-md lg:px-8">
            <div class="max-w-6xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-tr from-cyan-600 via-teal-500 to-emerald-400 font-black text-white shadow-lg shadow-cyan-500/20">
                        PI
                    </div>
                    <div>
                        <div class="text-sm font-black text-white tracking-tight leading-tight flex items-center gap-2">
                            <span>Pirgacha Internet</span>
                            <span class="rounded-full bg-cyan-500/10 border border-cyan-500/30 px-2 py-0.5 text-[10px] font-bold text-cyan-400">Portal</span>
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">Welcome, {{ user?.name }}</div>
                    </div>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-3">
                    <Link
                        v-if="user?.roles?.includes('admin')"
                        :href="route('admin.dashboard')"
                        class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:text-white transition"
                    >
                        Admin
                    </Link>
                    <Link
                        v-if="user?.roles?.includes('staff')"
                        :href="route('staff.dashboard')"
                        class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition"
                    >
                        Staff
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs font-bold text-rose-400 hover:bg-rose-500/10 hover:border-rose-500/30 transition"
                    >
                        Sign Out
                    </Link>
                </div>
            </div>

            <!-- Desktop Sub-Navigation Bar -->
            <div class="max-w-6xl mx-auto mt-3 pt-3 border-t border-slate-800/60 hidden md:flex items-center gap-1">
                <Link
                    v-for="item in navLinks"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        route().current(item.active)
                            ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30'
                            : 'text-slate-400 hover:text-white hover:bg-slate-800/60 border-transparent',
                        'rounded-xl border px-3 py-1.5 text-xs font-bold transition'
                    ]"
                >
                    {{ item.name }}
                </Link>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-6xl mx-auto w-full p-4 md:p-6 pb-24 md:pb-12">
            <!-- Global Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-300 shadow-lg shadow-emerald-500/10 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <div v-if="$page.props.flash?.error" class="mb-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs font-bold text-rose-300 shadow-lg shadow-rose-500/10 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <slot />
        </main>

        <!-- Mobile Bottom Tab Bar (Customer Portal) -->
        <nav class="fixed bottom-0 inset-x-0 z-40 flex items-center justify-around border-t border-slate-800 bg-slate-900/95 py-2 px-2 backdrop-blur-xl md:hidden">
            <Link
                v-for="item in navLinks.slice(0, 5)"
                :key="item.name"
                :href="item.href"
                :class="[
                    route().current(item.active) ? 'text-cyan-400 font-black' : 'text-slate-400 font-medium',
                    'flex flex-col items-center gap-1 text-[11px] p-1.5 transition active:scale-95'
                ]"
            >
                <span>{{ item.name.split(' ')[0] }}</span>
            </Link>
        </nav>
    </div>
</template>
