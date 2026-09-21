<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

const page = usePage();

const props = defineProps({
    faqs: {
        type: Array,
        default: () => [],
    }
});

const defaultFaqs = [
    {
        question: 'How long does a new connection setup take?',
        category: 'General',
        answer: 'Once your application is received, our field technicians conduct an optical signal test and complete fiber drop cable wiring along with ONU configuration within 24 hours.'
    },
    {
        question: 'Is there any data limit, cap or Fair Usage Policy (FUP)?',
        category: 'General',
        answer: 'No, all Pirgacha Internet residential and commercial plans come with truly unlimited data and zero throttling.'
    },
    {
        question: 'What equipment is provided with the optical connection?',
        category: 'Setup & Connection',
        answer: 'We provide an Optical Network Unit (ONU) configured with your PPPoE credentials and optical fiber drop cable up to your router with optical patch cord.'
    },
    {
        question: 'Do you provide a Wi-Fi router with the optical connection?',
        category: 'Setup & Connection',
        answer: 'We configure your existing Wi-Fi router free of charge. We also offer high-gain dual-band (2.4GHz + 5GHz) gigabit routers tested for optical fiber throughput at subsidized rates.'
    },
    {
        question: 'How can I pay my monthly bill?',
        category: 'Billing & Payments',
        answer: 'You can pay instantly online 24/7 via bKash and Nagad through your subscriber self-service portal, or hand over cash to our audited field collection agents.'
    },
    {
        question: 'What happens if my connection is disconnected due to late payment?',
        category: 'Billing & Payments',
        answer: 'If your line is temporarily suspended due to pending dues, simply pay your invoice online via bKash or Nagad from your portal. The system automatically restores your optical connection within seconds.'
    },
    {
        question: 'What should I do if the router LOS light is blinking red?',
        category: 'Troubleshooting',
        answer: `A blinking red LOS light indicates an optical signal disconnect or physical wire break. Please log a support ticket from your portal or call our 24/7 NOC emergency hotline immediately.`
    },
    {
        question: 'Why is my internet slow on Wi-Fi even though the line is active?',
        category: 'Troubleshooting',
        answer: 'Try restarting your Wi-Fi router by turning it off for 30 seconds. Ensure the router is placed in an elevated, central location away from thick concrete walls. For peak speeds, connect via 5GHz Wi-Fi or directly via LAN cable.'
    },
    {
        question: 'Do you provide BDIX and local peering speed?',
        category: 'Technical',
        answer: 'Yes! All Pirgacha Internet connections feature ultra-low latency BDIX peering (<5ms) and high-speed multi-gigabit routing for YouTube, Facebook CDN, Google Cache, and BDIX FTP servers.'
    },
    {
        question: 'Can I get a Real IP (Public Static IP) address for CCTV or servers?',
        category: 'Technical',
        answer: 'Yes, dedicated Real Public Static IPv4 addresses are available upon request for CCTV camera streaming, corporate VPNs, port forwarding, and local server hosting.'
    },
];

const allFaqs = computed(() => {
    return (props.faqs && props.faqs.length > 0) ? props.faqs : defaultFaqs;
});

const searchQuery = ref('');
const selectedCategory = ref('All');

// Set of open FAQ indices/keys for accordion toggle
const openFaqIds = ref(new Set([0, 1])); // Open first two by default

const toggleFaq = (id) => {
    if (openFaqIds.value.has(id)) {
        openFaqIds.value.delete(id);
    } else {
        openFaqIds.value.add(id);
    }
};

const isFaqOpen = (id) => openFaqIds.value.has(id);

const expandAll = () => {
    filteredFaqs.value.forEach((_, idx) => openFaqIds.value.add(idx));
};

const collapseAll = () => {
    openFaqIds.value.clear();
};

const categories = computed(() => {
    const cats = new Set();
    allFaqs.value.forEach(f => {
        if (f.category) cats.add(f.category);
    });
    return ['All', ...Array.from(cats)];
});

const filteredFaqs = computed(() => {
    return allFaqs.value.filter(faq => {
        const matchesCategory = selectedCategory.value === 'All' || faq.category === selectedCategory.value;
        const query = searchQuery.value.trim().toLowerCase();
        if (!query) return matchesCategory;

        const matchesQuery = 
            (faq.question && faq.question.toLowerCase().includes(query)) ||
            (faq.answer && faq.answer.toLowerCase().includes(query)) ||
            (faq.category && faq.category.toLowerCase().includes(query));

        return matchesCategory && matchesQuery;
    });
});
</script>

<template>
    <Head title="Frequently Asked Questions (FAQ) - Pirgacha Internet">
        <meta name="description" content="Find quick answers to common questions about Pirgacha Internet fiber broadband setup, payments, troubleshooting, and network speed." />
    </Head>

    <WebsiteLayout>
        <!-- Hero Section with Ambient Glow -->
        <section class="relative pt-16 pb-12 overflow-hidden">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-5xl h-72 bg-gradient-to-b from-brand-sky/20 via-brand-orange/10 to-transparent blur-3xl pointer-events-none -z-10"></div>

            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-brand-orange/30 bg-brand-orange/10 px-3 py-1 text-xs font-bold text-brand-orange mb-4">
                    <span>💡</span>
                    <span>Help & Knowledge Center</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight">
                    Frequently Asked <span class="bg-gradient-to-r from-brand-sky via-brand-cyan to-brand-orange bg-clip-text text-transparent">Questions</span>
                </h1>
                
                <p class="mt-4 text-sm sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
                    Have questions about our fiber connection, billing, or equipment? We have put together quick answers for everything you need.
                </p>

                <!-- Search Bar -->
                <div class="mt-8 max-w-xl mx-auto relative">
                    <div class="relative flex items-center">
                        <span class="absolute left-4 text-slate-400 text-lg">🔍</span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search questions by keyword (e.g., billing, LOS, router, speed)..."
                            class="w-full pl-12 pr-10 py-3.5 rounded-2xl bg-[#0B1E36]/90 border border-brand-navy focus:border-brand-sky focus:ring-2 focus:ring-brand-sky/30 text-white placeholder-slate-400 text-xs sm:text-sm shadow-xl transition"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            type="button"
                            class="absolute right-3.5 text-slate-400 hover:text-white text-xs bg-slate-700/50 rounded-full w-5 h-5 flex items-center justify-center transition"
                            title="Clear search"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Category Filters Pill Bar -->
                <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        @click="selectedCategory = cat"
                        :class="[
                            selectedCategory === cat
                                ? 'bg-gradient-to-r from-brand-orange to-brand-amber text-white font-black shadow-lg shadow-brand-orange/30 border-brand-orange'
                                : 'bg-[#091A2E] text-slate-300 hover:text-white hover:bg-brand-navy border-brand-navy/80',
                            'px-4 py-1.5 rounded-xl text-xs font-semibold border transition duration-200 cursor-pointer active:scale-95'
                        ]"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>
        </section>

        <!-- FAQ Main Accordion Content -->
        <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <!-- Controls Bar (Count & Expand/Collapse) -->
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-brand-navy/60 text-xs text-slate-400">
                <div>
                    Showing <span class="text-white font-bold">{{ filteredFaqs.length }}</span> {{ filteredFaqs.length === 1 ? 'question' : 'questions' }}
                    <span v-if="selectedCategory !== 'All'"> in <span class="text-brand-orange font-semibold">{{ selectedCategory }}</span></span>
                    <span v-if="searchQuery"> matching "<span class="text-brand-sky font-semibold">{{ searchQuery }}</span>"</span>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="expandAll"
                        type="button"
                        class="hover:text-brand-cyan transition font-medium"
                    >
                        Expand All
                    </button>
                    <span class="text-slate-600">•</span>
                    <button
                        @click="collapseAll"
                        type="button"
                        class="hover:text-brand-cyan transition font-medium"
                    >
                        Collapse All
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredFaqs.length === 0" class="text-center py-16 rounded-3xl border border-dashed border-brand-navy bg-[#091A2E]/50 p-8 space-y-3">
                <div class="text-4xl">🔎</div>
                <h3 class="text-base font-bold text-white">No questions found</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    We could not find any FAQ matching "<span class="text-brand-orange">{{ searchQuery }}</span>". Feel free to reach out directly to our 24/7 support team.
                </p>
                <div class="pt-2 flex justify-center gap-3">
                    <button
                        @click="searchQuery = ''; selectedCategory = 'All'"
                        class="px-4 py-2 rounded-xl bg-brand-navy text-xs font-bold text-white hover:bg-brand-navy/80 transition"
                    >
                        Reset Filter
                    </button>
                    <Link
                        :href="route('contact')"
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber text-xs font-bold text-white shadow-md shadow-brand-orange/30 hover:shadow-brand-orange/50 transition"
                    >
                        Contact Support
                    </Link>
                </div>
            </div>

            <!-- Accordion List -->
            <div v-else class="space-y-3.5">
                <div
                    v-for="(faq, idx) in filteredFaqs"
                    :key="faq.id || idx"
                    class="rounded-2xl border transition-all duration-300 overflow-hidden"
                    :class="[
                        isFaqOpen(idx)
                            ? 'border-brand-sky/50 bg-[#0A1F38]/90 shadow-xl shadow-brand-sky/5'
                            : 'border-brand-navy/80 bg-[#091A2E]/80 hover:border-brand-sky/30 hover:bg-[#0B2038]'
                    ]"
                >
                    <!-- Header Question (Clickable) -->
                    <button
                        @click="toggleFaq(idx)"
                        type="button"
                        class="w-full text-left p-5 sm:p-6 flex items-start justify-between gap-4 cursor-pointer focus:outline-none"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center font-mono font-black text-xs"
                                :class="isFaqOpen(idx) ? 'bg-brand-orange text-white' : 'bg-brand-orange/15 text-brand-orange border border-brand-orange/30'"
                            >
                                Q
                            </span>
                            <div>
                                <h3 class="text-sm sm:text-base font-bold text-white leading-snug">
                                    {{ faq.question }}
                                </h3>
                                <div v-if="faq.category" class="mt-1.5 flex items-center gap-2">
                                    <span class="inline-block rounded-md bg-brand-sky/10 border border-brand-sky/25 px-2 py-0.5 text-[10px] font-bold text-brand-sky">
                                        {{ faq.category }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Chevron Icon with Rotation -->
                        <div
                            class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center border transition-all duration-300 mt-0.5"
                            :class="[
                                isFaqOpen(idx)
                                    ? 'bg-brand-sky/20 border-brand-sky/40 text-brand-cyan rotate-180'
                                    : 'bg-[#061322] border-brand-navy text-slate-400'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Accordion Collapsible Body -->
                    <div
                        v-show="isFaqOpen(idx)"
                        class="px-5 sm:px-6 pb-6 pt-1 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-brand-navy/40"
                    >
                        <div class="pl-9 flex items-start gap-2.5">
                            <p class="whitespace-pre-line text-slate-300/95 font-normal leading-relaxed">
                                {{ faq.answer }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Need More Help Banner / Contact Box -->
            <div class="mt-14 relative rounded-3xl border border-brand-orange/30 bg-gradient-to-r from-[#0C223D] via-[#091A2E] to-[#152336] p-8 sm:p-10 overflow-hidden shadow-2xl">
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-brand-orange/15 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-10 -top-10 w-48 h-48 bg-brand-sky/15 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
                    <div class="space-y-2 max-w-lg">
                        <span class="inline-block text-xs font-bold text-brand-orange uppercase tracking-wider">Still have questions?</span>
                        <h3 class="text-xl sm:text-2xl font-black text-white">Can't find what you're looking for?</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Our support desk and local NOC engineers in Pirgacha Sadar are available 24/7 to assist with connection inquiries, speed tests, or line diagnosis.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        <a
                            :href="`tel:${page.props.company?.hotline || '01711-000000'}`"
                            class="w-full sm:w-auto text-center rounded-xl border border-brand-navy bg-[#0B1E36] hover:bg-[#0E2646] hover:border-brand-sky/50 px-5 py-3 text-xs font-bold text-white transition active:scale-95"
                        >
                            📞 Call NOC Hotline
                        </a>
                        <Link
                            :href="route('contact')"
                            class="w-full sm:w-auto text-center rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber hover:from-brand-amber hover:to-brand-gold px-6 py-3 text-xs font-black text-white shadow-lg shadow-brand-orange/30 hover:shadow-brand-orange/50 transition active:scale-95"
                        >
                            Open Support Ticket →
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </WebsiteLayout>
</template>
