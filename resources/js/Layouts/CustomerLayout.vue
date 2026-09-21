<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    title: {
        type: String,
        default: 'Subscriber Portal',
    },
});

const page = usePage();
const user = page.props.auth.user;

const navLinks = [
    { 
        name: 'Dashboard', 
        href: route('account.dashboard'), 
        active: 'account.dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' 
    },
    { 
        name: 'Renew', 
        fullName: 'Renew Connection',
        href: route('account.renewal'), 
        active: 'account.renewal',
        icon: 'M13 10V3L4 14h7v7l9-11h-7z' 
    },
    { 
        name: 'Upgrade', 
        fullName: 'Upgrade Package',
        href: route('account.upgrade'), 
        active: 'account.upgrade',
        icon: 'M7 11l5-5m0 0l5 5m-5-5v12' 
    },
    { 
        name: 'Profile', 
        fullName: 'My Profile',
        href: route('account.profile'), 
        active: 'account.profile',
        icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' 
    },
];
</script>

<template>
    <div class="min-h-screen bg-[#071322] text-slate-100 flex flex-col antialiased selection:bg-brand-orange selection:text-white">
        <!-- Top App Navigation -->
        <header class="sticky top-0 z-30 border-b border-brand-navy bg-[#071322]/95 px-4 py-3.5 backdrop-blur-md lg:px-8">
            <div class="max-w-6xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('account.dashboard')" class="flex items-center gap-3">
                        <ApplicationLogo size="sm" :animated="true" />
                        <div>
                            <div class="text-sm font-black text-white tracking-tight leading-tight flex items-center gap-2">
                                <span>Pirgacha Internet</span>
                            </div>
                            <div class="text-xs text-slate-400 mt-0.5">Welcome, {{ user?.name }}</div>
                        </div>
                    </Link>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-3">
                    <Link
                        v-if="user?.roles?.includes('admin')"
                        :href="route('admin.dashboard')"
                        class="rounded-xl border border-brand-navy bg-[#0B1E36] px-3 py-1.5 text-xs font-semibold text-slate-300 hover:text-white transition"
                    >
                        Admin
                    </Link>
                    <Link
                        v-if="user?.roles?.includes('staff')"
                        :href="route('staff.dashboard')"
                        class="rounded-xl border border-brand-navy bg-[#0B1E36] px-3 py-1.5 text-xs font-semibold text-brand-sky hover:text-brand-cyan transition"
                    >
                        Staff
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="rounded-xl border border-brand-navy bg-[#0B1E36] px-3 py-1.5 text-xs font-bold text-rose-400 hover:bg-rose-500/10 hover:border-rose-500/30 transition"
                    >
                        Sign Out
                    </Link>
                </div>
            </div>

            <!-- Desktop Sub-Navigation Bar -->
            <div class="max-w-6xl mx-auto mt-3 pt-3 border-t border-brand-navy/60 hidden md:flex items-center gap-1.5">
                <Link
                    v-for="item in navLinks"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        route().current(item.active)
                            ? 'bg-gradient-to-r from-brand-sky/20 to-brand-blue/30 text-white border-brand-sky/50 shadow-sm shadow-brand-sky/20'
                            : 'text-slate-400 hover:text-white hover:bg-brand-navy/40 border-transparent',
                        'rounded-xl border px-3.5 py-1.5 text-xs font-bold transition'
                    ]"
                >
                    {{ item.fullName || item.name }}
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

            <!-- Project Credit Footer -->
            <footer class="mt-8 mb-4 md:mb-0 text-center">
                <p class="text-[11px] sm:text-xs text-slate-500 font-medium tracking-wide">
                    &copy; {{ new Date().getFullYear() }} Pirgacha Internet. 
                    Developed with <span class="text-rose-500 mx-0.5">❤️</span> by 
                    <a href="https://www.facebook.com/rashedsarkarofficial" target="_blank" class="font-bold text-brand-sky hover:text-brand-cyan transition ml-0.5">Rashed Sarkar</a>
                </p>
            </footer>
        </main>

        <!-- Mobile Bottom Tab Bar (Professional Native App Style) -->
        <nav class="fixed bottom-0 inset-x-0 z-40 border-t border-brand-navy/80 bg-[#071322]/95 backdrop-blur-xl md:hidden px-3 py-1.5 shadow-2xl shadow-black/80">
            <div class="grid grid-cols-4 gap-1">
                <Link
                    v-for="item in navLinks"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        route().current(item.active)
                            ? 'text-brand-orange'
                            : 'text-slate-400 hover:text-slate-200',
                        'group flex flex-col items-center justify-center py-1 px-1 rounded-xl transition active:scale-90 relative'
                    ]"
                >
                    <!-- Active background pill glow -->
                    <div 
                        v-if="route().current(item.active)" 
                        class="absolute -top-1.5 w-6 h-1 rounded-full bg-brand-orange shadow-sm shadow-brand-orange"
                    ></div>

                    <!-- Icon Container -->
                    <div 
                        :class="[
                            route().current(item.active)
                                ? 'bg-brand-orange/15 text-brand-orange shadow-sm shadow-brand-orange/20'
                                : 'text-slate-400 group-hover:text-slate-300',
                            'p-1.5 rounded-xl transition duration-200'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                    </div>

                    <!-- Label -->
                    <span 
                        :class="[
                            route().current(item.active) ? 'font-black text-brand-orange' : 'font-medium text-slate-400',
                            'text-[10px] tracking-tight leading-tight mt-0.5'
                        ]"
                    >
                        {{ item.name }}
                    </span>
                </Link>
            </div>
        </nav>
    </div>
</template>
