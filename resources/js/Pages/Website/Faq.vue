<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

const props = defineProps({
    faqs: {
        type: Array,
        default: () => [],
    }
});

const defaultFaqs = [
    {
        question: 'How long does a new connection setup take?',
        answer: 'Once your application is received, our field technicians conduct a signal test and complete optical fiber wiring within 24 hours.'
    },
    {
        question: 'What equipment is provided with the connection?',
        answer: 'We provide an Optical Network Unit (ONU) configured with your PPPoE credentials and optical fiber drop cable up to your router.'
    },
    {
        question: 'How can I pay my monthly bill?',
        answer: 'You can pay instantly online via bKash and Nagad through your subscriber self-service portal, or hand over cash to our audited field collection agents.'
    },
    {
        question: 'Is there any data limit or FUP (Fair Usage Policy)?',
        answer: 'No, all Pirgacha Internet residential and commercial plans come with truly unlimited data and zero throttling.'
    },
    {
        question: 'What should I do if the router LOS light is blinking red?',
        answer: 'A blinking red LOS light indicates an optical signal disconnect or wire break. Please log a support ticket from your portal or call our 24/7 hotline at 01711-000000.'
    },
];

const displayFaqs = computed(() => {
    return (props.faqs && props.faqs.length > 0) ? props.faqs : defaultFaqs;
});
</script>

<template>
    <Head title="Frequently Asked Questions - Pirgacha Internet" />

    <WebsiteLayout>
        <div class="py-16 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-xs font-bold text-brand-orange uppercase tracking-widest">Help & Support</span>
                <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">Frequently Asked Questions</h1>
                <p class="text-xs text-slate-400 mt-2">Answers to common inquiries about connection setup, billing, and technical support.</p>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(faq, idx) in displayFaqs"
                    :key="idx"
                    class="rounded-3xl border border-brand-navy bg-[#091A2E]/80 p-6 backdrop-blur-sm space-y-2 hover:border-brand-sky/40 transition"
                >
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span class="text-brand-orange font-mono">Q.</span>
                            {{ faq.question }}
                        </h3>
                        <span v-if="faq.category" class="rounded-full bg-brand-sky/10 border border-brand-sky/30 px-2.5 py-0.5 text-[10px] font-bold text-brand-sky">
                            {{ faq.category }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed pl-6">
                        {{ faq.answer }}
                    </p>
                </div>
            </div>
        </div>
    </WebsiteLayout>
</template>
