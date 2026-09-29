<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    hideFooter: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const user = page.props.auth?.user;
const isMobileMenuOpen = ref(false);

const navLinks = [
    { name: 'Home', href: route('home'), active: 'home' },
    { name: 'Packages', href: route('packages'), active: 'packages' },
    { name: 'About', href: route('about'), active: 'about' },
    { name: 'FAQ', href: route('faq'), active: 'faq' },
    { name: 'Notices', href: route('notices'), active: 'notices' },
    { name: 'Contact', href: route('contact'), active: 'contact' },
];
</script>

<template>
    <div class="min-h-screen bg-[#071322] text-slate-100 flex flex-col antialiased selection:bg-brand-orange selection:text-white">
        <!-- Top Public Header -->
        <header class="sticky top-0 z-50 border-b border-brand-navy/60 bg-[#071322]/90 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Brand Logo with animation -->
                <Link :href="route('home')">
                    <ApplicationLogo size="default" :animated="true" :with-text="true" />
                </Link>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-1.5">
                    <Link
                        v-for="item in navLinks"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            route().current(item.active) 
                                ? 'text-white bg-gradient-to-r from-brand-blue/40 to-brand-sky/20 border-brand-sky/50 shadow-sm shadow-brand-sky/20' 
                                : 'text-slate-300 hover:text-white hover:bg-brand-navy/30 border-transparent',
                            'px-4 py-2 rounded-xl text-xs font-bold transition border'
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
                        class="rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue hover:from-brand-cyan hover:to-brand-sky px-5 py-2.5 text-xs font-extrabold text-white shadow-lg shadow-brand-sky/25 transition active:scale-95"
                    >
                        My Portal →
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-xl border border-brand-navy bg-[#0B1E36] px-4 py-2 text-xs font-bold text-slate-200 hover:text-white hover:border-brand-sky/50 transition"
                        >
                            Sign In
                        </Link>
                        <Link
                            :href="route('contact')"
                            class="rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber hover:from-brand-amber hover:to-brand-gold px-5 py-2.5 text-xs font-black text-white shadow-lg shadow-brand-orange/30 hover:shadow-brand-orange/50 transition active:scale-95"
                        >
                            Get Connected
                        </Link>
                    </template>
                </div>

                <!-- Mobile Menu Button -->
                <button
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                    class="p-2 rounded-xl border border-brand-navy bg-[#0B1E36] text-slate-400 hover:text-white md:hidden"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Nav Menu -->
            <div v-if="isMobileMenuOpen" class="md:hidden border-t border-brand-navy bg-[#071322] p-4 space-y-2">
                <Link
                    v-for="item in navLinks"
                    :key="item.name"
                    :href="item.href"
                    class="block px-3.5 py-2 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-brand-navy/50"
                >
                    {{ item.name }}
                </Link>
                <div class="pt-3 border-t border-brand-navy flex gap-2">
                    <Link
                        v-if="user"
                        :href="route('dashboard')"
                        class="flex-1 text-center rounded-xl bg-brand-sky p-2.5 text-xs font-bold text-white shadow-md"
                    >
                        My Portal
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="flex-1 text-center rounded-xl border border-brand-navy bg-[#0B1E36] p-2.5 text-xs font-bold text-slate-300"
                        >
                            Sign In
                        </Link>
                        <Link
                            :href="route('contact')"
                            class="flex-1 text-center rounded-xl bg-brand-orange p-2.5 text-xs font-bold text-white shadow-md shadow-brand-orange/30"
                        >
                            Connect
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Flash Notice Banner -->
        <div v-if="$page.props.flash?.success" class="bg-brand-blue/20 border-b border-brand-sky/40 py-3 px-4 text-center text-xs font-bold text-brand-cyan">
            {{ $page.props.flash.success }}
        </div>

        <!-- Body Slot -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Modern Footer -->
        <footer v-if="!hideFooter" class="border-t border-brand-navy/80 bg-[#071527] text-slate-400 text-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="space-y-3">
                    <ApplicationLogo size="sm" :animated="true" :with-text="true" />
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Leading optical broadband internet service provider in Pirgacha, delivering high-speed optical fiber connectivity with 99.9% uptime.
                    </p>
                </div>

                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-brand-sky mb-3">Fast Navigation</h3>
                    <ul class="space-y-2">
                        <li><Link :href="route('packages')" class="hover:text-brand-cyan transition">Broadband Packages</Link></li>
                        <li><Link :href="route('faq')" class="hover:text-brand-cyan transition">FAQ & Setup Help</Link></li>
                        <li><Link :href="route('notices')" class="hover:text-brand-cyan transition">Network Notices</Link></li>
                        <li><Link :href="route('contact')" class="hover:text-brand-cyan transition">Contact Us</Link></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-brand-sky mb-3">Support & NOC</h3>
                    <ul class="space-y-2">
                        <li>Hotline: <span class="text-white font-mono font-bold">{{ $page.props.company?.hotline || '01711-000000' }}</span></li>
                        <li>Email: <span class="text-white">{{ $page.props.company?.email || 'support@pirgachainternet.com' }}</span></li>
                        <li>Support: <span class="text-brand-orange font-semibold">{{ $page.props.company?.working_hours || '24/7 Field & Remote NOC' }}</span></li>
                        <li>{{ $page.props.company?.address || 'Town Center, Pirgacha Sadar, Rangpur' }}</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-brand-orange mb-3">Self-Service</h3>
                    <p class="text-xs text-slate-400 mb-3">Manage your connection, invoices, and instant mobile wallet renewal.</p>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-xl border border-brand-orange/40 bg-brand-orange/10 px-4 py-2 text-xs font-bold text-brand-orange hover:bg-brand-orange/20 transition"
                    >
                        Subscriber Login →
                    </Link>
                </div>
            </div>

            <div class="border-t border-brand-navy/50 py-6 text-center text-[11px] text-slate-500">
                <p class="mb-1">© {{ new Date().getFullYear() }} Pirgacha Internet. All rights reserved. BTRC Licensed Broadband Provider.</p>
                <p class="font-medium tracking-wide text-slate-500">
                    Developed with <span class="text-rose-500 mx-0.5">❤️</span> by 
                    <a href="https://www.facebook.com/rashedsarkarofficial" target="_blank" class="font-bold text-brand-sky hover:text-brand-cyan transition ml-0.5">Rashed Sarkar</a>
                </p>
            </div>
        </footer>
    </div>
</template>
