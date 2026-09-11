<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-[#071322] px-4 py-8 antialiased selection:bg-brand-orange selection:text-white"
    >
        <div class="mb-6">
            <Link href="/">
                <ApplicationLogo size="xl" :animated="true" :with-text="true" sub-text="Subscriber & Staff Portal" />
            </Link>
        </div>

        <div
            class="w-full overflow-hidden rounded-3xl border border-brand-navy bg-[#091A2E]/90 px-8 py-8 shadow-2xl backdrop-blur-xl sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>

