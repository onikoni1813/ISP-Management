<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

const props = defineProps({
    notices: {
        type: Array,
        default: () => [],
    }
});

const defaultNotices = [
    {
        title: 'BTRC National Optical Backbone Upgradation Notice',
        published_at: '2026-09-01',
        category: 'Maintenance',
        content: 'Upstream optical submarine cable maintenance will be carried out between 02:00 AM and 05:00 AM. Minimal latency fluctuations may be observed.'
    },
    {
        title: 'New Fiber Distribution Node in Pirgacha College Road',
        published_at: '2026-08-15',
        category: 'Expansion',
        content: 'We have commissioned a new 10G optical distribution node covering College Road and student mess facilities for high-capacity gigabit routing.'
    },
    {
        title: 'Instant bKash & Nagad Self-Service Renewal Launched',
        published_at: '2026-07-20',
        category: 'System',
        content: 'Subscribers can now extend their connection validity instantly from the /account self-service portal using any mobile wallet.'
    },
];

const displayNotices = computed(() => {
    return (props.notices && props.notices.length > 0) ? props.notices : defaultNotices;
});
</script>

<template>
    <Head title="Network Notices & Announcements - Pirgacha Internet" />

    <WebsiteLayout>
        <div class="py-16 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-widest">NOC Bulletin</span>
                <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">Notices & Network Updates</h1>
                <p class="text-xs text-slate-400 mt-2">Official service announcements, scheduled maintenance windows, and system updates.</p>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(n, idx) in displayNotices"
                    :key="idx"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <span class="rounded-full bg-cyan-500/10 border border-cyan-500/20 px-2.5 py-0.5 text-[10px] font-bold text-cyan-400">
                            {{ n.category }}
                        </span>
                        <span class="text-xs font-mono text-slate-500">
                            {{ n.published_at ? new Date(n.published_at).toLocaleDateString() : 'Active' }}
                        </span>
                    </div>

                    <h3 class="text-base font-bold text-white">{{ n.title }}</h3>
                    <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line">{{ n.content }}</p>
                </div>
            </div>
        </div>
    </WebsiteLayout>
</template>
