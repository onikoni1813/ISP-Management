<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    title: {
        type: String,
        default: 'Admin Console',
    },
});

const page = usePage();
const user = page.props.auth.user;
const isSidebarOpen = ref(true);
const isMobileMenuOpen = ref(false);

const navItems = [
    { name: 'Dashboard', href: route('admin.dashboard'), active: 'admin.dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Customers', href: route('admin.customers.index'), active: 'admin.customers.*', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { name: 'Packages & Areas', href: route('admin.packages.index'), active: 'admin.packages.*', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
    { name: 'Billing & Payments', href: route('admin.billing.invoices'), active: 'admin.billing.*', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { name: 'Staff Management', href: route('admin.staff-members.index'), active: 'admin.staff-members.*', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Complaints / Tickets', href: route('admin.complaints.index'), active: 'admin.complaints.*', icon: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Accounts & Ledger', href: route('admin.accounting.accounts'), active: 'admin.accounting.accounts*', icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
    { name: 'Expenses', href: route('admin.accounting.expenses'), active: 'admin.accounting.expenses*', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
    { name: 'Payroll & Salaries', href: route('admin.accounting.payroll'), active: 'admin.accounting.payroll*', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { name: 'SMS Engine', href: route('admin.sms.index'), active: 'admin.sms.*', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' },
    { name: 'Website CMS', href: route('admin.cms.index'), active: 'admin.cms.*', icon: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9' },
    { name: 'Audit & Reports', href: route('admin.reports.index'), active: 'admin.reports.*', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
];
</script>

<template>
    <div class="min-h-screen bg-[#071322] text-slate-100 flex flex-col antialiased selection:bg-brand-orange selection:text-white">
        <!-- Top Navbar -->
        <header class="sticky top-0 z-30 flex items-center justify-between border-b border-brand-navy bg-[#071322]/95 px-4 py-3 backdrop-blur-md lg:px-6">
            <div class="flex items-center gap-3">
                <button 
                    @click="isSidebarOpen = !isSidebarOpen" 
                    class="hidden p-2 text-slate-400 hover:text-white rounded-xl hover:bg-brand-navy/60 transition lg:block border border-transparent hover:border-brand-navy"
                    title="Toggle Sidebar"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                </button>
                <button 
                    @click="isMobileMenuOpen = !isMobileMenuOpen" 
                    class="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-brand-navy/60 transition lg:hidden border border-transparent hover:border-brand-navy"
                    title="Open Mobile Navigation"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>
                <div class="flex items-center gap-2 sm:gap-3">
                    <Link :href="route('admin.dashboard')">
                        <ApplicationLogo size="sm" :animated="true" :with-text="true" badge="Admin" />
                    </Link>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- System Status Indicator -->
                <div class="hidden sm:flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-400">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    System Live
                </div>

                <!-- Admin Complaint Notification Bell -->
                <Link
                    :href="route('admin.complaints.index')"
                    class="relative rounded-xl border border-brand-navy bg-[#0B1E36] p-2 text-slate-300 hover:text-white transition flex items-center justify-center"
                    title="Open Complaints Awaiting Assignment"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <!-- Badge for Open complaints needing assignment -->
                    <span 
                        v-if="$page.props.unread_complaints > 0" 
                        class="absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white shadow-md animate-pulse"
                    >
                        {{ $page.props.unread_complaints }}
                    </span>
                </Link>

                <!-- Profile Dropdown Link -->
                <div class="flex items-center gap-2 pl-1 sm:pl-2 border-l border-brand-navy">
                    <div class="text-right hidden md:block">
                        <div class="text-xs font-semibold text-slate-200 truncate max-w-[140px]">{{ user?.name || 'Administrator' }}</div>
                        <div class="text-[10px] text-slate-400 truncate max-w-[140px]">{{ user?.email }}</div>
                    </div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="rounded-xl p-2 text-slate-400 hover:bg-rose-500/10 hover:text-rose-400 transition"
                        title="Logout"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Wrapper -->
        <div class="flex flex-1 overflow-hidden">
            <!-- Desktop Sidebar -->
            <aside 
                :class="[
                    isSidebarOpen ? 'w-64' : 'w-20',
                    'hidden lg:flex flex-col border-r border-brand-navy bg-[#071527] transition-all duration-300'
                ]"
            >
                <div class="p-4 space-y-1.5 overflow-y-auto flex-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            route().current(item.active)
                                ? 'bg-gradient-to-r from-brand-sky to-brand-blue text-white shadow-md shadow-brand-sky/25 border border-brand-sky/40'
                                : 'text-slate-300 hover:bg-brand-navy/60 hover:text-white border border-transparent',
                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200'
                        ]"
                        :title="!isSidebarOpen ? item.name : undefined"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        <span v-if="isSidebarOpen" class="truncate">{{ item.name }}</span>
                    </Link>
                </div>

                <div class="p-4 border-t border-brand-navy text-xs text-slate-400">
                    <div v-if="isSidebarOpen" class="space-y-1">
                        <div class="font-bold text-brand-orange">Pirgacha NOC Engine</div>
                        <div class="text-[11px] text-slate-500">BTRC Compliant Platform</div>
                    </div>
                </div>
            </aside>

            <!-- Mobile Drawer Navigation -->
            <div v-if="isMobileMenuOpen" class="fixed inset-0 z-40 flex lg:hidden">
                <div @click="isMobileMenuOpen = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
                <div class="relative flex w-4/5 max-w-xs flex-col bg-[#071527] p-4 border-r border-brand-navy shadow-2xl">
                    <div class="flex items-center justify-between pb-4 border-b border-brand-navy">
                        <div class="font-bold text-white text-base">Menu</div>
                        <button @click="isMobileMenuOpen = false" class="text-slate-400 hover:text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 space-y-1.5 overflow-y-auto flex-1">
                        <Link
                            v-for="item in navItems"
                            :key="item.name"
                            :href="item.href"
                            @click="isMobileMenuOpen = false"
                            :class="[
                                route().current(item.active)
                                    ? 'bg-gradient-to-r from-brand-sky to-brand-blue text-white shadow-md'
                                    : 'text-slate-300 hover:bg-brand-navy/60 hover:text-white',
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition'
                            ]"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            <span>{{ item.name }}</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto px-3 py-4 sm:p-6 lg:p-8 bg-[#071322]">
                <div class="mx-auto max-w-7xl">
                    <!-- Flash Notification Banner -->
                    <div v-if="$page.props.flash?.success" class="mb-6 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-400 flex items-center justify-between shadow-xl">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            <span>{{ $page.props.flash.success }}</span>
                        </div>
                    </div>
                    <div v-if="$page.props.flash?.error" class="mb-6 rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 text-sm font-bold text-rose-400 flex items-center justify-between shadow-xl">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
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
                </div>
            </main>
        </div>
    </div>
</template>
