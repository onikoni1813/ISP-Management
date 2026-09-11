<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const page = usePage();
const user = page.props.auth?.user;
const isMobileMenuOpen = ref(false);

const navLinks = [
    { name: 'Home', href: route('home'), active: 'home' },
    { name: 'Packages', href: route('packages'), active: 'packages' },
    { name: 'Coverage', href: route('coverage'), active: 'coverage' },
    { name: 'About', href: route('about'), active: 'about' },
    { name: 'FAQ', href: route('faq'), active: 'faq' },
    { name: 'Notices', href: route('notices'), active: 'notices' },
    { name: 'Contact', href: route('contact'), active: 'contact' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col antialiased selection:bg-cyan-500 selection:text-white">
        <!-- Top Public Header -->
        <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/90 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Brand Logo with animation -->
                <Link :href="route('home')">
                    <ApplicationLogo size="default" :animated="true" :with-text="true" />
                </Link>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-1">
                    <Link
                        v-for="item in navLinks"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            route().current(item.active) ? 'text-cyan-400 bg-cyan-500/10 border-cyan-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-900 border-transparent',
                            'px-3.5 py-2 rounded-xl text-xs font-bold transition border'
                        ]"
                    >
                        {{ item.name }}
                    </Link>
                </nav>

                <!-- Auth & Action Buttons -->
                <div class="hidden md:flex items-center gap-3">
                    <Link
                        v-if="user"
                        :href="route('dashboard')"
                        class="rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 px-4 py-2 text-xs font-extrabold text-white shadow-lg shadow-cyan-600/20 transition active:scale-95"
                    >
                        My Portal →
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-xl border border-slate-700 bg-slate-900 px-4 py-2 text-xs font-bold text-slate-300 hover:text-white hover:border-slate-600 transition"
                        >
                            Sign In
                        </Link>
                        <Link
                            :href="route('contact')"
                            class="rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 px-4 py-2 text-xs font-extrabold text-white shadow-lg shadow-cyan-600/20 hover:from-cyan-500 hover:to-teal-400 transition active:scale-95"
                        >
                            Get Connected
                        </Link>
                    </template>
                </div>

                <!-- Mobile Menu Button -->
                <button
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                    class="p-2 rounded-xl border border-slate-800 bg-slate-900 text-slate-400 hover:text-white md:hidden"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Nav Menu -->
            <div v-if="isMobileMenuOpen" class="md:hidden border-t border-slate-800 bg-slate-950 p-4 space-y-2">
                <Link
                    v-for="item in navLinks"
                    :key="item.name"
                    :href="item.href"
                    class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-900"
                >
                    {{ item.name }}
                </Link>
                <div class="pt-3 border-t border-slate-800 flex gap-2">
                    <Link
                        v-if="user"
                        :href="route('dashboard')"
                        class="flex-1 text-center rounded-xl bg-cyan-600 p-2.5 text-xs font-bold text-white"
                    >
                        My Portal
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="flex-1 text-center rounded-xl border border-slate-700 bg-slate-900 p-2.5 text-xs font-bold text-slate-300"
                        >
                            Sign In
                        </Link>
                        <Link
                            :href="route('contact')"
                            class="flex-1 text-center rounded-xl bg-cyan-600 p-2.5 text-xs font-bold text-white"
                        >
                            Connect
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Flash Notice Banner -->
        <div v-if="$page.props.flash?.success" class="bg-emerald-500/10 border-b border-emerald-500/30 py-3 px-4 text-center text-xs font-bold text-emerald-300">
            {{ $page.props.flash.success }}
        </div>

        <!-- Body Slot -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Modern Footer -->
        <footer class="border-t border-slate-800 bg-slate-950 text-slate-400 text-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="space-y-3">
                    <ApplicationLogo size="sm" :animated="true" :with-text="true" />
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Leading optical broadband internet service provider in Pirgacha, delivering high-speed optical fiber connectivity with 99.9% uptime.
                    </p>
                </div>

                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-white mb-3">Fast Navigation</h3>
                    <ul class="space-y-2">
                        <li><Link :href="route('packages')" class="hover:text-cyan-400">Broadband Packages</Link></li>
                        <li><Link :href="route('coverage')" class="hover:text-cyan-400">Coverage Zones</Link></li>
                        <li><Link :href="route('faq')" class="hover:text-cyan-400">FAQ & Setup Help</Link></li>
                        <li><Link :href="route('notices')" class="hover:text-cyan-400">Network Notices</Link></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-white mb-3">Support & NOC</h3>
                    <ul class="space-y-2">
                        <li>Hotline: <span class="text-white font-mono font-bold">01711-000000</span></li>
                        <li>Email: <span class="text-white">support@pirgachainternet.com</span></li>
                        <li>Support: 24/7 Field & Remote Assistance</li>
                        <li>Town Center, Pirgacha Sadar, Rangpur</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-white mb-3">Self-Service</h3>
                    <p class="text-xs text-slate-400 mb-3">Manage your connection, invoices, and instant mobile wallet renewal.</p>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-xl border border-cyan-500/40 bg-cyan-500/10 px-4 py-2 text-xs font-bold text-cyan-300 hover:bg-cyan-500/20"
                    >
                        Subscriber Login →
                    </Link>
                </div>
            </div>

            <div class="border-t border-slate-900 py-6 text-center text-[11px] text-slate-600">
                © 2026 Pirgacha Internet. All rights reserved. BTRC Licensed Broadband Provider.
            </div>
        </footer>
    </div>
</template>
