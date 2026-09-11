<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-slate-950 px-4 py-8 antialiased selection:bg-cyan-500 selection:text-white"
    >
        <div class="mb-6">
            <Link href="/">
                <ApplicationLogo size="xl" :animated="true" :with-text="true" sub-text="Subscriber & Staff Portal" />
            </Link>
        </div>

        <div
            class="w-full overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/90 px-8 py-8 shadow-2xl backdrop-blur-xl sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>

