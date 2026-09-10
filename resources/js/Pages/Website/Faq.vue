<script setup>
import { Head, Link } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

const faqs = [
    {
        q: 'How long does a new connection setup take?',
        a: 'Once your application is received, our field technicians conduct a signal test and complete optical fiber wiring within 24 hours.'
    },
    {
        q: 'What equipment is provided with the connection?',
        a: 'We provide an Optical Network Unit (ONU) configured with your PPPoE credentials and optical fiber drop cable up to your router.'
    },
    {
        q: 'How can I pay my monthly bill?',
        a: 'You can pay instantly online via bKash and Nagad through your subscriber self-service portal, or hand over cash to our audited field collection agents.'
    },
    {
        q: 'Is there any data limit or FUP (Fair Usage Policy)?',
        a: 'No, all Pirgacha Internet residential and commercial plans come with truly unlimited data and zero throttling.'
    },
    {
        q: 'What should I do if the router LOS light is blinking red?',
        a: 'A blinking red LOS light indicates an optical signal disconnect or wire break. Please log a support ticket from your portal or call our 24/7 hotline at 01711-000000.'
    },
];
</script>

<template>
    <Head title="Frequently Asked Questions - Pirgacha Internet" />

    <WebsiteLayout>
        <div class="py-16 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest">Help & Support</span>
                <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">Frequently Asked Questions</h1>
                <p class="text-xs text-slate-400 mt-2">Answers to common inquiries about connection setup, billing, and technical support.</p>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(faq, idx) in faqs"
                    :key="idx"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-2"
                >
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="text-cyan-400 font-mono">Q.</span>
                        {{ faq.q }}
                    </h3>
                    <p class="text-xs text-slate-300 pl-6 leading-relaxed">
                        {{ faq.a }}
                    </p>
                </div>
            </div>
        </div>
    </WebsiteLayout>
</template>
